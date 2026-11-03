<?php

declare(strict_types=1);

namespace MenuQr;

/**
 * Converts uploaded photos to WebP. Re-encoding through GD drops anything
 * embedded in the original file, and the server picks the name and format.
 */
final class Images
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 40_000_000;
    private const QUALITY = 80;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Longest side of each stored version, per kind of image. */
    private const SIZES = [
        'dish' => ['' => 1200, '-thumb' => 480],
        'logo' => ['' => 512],
        'cover' => ['' => 1600],
    ];

    /**
     * @param array<string, mixed>|null $upload one entry of $_FILES
     * @return string|null stored name (without extension), or null when no file was sent
     * @throws ImageException with a translated message
     */
    public static function store(?array $upload, string $kind): ?string
    {
        if ($upload === null || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new ImageException(t('image.error.too_big', ['max' => self::MAX_BYTES / 1024 / 1024]));
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) {
            throw new ImageException(t('image.error.upload'));
        }
        if ((int) $upload['size'] > self::MAX_BYTES) {
            throw new ImageException(t('image.error.too_big', ['max' => self::MAX_BYTES / 1024 / 1024]));
        }

        $path = (string) $upload['tmp_name'];
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (in_array($mime, ['image/heic', 'image/heif'], true)) {
            throw new ImageException(t('image.error.heic'));
        }
        $info = @getimagesize($path);
        if (!in_array($mime, self::ALLOWED, true) || $info === false || $info['mime'] !== $mime) {
            throw new ImageException(t('image.error.type'));
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new ImageException(t('image.error.dimensions'));
        }

        $image = self::open($path, $mime);
        $name = bin2hex(random_bytes(16));
        try {
            foreach (self::SIZES[$kind] as $suffix => $maxSide) {
                $resized = self::fit($image, $maxSide);
                $saved = imagewebp($resized, self::path($name . $suffix), self::QUALITY);
                if ($resized !== $image) {
                    imagedestroy($resized);
                }
                if (!$saved) {
                    throw new ImageException(t('image.error.upload'));
                }
            }
        } catch (ImageException $exception) {
            self::delete($name);
            throw $exception;
        } finally {
            imagedestroy($image);
        }
        return $name;
    }

    public static function delete(?string $name): void
    {
        if ($name === null || !preg_match('/^[a-f0-9]{32}$/', $name)) {
            return;
        }
        foreach (['', '-thumb'] as $suffix) {
            $file = self::path($name . $suffix);
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public static function url(?string $name, bool $thumb = false): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }
        return '/media/' . $name . ($thumb ? '-thumb' : '') . '.webp';
    }

    private static function path(string $name): string
    {
        return Config::dataDir() . '/uploads/' . $name . '.webp';
    }

    private static function open(string $path, string $mime): \GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/gif' => @imagecreatefromgif($path),
        };
        if (!$image instanceof \GdImage) {
            throw new ImageException(t('image.error.type'));
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $mime === 'image/jpeg' ? self::applyOrientation($image, $path) : $image;
    }

    /** Phone photos are often stored sideways with an EXIF flag saying how to rotate them. */
    private static function applyOrientation(\GdImage $image, string $path): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
        if ($rotated instanceof \GdImage && $rotated !== $image) {
            imagedestroy($image);
            return $rotated;
        }
        return $image;
    }

    private static function fit(\GdImage $image, int $maxSide): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));
        if ($scale >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return $resized;
    }
}
