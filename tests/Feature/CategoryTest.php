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
        // Arrange: 認証ユーザーとカテゴリを作成
        $user = User::factory()->create();
        Category::create([
            'name' => '食費',
            'type' => 'expense',
        ]);

        // Act: カテゴリ一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('categories.index'));

        // Assert: Inertiaページが正しいデータと共に返される
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
        // Arrange: 認証なしの状態

        // Act: カテゴリ一覧ページにアクセス
        $response = $this->get(route('categories.index'));

        // Assert: ログインページへリダイレクトされる
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function カテゴリをモデルで作成できる(): void
    {
        // Arrange: カテゴリデータを準備
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act: Eloquentモデルでカテゴリを作成
        Category::create($data);

        // Assert: データベースにカテゴリが保存されている
        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを作成できる(): void
    {
        // Arrange: 認証ユーザーとカテゴリデータを準備
        $user = User::factory()->create();
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act: カテゴリ作成エンドポイントにPOST
        $response = $this->actingAs($user)
            ->post(route('categories.store'), $data);

        // Assert: 一覧にリダイレクトされ、データベースに保存される
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => '食費',
            'type' => 'expense',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを作成できない(): void
    {
        // Arrange: カテゴリデータを準備（認証なし）
        $data = [
            'name' => '食費',
            'type' => 'expense',
        ];

        // Act: 認証なしでカテゴリ作成を試みる
        $response = $this->post(route('categories.store'), $data);

        // Assert: ログインページへリダイレクトされ、データは保存されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('categories', ['name' => '食費']);
    }

    #[Test]
    public function カテゴリ作成時に名前は必須(): void
    {
        // Arrange: 名前が空のカテゴリデータを準備
        $user = User::factory()->create();
        $data = [
            'name' => '',
            'type' => 'expense',
        ];

        // Act: バリデーションエラーとなるデータでPOST
        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        // Assert: nameフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function カテゴリ作成時に種別は必須(): void
    {
        // Arrange: 種別が空のカテゴリデータを準備
        $user = User::factory()->create();
        $data = [
            'name' => '食費',
            'type' => '',
        ];

        // Act: バリデーションエラーとなるデータでPOST
        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        // Assert: typeフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを更新できる(): void
    {
        // Arrange: 既存カテゴリと更新データを準備
        $user     = User::factory()->create();
        $category = Category::create(['name' => '食費', 'type' => 'expense']);
        $data     = [
            'name' => '外食費',
            'type' => 'expense',
        ];

        // Act: カテゴリ更新エンドポイントにPUT
        $response = $this->actingAs($user)
            ->put(route('categories.update', $category), $data);

        // Assert: 一覧にリダイレクトされ、データベースが更新される
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id'   => $category->id,
            'name' => '外食費',
        ]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを更新できない(): void
    {
        // Arrange: 既存カテゴリと更新データを準備（認証なし）
        $category = Category::create(['name' => '食費', 'type' => 'expense']);
        $data     = [
            'name' => '外食費',
            'type' => 'expense',
        ];

        // Act: 認証なしでカテゴリ更新を試みる
        $response = $this->put(route('categories.update', $category), $data);

        // Assert: ログインページへリダイレクトされ、データは更新されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['name' => '食費']);
    }

    #[Test]
    public function 認証済みユーザーはカテゴリを削除できる(): void
    {
        // Arrange: 削除対象のカテゴリを作成
        $user     = User::factory()->create();
        $category = Category::create(['name' => '食費', 'type' => 'expense']);

        // Act: カテゴリ削除エンドポイントにDELETE
        $response = $this->actingAs($user)
            ->delete(route('categories.destroy', $category));

        // Assert: 一覧にリダイレクトされ、データベースから削除される
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function 未認証ユーザーはカテゴリを削除できない(): void
    {
        // Arrange: 削除対象のカテゴリを作成（認証なし）
        $category = Category::create(['name' => '食費', 'type' => 'expense']);

        // Act: 認証なしでカテゴリ削除を試みる
        $response = $this->delete(route('categories.destroy', $category));

        // Assert: ログインページへリダイレクトされ、データは削除されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function カテゴリがない場合も正常に表示される(): void
    {
        // Arrange: カテゴリなしの状態で認証ユーザーを作成
        $user = User::factory()->create();

        // Act: カテゴリ一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('categories.index'));

        // Assert: 空のカテゴリ配列を含むページが返される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 0)
        );
    }
}
