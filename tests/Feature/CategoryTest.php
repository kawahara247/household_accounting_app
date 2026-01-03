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
        Category::create([
            'name'  => '食費',
            'type'  => 'expense',
            'icon'  => '🍔',
            'color' => '#FF5733',
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('categories.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 1)
                ->has(
                    'categories.0',
                    fn (Assert $category) => $category
                        ->has('id')
                        ->where('name', '食費')
                        ->where('type', 'expense')
                        ->where('icon', '🍔')
                        ->where('color', '#FF5733')
                        ->etc()
                )
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
    public function カテゴリをモデルで作成できる(): void
    {
        // Arrange
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act
        Category::create($data);

        // Assert
        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを作成できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'name'  => '食費',
            'type'  => 'expense',
            'icon'  => '🍔',
            'color' => '#FF5733',
        ];

        // Act
        $response = $this->actingAs($user)
            ->post(route('categories.store'), $data);

        // Assert
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name'  => '食費',
            'type'  => 'expense',
            'icon'  => '🍔',
            'color' => '#FF5733',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを作成できない(): void
    {
        // Arrange
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act
        $response = $this->post(route('categories.store'), $data);

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('categories', ['name' => '食費']);
    }

    #[Test]
    public function カテゴリ作成時に名前は必須(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'name' => '',
            'type' => 'expense',
        ];

        // Act
        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function カテゴリ作成時に種別は必須(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'name' => '食費',
            'type' => '',
        ];

        // Act
        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        // Assert
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを更新できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create(['name' => '食費', 'type' => 'expense']);
        $data     = [
            'name'  => '外食費',
            'type'  => 'expense',
            'icon'  => '🍜',
            'color' => '#33FF57',
        ];

        // Act
        $response = $this->actingAs($user)
            ->put(route('categories.update', $category), $data);

        // Assert
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id'    => $category->id,
            'name'  => '外食費',
            'icon'  => '🍜',
            'color' => '#33FF57',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを更新できない(): void
    {
        // Arrange
        $category = Category::create(['name' => '食費', 'type' => 'expense']);
        $data     = [
            'name' => '外食費',
            'type' => 'expense',
        ];

        // Act
        $response = $this->put(route('categories.update', $category), $data);

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['name' => '食費']);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを削除できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create(['name' => '食費', 'type' => 'expense']);

        // Act
        $response = $this->actingAs($user)
            ->delete(route('categories.destroy', $category));

        // Assert
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを削除できない(): void
    {
        // Arrange
        $category = Category::create(['name' => '食費', 'type' => 'expense']);

        // Act
        $response = $this->delete(route('categories.destroy', $category));

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function カテゴリがない場合も正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('categories.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 0)
        );
    }
}
