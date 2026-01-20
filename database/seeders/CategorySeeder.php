<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FlowType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 支出カテゴリ
            ['name' => '食費', 'type' => FlowType::Expense],
            ['name' => '個人の出費', 'type' => FlowType::Expense],
            ['name' => 'デート費', 'type' => FlowType::Expense],
            ['name' => '光熱費', 'type' => FlowType::Expense],
            ['name' => '保険', 'type' => FlowType::Expense],
            ['name' => '家賃', 'type' => FlowType::Expense],
            ['name' => '衛生用品', 'type' => FlowType::Expense],
            ['name' => '雑貨', 'type' => FlowType::Expense],
            ['name' => 'その他支出', 'type' => FlowType::Expense],
            // 収入カテゴリ
            ['name' => '給与', 'type' => FlowType::Income],
            ['name' => '賞与', 'type' => FlowType::Income],
            ['name' => 'その他収入', 'type' => FlowType::Income],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
