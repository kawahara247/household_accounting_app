<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 認証済みユーザーはカテゴリ一覧を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Category::create(['name' => '食費', 'type' => 'expense']);
        Category::create(['name' => '給与', 'type' => 'income']);

        // Act
        $response = $this->actingAs($user)->get(route('categories.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 2)
        );
    }

    #[Test]
    public function 未認証ユーザーはカテゴリ一覧にアクセスできない(): void
    {
        // Arrange
        // (認証なし)

        // Act
        $response = $this->get(route('categories.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function カテゴリを作成できる(): void
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
