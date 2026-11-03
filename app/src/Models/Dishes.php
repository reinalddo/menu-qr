<?php

declare(strict_types=1);

namespace MenuQr\Models;

use MenuQr\Db;
use MenuQr\Images;

final class Dishes
{
    /** @return array<int, array<string, mixed>> dishes with their category name, in menu order */
    public static function all(?int $categoryId = null): array
    {
        $sql = 'SELECT d.*, c.name AS category_name,
                       (SELECT MIN(s.price) FROM dish_sizes s WHERE s.dish_id = d.id) AS min_size_price
                FROM dishes d JOIN categories c ON c.id = d.category_id'
            . ($categoryId ? ' WHERE d.category_id = ?' : '')
            . ' ORDER BY c.position, c.id, d.position, d.id';
        $statement = Db::pdo()->prepare($sql);
        $statement->execute($categoryId ? [$categoryId] : []);
        return $statement->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = Db::pdo()->prepare('SELECT * FROM dishes WHERE id = ?');
        $statement->execute([$id]);
        $dish = $statement->fetch();
        if (!$dish) {
            return null;
        }

        $sizes = Db::pdo()->prepare('SELECT name, price FROM dish_sizes WHERE dish_id = ? ORDER BY position, id');
        $sizes->execute([$id]);
        $dish['sizes'] = $sizes->fetchAll();

        $tags = Db::pdo()->prepare('SELECT tag FROM dish_tags WHERE dish_id = ?');
        $tags->execute([$id]);
        $dish['tags'] = $tags->fetchAll(\PDO::FETCH_COLUMN);
        return $dish;
    }

    /**
     * @param array{category_id: int, name: string, description: ?string, price: string,
     *              is_available: bool, is_visible: bool, is_featured: bool} $data
     * @param array<int, array{name: string, price: string}> $sizes
     * @param string[] $tags
     */
    public static function save(?int $id, array $data, array $sizes, array $tags, ?string $photo): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $values = [
                $data['category_id'], $data['name'], $data['description'], $data['price'],
                (int) $data['is_available'], (int) $data['is_visible'], (int) $data['is_featured'], $photo,
            ];
            if ($id === null) {
                $position = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM dishes WHERE category_id = ?');
                $position->execute([$data['category_id']]);
                $pdo->prepare(
                    'INSERT INTO dishes (category_id, name, description, price, is_available, is_visible, is_featured, photo, position)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([...$values, (int) $position->fetchColumn()]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $current = self::find($id);
                if ($current !== null && (int) $current['category_id'] !== $data['category_id']) {
                    // Moved to another category: it goes to the end of that list.
                    $position = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM dishes WHERE category_id = ?');
                    $position->execute([$data['category_id']]);
                    $pdo->prepare('UPDATE dishes SET position = ? WHERE id = ?')->execute([(int) $position->fetchColumn(), $id]);
                }
                $pdo->prepare(
                    'UPDATE dishes SET category_id = ?, name = ?, description = ?, price = ?, is_available = ?,
                     is_visible = ?, is_featured = ?, photo = ? WHERE id = ?'
                )->execute([...$values, $id]);
            }

            $pdo->prepare('DELETE FROM dish_sizes WHERE dish_id = ?')->execute([$id]);
            $insertSize = $pdo->prepare('INSERT INTO dish_sizes (dish_id, name, price, position) VALUES (?, ?, ?, ?)');
            foreach (array_values($sizes) as $index => $size) {
                $insertSize->execute([$id, $size['name'], $size['price'], $index + 1]);
            }

            $pdo->prepare('DELETE FROM dish_tags WHERE dish_id = ?')->execute([$id]);
            $insertTag = $pdo->prepare('INSERT INTO dish_tags (dish_id, tag) VALUES (?, ?)');
            foreach ($tags as $tag) {
                $insertTag->execute([$id, $tag]);
            }

            $pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function setAvailable(int $id, bool $available): bool
    {
        $statement = Db::pdo()->prepare('UPDATE dishes SET is_available = ? WHERE id = ?');
        $statement->execute([(int) $available, $id]);
        return self::find($id) !== null;
    }

    public static function delete(int $id): void
    {
        $dish = self::find($id);
        if ($dish === null) {
            return;
        }
        Db::pdo()->prepare('DELETE FROM dishes WHERE id = ?')->execute([$id]);
        Images::delete($dish['photo']);
    }

    public static function move(int $id, string $direction): void
    {
        Positions::swap('dishes', $id, $direction, 'category_id');
    }

    /** @return array{categories: int, dishes: int, sold_out: int, hidden: int} */
    public static function counts(): array
    {
        $row = Db::pdo()->query(
            'SELECT (SELECT COUNT(*) FROM categories) AS categories,
                    COUNT(*) AS dishes,
                    COALESCE(SUM(is_available = 0), 0) AS sold_out,
                    COALESCE(SUM(is_visible = 0), 0) AS hidden
             FROM dishes'
        )->fetch();
        return array_map('intval', $row);
    }
}
