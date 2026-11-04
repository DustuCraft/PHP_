<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Enums\Category;

/**
 * Каталог доставки: фастфуд, комбо и напитки.
 *
 * В реальном проекте данные пришли бы из БД, здесь — статический справочник,
 * чтобы пример оставался воспроизводимым.
 */
final class Menu
{
    /** @var list<Product>|null */
    private static ?array $products = null;

    /** @return list<Product> */
    public function all(): array
    {
        return self::products();
    }

    public function find(string $id): ?Product
    {
        foreach ($this->all() as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    /** @return list<Product> */
    public function byCategory(Category $category): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (Product $product): bool => $product->category === $category
        ));
    }

    /**
     * Меню, сгруппированное по категориям в порядке отображения.
     *
     * @return array<string, list<Product>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach (Category::cases() as $category) {
            $groups[$category->value] = $this->byCategory($category);
        }

        return $groups;
    }

    /** @return list<Product> */
    private static function products(): array
    {
        return self::$products ??= [
            new Product('burger-cheese', 'Чизбургер', 1690, Category::FastFood, 'Говядина, чеддер, соус, булочка бриошь'),
            new Product('burger-double', 'Дабл бургер', 2290, Category::FastFood, 'Две котлеты, бекон, сыр'),
            new Product('fries', 'Картофель фри', 790, Category::FastFood, 'Хрустящая соломка, 120 г'),
            new Product('nuggets', 'Наггетсы, 6 шт.', 1290, Category::FastFood, 'Курица, панировка, соус на выбор'),
            new Product('hotdog', 'Хот-дог', 990, Category::FastFood, 'Сосиска гриль, горчица, лук'),

            new Product('combo-classic', 'Комбо «Классик»', 2490, Category::Combo, 'Чизбургер + фри + кола 0,5'),
            new Product('combo-chicken', 'Комбо «Чикен»', 2690, Category::Combo, 'Наггетсы + фри + лимонад'),
            new Product('combo-family', 'Семейное комбо', 4990, Category::Combo, '2 бургера + 2 фри + 2 напитка'),
            new Product('combo-breakfast', 'Комбо «Завтрак»', 1990, Category::Combo, 'Сэндвич + кофе + круассан'),

            new Product('cola', 'Кола, 0,5 л', 490, Category::Drink, 'Классическая, со льдом'),
            new Product('ayran', 'Айран, 0,3 л', 590, Category::Drink, 'Кисломолочный, охлаждённый'),
            new Product('coffee', 'Кофе американо', 690, Category::Drink, 'Зерновой, 250 мл'),
            new Product('lemonade', 'Лимонад, 0,4 л', 790, Category::Drink, 'Цитрус-мята'),
            new Product('water', 'Вода, 0,5 л', 350, Category::Drink, 'Негазированная'),
        ];
    }
}
