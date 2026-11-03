<?php

declare(strict_types=1);

namespace MenuQr\Controllers\Admin;

use MenuQr\Http;
use MenuQr\ImageException;
use MenuQr\Images;
use MenuQr\Models\Categories;
use MenuQr\Models\Dishes;
use MenuQr\Money;
use MenuQr\Router;
use MenuQr\Session;
use MenuQr\Tags;

final class DishesController extends AdminController
{
    private const LIST = '/admin/dishes';
    private const MAX_SIZES = 10;

    public static function index(): void
    {
        self::admin();
        $categories = Categories::all();
        $filter = (int) ($_GET['category'] ?? 0);
        if ($filter > 0 && !in_array($filter, array_map('intval', array_column($categories, 'id')), true)) {
            $filter = 0;
        }
        self::render('dishes/index', 'dishes', [
            'title' => t('nav.dishes'),
            'categories' => $categories,
            'filter' => $filter,
            'dishes' => Dishes::all($filter ?: null),
        ]);
    }

    public static function create(): void
    {
        self::admin();
        $categories = Categories::all();
        if ($categories === []) {
            self::back('/admin/categories', t('dishes.error.no_categories'), 'error');
        }
        $categoryId = (int) ($_GET['category'] ?? $categories[0]['id']);
        self::form(null, [
            'category_id' => $categoryId,
            'name' => '',
            'description' => '',
            'price' => '',
            'is_available' => true,
            'is_visible' => true,
            'is_featured' => false,
            'photo' => null,
            'sizes' => [],
            'tags' => [],
        ], [], $categories);
    }

    public static function store(): void
    {
        self::guardPost(self::LIST);
        self::save(null);
    }

    public static function edit(int $id): void
    {
        self::admin();
        $dish = Dishes::find($id);
        if ($dish === null) {
            Router::notFound();
            return;
        }
        // A dish with sizes is stored with base price 0: show the field empty, as it was typed.
        $dish['price'] = $dish['sizes'] !== [] && (float) $dish['price'] === 0.0 ? '' : Money::input($dish['price']);
        $dish['sizes'] = array_map(
            static fn (array $size): array => ['name' => $size['name'], 'price' => Money::input($size['price'])],
            $dish['sizes']
        );
        self::form($dish, $dish, [], Categories::all());
    }

    public static function update(int $id): void
    {
        self::guardPost(self::LIST);
        if (Dishes::find($id) === null) {
            Router::notFound();
            return;
        }
        self::save($id);
    }

    public static function move(int $id): void
    {
        self::guardPost(self::LIST);
        Dishes::move($id, Http::input('direction') === 'up' ? 'up' : 'down');
        $category = (int) Http::input('category');
        Http::redirect(self::LIST . ($category > 0 ? '?category=' . $category : '') . '#dish-' . $id);
    }

    public static function delete(int $id): void
    {
        self::guardPost(self::LIST);
        $dish = Dishes::find($id);
        Dishes::delete($id);
        self::back(self::LIST, t('dishes.deleted', ['name' => $dish['name'] ?? '']));
    }

    private static function save(?int $id): void
    {
        $current = $id === null ? null : Dishes::find($id);
        $categories = Categories::all();
        [$values, $sizes, $errors] = self::read($categories);
        $values['photo'] = $current['photo'] ?? null;
        $newPhoto = null;

        // The photo is processed last, so a typo in another field does not leave an orphan file.
        if ($errors === []) {
            try {
                $newPhoto = Images::store($_FILES['photo'] ?? null, 'dish');
            } catch (ImageException $exception) {
                $errors['photo'] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            self::form($current, $values + ['sizes' => $sizes], $errors, $categories);
            return;
        }

        $oldPhoto = $current['photo'] ?? null;
        $photo = $oldPhoto;
        if ($newPhoto !== null) {
            $photo = $newPhoto;
        } elseif (Http::input('remove_photo') === '1') {
            $photo = null;
        }

        $parsedSizes = array_map(
            static fn (array $size): array => ['name' => $size['name'], 'price' => (string) Money::parse($size['price'])],
            $sizes
        );
        $price = $values['price'] === '' ? '0.00' : (string) Money::parse($values['price']);

        try {
            $savedId = Dishes::save($id, [
                'category_id' => $values['category_id'],
                'name' => $values['name'],
                'description' => $values['description'] === '' ? null : $values['description'],
                'price' => $price,
                'is_available' => $values['is_available'],
                'is_visible' => $values['is_visible'],
                'is_featured' => $values['is_featured'],
            ], $parsedSizes, $values['tags'], $photo);
        } catch (\Throwable $exception) {
            if ($newPhoto !== null) {
                Images::delete($newPhoto);
            }
            throw $exception;
        }

        if ($oldPhoto !== null && $oldPhoto !== $photo) {
            Images::delete($oldPhoto);
        }

        Session::flash('success', t('common.saved'));
        $next = Http::input('next') === 'new'
            ? '/admin/dishes/new?category=' . $values['category_id']
            : self::LIST . '?category=' . $values['category_id'] . '#dish-' . $savedId;
        Http::redirect($next);
    }

    /** @return array{0: array<string, mixed>, 1: array<int, array{name: string, price: string}>, 2: array<string, string>} */
    private static function read(array $categories): array
    {
        $values = [
            'category_id' => (int) Http::input('category_id'),
            'name' => Http::input('name'),
            'description' => Http::input('description'),
            'price' => Http::input('price'),
            'is_available' => Http::input('is_available') === '1',
            'is_visible' => Http::input('is_visible') === '1',
            'is_featured' => Http::input('is_featured') === '1',
            'tags' => Tags::fromInput($_POST['tags'] ?? []),
        ];
        $errors = [];

        if (!in_array($values['category_id'], array_map('intval', array_column($categories, 'id')), true)) {
            $errors['category_id'] = t('dishes.error.category');
        }
        if (mb_strlen($values['name']) < 1 || mb_strlen($values['name']) > 150) {
            $errors['name'] = t('dishes.error.name');
        }
        if (mb_strlen($values['description']) > 1000) {
            $errors['description'] = t('dishes.error.description');
        }

        $sizes = [];
        $names = is_array($_POST['size_name'] ?? null) ? $_POST['size_name'] : [];
        $prices = is_array($_POST['size_price'] ?? null) ? $_POST['size_price'] : [];
        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            $sizePrice = trim((string) ($prices[$index] ?? ''));
            if ($name === '' && $sizePrice === '') {
                continue;
            }
            $sizes[] = ['name' => $name, 'price' => $sizePrice];
            if ($name === '' || mb_strlen($name) > 60 || Money::parse($sizePrice) === null) {
                $errors['sizes'] = t('dishes.error.sizes');
            }
        }
        if (count($sizes) > self::MAX_SIZES) {
            $errors['sizes'] = t('dishes.error.too_many_sizes', ['max' => self::MAX_SIZES]);
        }

        // With sizes, each size has its own price and the base price is optional.
        if ($values['price'] === '' ? $sizes === [] : Money::parse($values['price']) === null) {
            $errors['price'] = t('dishes.error.price');
        }

        return [$values, $sizes, $errors];
    }

    private static function form(?array $dish, array $values, array $errors, array $categories): void
    {
        self::render('dishes/form', 'dishes', [
            'title' => $dish === null ? t('dishes.new') : t('dishes.edit'),
            'dish' => $dish,
            'values' => $values,
            'errors' => $errors,
            'categories' => $categories,
        ]);
    }
}
