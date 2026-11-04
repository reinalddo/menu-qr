<?php

declare(strict_types=1);

namespace MenuQr;

use MenuQr\Models\Stats;
use PDO;
use ZipArchive;

/**
 * ZIP backup: manifest.json, data.json and media/*.webp.
 * Admin accounts and secrets are never exported. On import, the menu address of
 * the current server is kept, because the backup may come from another one.
 */
final class Backup
{
    public const FORMAT = 1;

    private const MAX_ENTRIES = 5000;
    private const MAX_TOTAL_BYTES = 600 * 1024 * 1024;
    private const MAX_FILE_BYTES = 12 * 1024 * 1024;
    private const MAX_DATA_BYTES = 20 * 1024 * 1024;
    private const MEDIA_PATTERN = '#^media/([a-f0-9]{32}(?:-thumb)?)\.webp$#';

    /** Settings that travel in a backup. menu_url and installed_at belong to the server. */
    public const SETTINGS = [
        'restaurant_name', 'tagline', 'address', 'maps_url', 'whatsapp', 'instagram', 'logo', 'cover',
        'menu_language', 'timezone', 'currency_code', 'currency_symbol', 'currency_decimals', 'number_format',
        'brand_color', 'show_powered_by', 'opening_hours', 'notice_enabled', 'notice_text',
        'second_currency_enabled', 'second_currency_code', 'second_currency_symbol', 'second_currency_rate',
        'second_currency_decimals', 'second_currency_rate_updated_at',
    ];

    /** @return string path of a temporary ZIP file; the caller deletes it */
    public static function export(bool $withStats): string
    {
        $pdo = Db::pdo();
        $settings = array_intersect_key(Settings::all(), array_flip(self::SETTINGS));
        $data = [
            'settings' => $settings,
            'categories' => $pdo->query('SELECT id, name, description, position, is_visible FROM categories ORDER BY id')->fetchAll(),
            'dishes' => $pdo->query(
                'SELECT id, category_id, name, description, price, photo, is_available, is_visible, is_featured, position
                 FROM dishes ORDER BY id'
            )->fetchAll(),
            'dish_sizes' => $pdo->query('SELECT dish_id, name, price, position FROM dish_sizes ORDER BY dish_id, position, id')->fetchAll(),
            'dish_tags' => $pdo->query('SELECT dish_id, tag FROM dish_tags ORDER BY dish_id, tag')->fetchAll(),
        ];
        if ($withStats) {
            $data['visit_counts'] = $pdo->query('SELECT day, source, views FROM visit_counts ORDER BY day, source')->fetchAll();
            $data['dish_view_counts'] = $pdo->query('SELECT day, dish_id, views FROM dish_view_counts ORDER BY day, dish_id')->fetchAll();
        }
        $manifest = [
            'app' => 'menu-qr',
            'format' => self::FORMAT,
            'app_version' => APP_VERSION,
            'exported_at' => gmdate('c'),
            'includes_stats' => $withStats,
        ];

        $path = (string) tempnam(sys_get_temp_dir(), 'menuqr-backup-');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the backup file');
        }
        $json = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        $zip->addFromString('manifest.json', json_encode($manifest, $json));
        $zip->addFromString('data.json', json_encode($data, $json));

        $images = array_filter([...array_column($data['dishes'], 'photo'), $settings['logo'] ?? '', $settings['cover'] ?? '']);
        foreach (array_unique($images) as $name) {
            foreach (['', '-thumb'] as $suffix) {
                $file = Config::dataDir() . '/uploads/' . $name . $suffix . '.webp';
                if (is_file($file)) {
                    $zip->addFile($file, 'media/' . $name . $suffix . '.webp');
                    // Already compressed images: storing them is faster and the ZIP is the same size.
                    $zip->setCompressionName('media/' . $name . $suffix . '.webp', ZipArchive::CM_STORE);
                }
            }
        }
        $zip->close();
        return $path;
    }

    /**
     * Replaces the whole menu with the backup's content.
     *
     * @return array{categories: int, dishes: int, photos: int}
     * @throws BackupException with a message for the admin
     */
    public static function import(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new BackupException(t('backup.error.not_zip'));
        }
        try {
            $media = self::inspect($zip);

            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (!is_array($manifest) || ($manifest['app'] ?? null) !== 'menu-qr' || ($manifest['format'] ?? null) !== self::FORMAT) {
                throw new BackupException(t('backup.error.not_menuqr'));
            }
            $data = json_decode((string) $zip->getFromName('data.json'), true);
            if (!is_array($data)) {
                throw new BackupException(t('backup.error.data'));
            }
            $clean = self::validate($data, $media);
            $written = self::extractMedia($zip, $media, $clean['images']);
        } finally {
            $zip->close();
        }

        $pdo = Db::pdo();
        try {
            $pdo->beginTransaction();
            self::replaceRows($pdo, $clean);
            Settings::setMany($clean['settings']);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $exception;
        }

        self::removeUnusedMedia($clean['images']);
        return [
            'categories' => count($clean['categories']),
            'dishes' => count($clean['dishes']),
            'photos' => count(array_filter(array_column($clean['dishes'], 'photo'))),
        ];
    }

    /**
     * Rejects ZIPs with unexpected files, paths or sizes before reading anything.
     *
     * @return array<string, int> media name (without .webp) => entry index
     */
    private static function inspect(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > self::MAX_ENTRIES) {
            throw new BackupException(t('backup.error.not_menuqr'));
        }
        $media = [];
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            if ($entry === false) {
                throw new BackupException(t('backup.error.not_zip'));
            }
            $name = (string) $entry['name'];
            $size = (int) $entry['size'];
            $total += $size;

            if ($name === 'manifest.json' || $name === 'data.json') {
                if ($size > self::MAX_DATA_BYTES) {
                    throw new BackupException(t('backup.error.too_big'));
                }
            } elseif (preg_match(self::MEDIA_PATTERN, $name, $matches)) {
                if ($size > self::MAX_FILE_BYTES) {
                    throw new BackupException(t('backup.error.too_big'));
                }
                $media[$matches[1]] = $i;
            } elseif ($name !== 'media/') {
                throw new BackupException(t('backup.error.unexpected_file', ['name' => mb_substr($name, 0, 80)]));
            }
        }
        if ($total > self::MAX_TOTAL_BYTES) {
            throw new BackupException(t('backup.error.too_big'));
        }
        return $media;
    }

    /**
     * @param array<string, int> $media
     * @return array{settings: array<string, string>, categories: array, dishes: array, dish_sizes: array,
     *               dish_tags: array, stats: bool, visit_counts: array, dish_view_counts: array, images: string[]}
     */
    private static function validate(array $data, array $media): array
    {
        $fail = static fn () => throw new BackupException(t('backup.error.data'));
        $list = static fn (string $key): array => is_array($data[$key] ?? null) ? array_values($data[$key]) : $fail();
        $int = static fn (mixed $value, int $min = PHP_INT_MIN): int
            => is_int($value) && $value >= $min ? $value : (is_string($value) && preg_match('/^-?\d+$/', $value) && (int) $value >= $min ? (int) $value : $fail());
        $flag = static fn (mixed $value): int => in_array($value, [0, 1, '0', '1', true, false], true) ? (int) $value : $fail();
        $text = static fn (mixed $value, int $min, int $max): string
            => is_string($value) && mb_strlen($value) >= $min && mb_strlen($value) <= $max ? $value : $fail();
        $optional = static fn (mixed $value, int $max): ?string => $value === null || $value === '' ? null : $text($value, 1, $max);
        $price = static fn (mixed $value): string
            => (is_string($value) || is_int($value) || is_float($value)) && preg_match('/^\d{1,8}(\.\d{1,2})?$/', (string) $value) ? (string) $value : $fail();
        $day = static fn (mixed $value): string
            => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)) ? $value : $fail();
        $image = static fn (mixed $value): ?string
            => is_string($value) && preg_match('/^[a-f0-9]{32}$/', $value) && isset($media[$value]) ? $value : null;

        $settings = [];
        foreach ((is_array($data['settings'] ?? null) ? $data['settings'] : $fail()) as $name => $value) {
            if (in_array($name, self::SETTINGS, true) && is_scalar($value) && mb_strlen((string) $value) <= 5000) {
                $settings[$name] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            }
        }
        foreach (['logo', 'cover'] as $kind) {
            if (array_key_exists($kind, $settings)) {
                $settings[$kind] = $image($settings[$kind]) ?? '';
            }
        }
        if (($settings['restaurant_name'] ?? '') === '') {
            $fail();
        }
        // Values the menu relies on: an unknown one falls back to a safe default instead of breaking the page.
        $allowed = [
            'timezone' => [\DateTimeZone::listIdentifiers(), 'UTC'],
            'menu_language' => [array_keys(I18n::LOCALES), I18n::DEFAULT],
            'currency_decimals' => [['0', '2'], '2'],
            'second_currency_decimals' => [['0', '2'], '2'],
            'number_format' => [Money::FORMATS, '1,234.56'],
        ];
        foreach ($allowed as $name => [$values, $default]) {
            if (array_key_exists($name, $settings) && !in_array($settings[$name], $values, true)) {
                $settings[$name] = $default;
            }
        }

        $categories = [];
        foreach ($list('categories') as $row) {
            $id = $int($row['id'] ?? null, 1);
            isset($categories[$id]) && $fail();
            $categories[$id] = [
                'id' => $id,
                'name' => $text($row['name'] ?? null, 1, 120),
                'description' => $optional($row['description'] ?? null, 500),
                'position' => $int($row['position'] ?? 0),
                'is_visible' => $flag($row['is_visible'] ?? 1),
            ];
        }

        $dishes = [];
        foreach ($list('dishes') as $row) {
            $id = $int($row['id'] ?? null, 1);
            $category = $int($row['category_id'] ?? null, 1);
            (isset($dishes[$id]) || !isset($categories[$category])) && $fail();
            $dishes[$id] = [
                'id' => $id,
                'category_id' => $category,
                'name' => $text($row['name'] ?? null, 1, 150),
                'description' => $optional($row['description'] ?? null, 1000),
                'price' => $price($row['price'] ?? null),
                // A photo missing from the ZIP is dropped instead of leaving a broken image.
                'photo' => $image($row['photo'] ?? null),
                'is_available' => $flag($row['is_available'] ?? 1),
                'is_visible' => $flag($row['is_visible'] ?? 1),
                'is_featured' => $flag($row['is_featured'] ?? 0),
                'position' => $int($row['position'] ?? 0),
            ];
        }

        $sizes = [];
        foreach ($list('dish_sizes') as $row) {
            $dish = $int($row['dish_id'] ?? null, 1);
            isset($dishes[$dish]) || $fail();
            $sizes[] = [$dish, $text($row['name'] ?? null, 1, 60), $price($row['price'] ?? null), $int($row['position'] ?? 0)];
        }

        $tags = [];
        foreach ($list('dish_tags') as $row) {
            $dish = $int($row['dish_id'] ?? null, 1);
            $tag = $row['tag'] ?? null;
            (isset($dishes[$dish]) && in_array($tag, Tags::ALL, true)) || $fail();
            $tags[$dish . ':' . $tag] = [$dish, $tag];
        }

        $stats = array_key_exists('visit_counts', $data);
        $visits = [];
        $dishViews = [];
        if ($stats) {
            foreach ($list('visit_counts') as $row) {
                $source = $row['source'] ?? null;
                in_array($source, Stats::SOURCES, true) || $fail();
                $visits[$day($row['day'] ?? null) . ':' . $source] = [$row['day'], $source, $int($row['views'] ?? null, 0)];
            }
            foreach (is_array($data['dish_view_counts'] ?? null) ? $data['dish_view_counts'] : [] as $row) {
                $dish = $int($row['dish_id'] ?? null, 1);
                if (isset($dishes[$dish])) {
                    $dishViews[$day($row['day'] ?? null) . ':' . $dish] = [$row['day'], $dish, $int($row['views'] ?? null, 0)];
                }
            }
        }

        $images = array_values(array_unique(array_filter([
            ...array_column($dishes, 'photo'), $settings['logo'] ?? null, $settings['cover'] ?? null,
        ])));

        return [
            'settings' => $settings,
            'categories' => array_values($categories),
            'dishes' => array_values($dishes),
            'dish_sizes' => $sizes,
            'dish_tags' => array_values($tags),
            'stats' => $stats,
            'visit_counts' => array_values($visits),
            'dish_view_counts' => array_values($dishViews),
            'images' => $images,
        ];
    }

    /**
     * Writes the used images to the uploads folder, after checking each one is a real WebP.
     *
     * @return string[] files that did not exist before (removed again if the import fails)
     */
    private static function extractMedia(ZipArchive $zip, array $media, array $images): array
    {
        $written = [];
        try {
            foreach ($images as $name) {
                foreach (['', '-thumb'] as $suffix) {
                    if (!isset($media[$name . $suffix])) {
                        continue;
                    }
                    $content = $zip->getFromIndex($media[$name . $suffix]);
                    $info = is_string($content) ? @getimagesizefromstring($content) : false;
                    if ($info === false || $info['mime'] !== 'image/webp' || strlen($content) > self::MAX_FILE_BYTES) {
                        throw new BackupException(t('backup.error.bad_image'));
                    }
                    $file = Config::dataDir() . '/uploads/' . $name . $suffix . '.webp';
                    if (!is_file($file)) {
                        file_put_contents($file, $content);
                        $written[] = $file;
                    }
                }
            }
        } catch (\Throwable $exception) {
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $exception;
        }
        return $written;
    }

    private static function replaceRows(PDO $pdo, array $clean): void
    {
        // Children first: dishes reference categories, and sizes, tags and views reference dishes.
        foreach (['dish_view_counts', 'dish_tags', 'dish_sizes', 'dishes', 'categories'] as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
        if ($clean['stats']) {
            $pdo->exec('DELETE FROM visit_counts');
        }

        $insert = static function (string $sql, array $rows) use ($pdo): void {
            $statement = $pdo->prepare($sql);
            foreach ($rows as $row) {
                $statement->execute(array_values($row));
            }
        };
        $insert('INSERT INTO categories (id, name, description, position, is_visible) VALUES (?, ?, ?, ?, ?)', $clean['categories']);
        $insert(
            'INSERT INTO dishes (id, category_id, name, description, price, photo, is_available, is_visible, is_featured, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $clean['dishes']
        );
        $insert('INSERT INTO dish_sizes (dish_id, name, price, position) VALUES (?, ?, ?, ?)', $clean['dish_sizes']);
        $insert('INSERT INTO dish_tags (dish_id, tag) VALUES (?, ?)', $clean['dish_tags']);
        if ($clean['stats']) {
            $insert('INSERT INTO visit_counts (day, source, views) VALUES (?, ?, ?)', $clean['visit_counts']);
            $insert('INSERT INTO dish_view_counts (day, dish_id, views) VALUES (?, ?, ?)', $clean['dish_view_counts']);
        }
    }

    /** Deletes photos that no dish, logo or cover uses anymore. */
    private static function removeUnusedMedia(array $images): void
    {
        $used = array_flip($images);
        foreach (glob(Config::dataDir() . '/uploads/*.webp') ?: [] as $file) {
            $name = preg_replace('/(-thumb)?\.webp$/', '', basename($file));
            if (!isset($used[$name])) {
                @unlink($file);
            }
        }
    }
}
