<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Http;
use MenuQr\Models\Categories;
use MenuQr\Router;

final class CategoriesController extends AdminController
{
    private const LIST = '/admin/categories';

    public static function index(): void
    {
        self::admin();
        self::render('categories/index', 'categories', [
            'title' => t('nav.categories'),
            'categories' => Categories::all(),
            'values' => ['name' => '', 'description' => ''],
            'errors' => [],
        ]);
    }

    public static function store(): void
    {
        self::guardPost(self::LIST);
        [$values, $errors] = self::read();
        if ($errors !== []) {
            self::render('categories/index', 'categories', [
                'title' => t('nav.categories'),
                'categories' => Categories::all(),
                'values' => $values,
                'errors' => $errors,
            ]);
            return;
        }
        Categories::create($values['name'], $values['description'] ?: null);
        self::back(self::LIST, t('categories.created', ['name' => $values['name']]));
    }

    public static function edit(int $id): void
    {
        self::admin();
        $category = Categories::find($id);
        if ($category === null) {
            Router::notFound();
            return;
        }
        self::render('categories/edit', 'categories', [
            'title' => t('categories.edit'),
            'category' => $category,
            'values' => $category,
            'errors' => [],
        ]);
    }

    public static function update(int $id): void
    {
        self::guardPost(self::LIST);
        $category = Categories::find($id);
        if ($category === null) {
            Router::notFound();
            return;
        }
        [$values, $errors] = self::read();
        $values['is_visible'] = Http::input('is_visible') === '1';
        if ($errors !== []) {
            self::render('categories/edit', 'categories', [
                'title' => t('categories.edit'),
                'category' => $category,
                'values' => $values,
                'errors' => $errors,
            ]);
            return;
        }
        Categories::update($id, $values['name'], $values['description'] ?: null, $values['is_visible']);
        self::back(self::LIST, t('common.saved'));
    }

    public static function move(int $id): void
    {
        self::guardPost(self::LIST);
        Categories::move($id, Http::input('direction') === 'up' ? 'up' : 'down');
        Http::redirect(self::LIST . '#category-' . $id);
    }

    public static function visibility(int $id): void
    {
        self::guardPost(self::LIST);
        Categories::setVisible($id, Http::input('is_visible') === '1');
        Http::redirect(self::LIST . '#category-' . $id);
    }

    public static function delete(int $id): void
    {
        self::guardPost(self::LIST);
        if (!Categories::delete($id)) {
            self::back(self::LIST, t('categories.error.not_empty'), 'error');
        }
        self::back(self::LIST, t('categories.deleted'));
    }

    /** @return array{0: array<string, string>, 1: array<string, string>} */
    private static function read(): array
    {
        $values = ['name' => Http::input('name'), 'description' => Http::input('description')];
        $errors = [];
        if (mb_strlen($values['name']) < 1 || mb_strlen($values['name']) > 120) {
            $errors['name'] = t('categories.error.name');
        }
        if (mb_strlen($values['description']) > 500) {
            $errors['description'] = t('categories.error.description');
        }
        return [$values, $errors];
    }
}
