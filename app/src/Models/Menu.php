<?php

declare(strict_types=1);

namespace MenuQr\Models;

use MenuQr\Db;

/** Everything the public menu shows, loaded with four queries. */
final class Menu
{
    /**
     * @return array{categories: array<int, array<string, mixed>>, featured: array<int, array<string, mixed>>}
     *         Only visible categories and dishes; categories without visible dishes are left out.
     */
    public static function load(): array
    {
        $pdo = Db::pdo();
        $categories = $pdo->query(
            'SELECT id, name, description FROM categories WHERE is_visible = 1 ORDER BY position, id'
        )->fetchAll();
        if ($categories === []) {
            return ['categories' => [], 'featured' => []];
        }

        $dishes = $pdo->query(
            'SELECT d.id, d.category_id, d.name, d.description, d.price, d.photo, d.is_available, d.is_featured
             FROM dishes d JOIN categories c ON c.id = d.category_id
             WHERE d.is_visible = 1 AND c.is_visible = 1
             ORDER BY d.position, d.id'
        )->fetchAll();

        $sizes = [];
        foreach ($pdo->query('SELECT dish_id, name, price FROM dish_sizes ORDER BY position, id') as $row) {
            $sizes[$row['dish_id']][] = $row;
        }
        $tags = [];
        foreach ($pdo->query('SELECT dish_id, tag FROM dish_tags') as $row) {
            $tags[$row['dish_id']][] = $row['tag'];
        }

        $byCategory = [];
        $featured = [];
        foreach ($dishes as $dish) {
            $dish['sizes'] = $sizes[$dish['id']] ?? [];
            $dish['tags'] = $tags[$dish['id']] ?? [];
            $byCategory[$dish['category_id']][] = $dish;
            if ($dish['is_featured'] && $dish['is_available']) {
                $featured[] = $dish;
            }
        }

        $result = [];
        foreach ($categories as $category) {
            if (!empty($byCategory[$category['id']])) {
                $category['dishes'] = $byCategory[$category['id']];
                $result[] = $category;
            }
        }
        return ['categories' => $result, 'featured' => $featured];
    }
}
