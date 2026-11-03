<?php

declare(strict_types=1);

namespace MenuQr\Models;

use MenuQr\Db;

final class Categories
{
    /** @return array<int, array<string, mixed>> with dish_count */
    public static function all(): array
    {
        return Db::pdo()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM dishes d WHERE d.category_id = c.id) AS dish_count
             FROM categories c ORDER BY c.position, c.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $statement = Db::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public static function create(string $name, ?string $description): int
    {
        $pdo = Db::pdo();
        $position = (int) $pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM categories')->fetchColumn();
        $pdo->prepare('INSERT INTO categories (name, description, position) VALUES (?, ?, ?)')
            ->execute([$name, $description, $position]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description, bool $visible): void
    {
        Db::pdo()->prepare('UPDATE categories SET name = ?, description = ?, is_visible = ? WHERE id = ?')
            ->execute([$name, $description, (int) $visible, $id]);
    }

    public static function setVisible(int $id, bool $visible): void
    {
        Db::pdo()->prepare('UPDATE categories SET is_visible = ? WHERE id = ?')->execute([(int) $visible, $id]);
    }

    /** @return bool false when it still has dishes */
    public static function delete(int $id): bool
    {
        $count = Db::pdo()->prepare('SELECT COUNT(*) FROM dishes WHERE category_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            return false;
        }
        Db::pdo()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        return true;
    }

    public static function move(int $id, string $direction): void
    {
        Positions::swap('categories', $id, $direction);
    }
}
