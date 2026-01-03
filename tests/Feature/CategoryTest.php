<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_カテゴリを作成できる(): void
    {
        // Arrange
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act
        $category = Category::create($data);

        // Assert
        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }
}
