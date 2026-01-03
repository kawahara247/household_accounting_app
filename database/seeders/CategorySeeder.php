<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 支出カテゴリ
            ['name' => '食費', 'type' => CategoryType::Expense],
            ['name' => 'ありさ出費', 'type' => CategoryType::Expense],
            ['name' => 'コウちゃん出費', 'type' => CategoryType::Expense],
            ['name' => 'デート費', 'type' => CategoryType::Expense],
            ['name' => '光熱費', 'type' => CategoryType::Expense],
            ['name' => '保険', 'type' => CategoryType::Expense],
            ['name' => '家賃', 'type' => CategoryType::Expense],
            ['name' => '衛生用品', 'type' => CategoryType::Expense],
            ['name' => '雑貨', 'type' => CategoryType::Expense],
            ['name' => '特別な出費', 'type' => CategoryType::Expense],
            // 収入カテゴリ
            ['name' => 'ありさ給与', 'type' => CategoryType::Income],
            ['name' => 'コウちゃん給与', 'type' => CategoryType::Income],
            ['name' => 'ありさ賞与', 'type' => CategoryType::Income],
            ['name' => 'コウちゃん賞与', 'type' => CategoryType::Income],
            ['name' => 'その他収入', 'type' => CategoryType::Income],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
