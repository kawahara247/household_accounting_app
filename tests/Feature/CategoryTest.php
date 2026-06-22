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
        $user = User::factory()->create();
        Category::factory()->expense()->name('食費')->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

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
                        ->etc()
                )
        );
    }

    #[Test]
    public function 未認証ユーザーはカテゴリ一覧にアクセスできない(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function カテゴリをモデルで作成できる(): void
    {
        Category::factory()->expense()->name('食費')->create();

        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => '食費',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを作成できない(): void
    {
        $response = $this->post(route('categories.store'), [
            'name' => '食費',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('categories', ['name' => '食費']);
    }

    #[Test]
    public function カテゴリ作成時に名前は必須(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => '',
            'type' => 'expense',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function カテゴリ作成時に種別は必須(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => '食費',
            'type' => '',
        ]);

        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを更新できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->name('食費')->create();

        $response = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => '外食費',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id'   => $category->id,
            'name' => '外食費',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを更新できない(): void
    {
        $category = Category::factory()->expense()->name('食費')->create();

        $response = $this->put(route('categories.update', $category), [
            'name' => '外食費',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['name' => '食費']);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを削除できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを削除できない(): void
    {
        $category = Category::factory()->expense()->create();

        $response = $this->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function カテゴリがない場合も正常に表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 0)
        );
    }
}
