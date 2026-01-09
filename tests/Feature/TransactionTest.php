<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function Transactionをモデルで作成できる(): void
    {
        // Arrange
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);

        // Assert
        $this->assertDatabaseHas('transactions', [
            'id'          => $transaction->id,
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);
        $this->assertSame('2026-01-04', $transaction->date->format('Y-m-d'));
    }

    #[Test]
    public function Transactionはカテゴリとのリレーションを持つ(): void
    {
        // Arrange
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // Act
        $relatedCategory = $transaction->category;

        // Assert
        $this->assertInstanceOf(Category::class, $relatedCategory);
        $this->assertSame($category->id, $relatedCategory->id);
    }

    #[Test]
    public function 認証済みユーザーは取引一覧を取得できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('transactions.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->has(
                    'transactions.0',
                    fn (Assert $transaction) => $transaction
                        ->has('id')
                        ->where('type', 'expense')
                        ->where('payer', 'person_a')
                        ->where('amount', 1000)
                        ->where('memo', 'ランチ代')
                        ->has('category')
                        ->etc()
                )
                ->has('categories', 1)
                ->has(
                    'categories.0',
                    fn (Assert $cat) => $cat
                        ->where('name', '食費')
                        ->where('type', 'expense')
                        ->etc()
                )
                ->has('payers', 2)
                ->has(
                    'payers.0',
                    fn (Assert $payer) => $payer
                        ->has('value')
                        ->has('label')
                )
        );
    }

    #[Test]
    public function 未認証ユーザーは取引一覧にアクセスできない(): void
    {
        // Act
        $response = $this->get(route('transactions.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーは取引を作成できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            'memo'        => '夕食代',
        ]);

        // Assert
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            'memo'        => '夕食代',
        ]);
    }

    #[Test]
    public function 取引作成時にリダイレクト先を指定できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            '_redirect'   => 'dashboard',
        ]);

        // Assert
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を作成できない(): void
    {
        // Arrange
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 取引作成時に日付は必須(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert
        $response->assertSessionHasErrors('date');
    }

    #[Test]
    public function 取引作成時に種別は必須(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 取引作成時にカテゴリは必須(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'   => '2026-01-04',
            'type'   => 'expense',
            'payer'  => 'person_a',
            'amount' => 1500,
        ]);

        // Assert
        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 取引作成時に支払元は必須(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'amount'      => 1500,
        ]);

        // Assert
        $response->assertSessionHasErrors('payer');
    }

    #[Test]
    public function 取引作成時に金額は必須(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
        ]);

        // Assert
        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function 認証済みユーザーは取引を更新できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => '元のメモ',
        ]);

        // Act
        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
            'memo'        => '更新後のメモ',
        ]);

        // Assert
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'payer'  => 'person_b',
            'amount' => 2000,
            'memo'   => '更新後のメモ',
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を更新できない(): void
    {
        // Arrange
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // Act
        $response = $this->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
        ]);

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'amount' => 1000,
        ]);
    }

    #[Test]
    public function 認証済みユーザーは取引を削除できる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // Act
        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        // Assert
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を削除できない(): void
    {
        // Arrange
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // Act
        $response = $this->delete(route('transactions.destroy', $transaction));

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);
    }

    #[Test]
    public function カテゴリで取引一覧をフィルタリングできる(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $categoryFood = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $categoryTransport = Category::create([
            'name' => '交通費',
            'type' => FlowType::Expense,
        ]);
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $categoryFood->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Expense,
            'category_id' => $categoryTransport->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 500,
        ]);

        // Act: 食費カテゴリでフィルタリング
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'category_id' => $categoryFood->id,
        ]));

        // Assert: 食費の取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.category_id', $categoryFood->id)
        );
    }

    #[Test]
    public function 支払元で取引一覧をフィルタリングできる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 500,
        ]);

        // Act: PersonAでフィルタリング
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'payer' => 'person_a',
        ]));

        // Assert: PersonAの取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.payer', 'person_a')
        );
    }

    #[Test]
    public function 種別で取引一覧をフィルタリングできる(): void
    {
        // Arrange
        $user            = User::factory()->create();
        $expenseCategory = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $incomeCategory = Category::create([
            'name' => '給与',
            'type' => FlowType::Income,
        ]);
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);

        // Act: 収入でフィルタリング
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'type' => 'income',
        ]));

        // Assert: 収入の取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.type', 'income')
        );
    }

    #[Test]
    public function メモで取引一覧をフィルタリングできる(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 500,
            'memo'        => '夕食代',
        ]);

        // Act: 「ランチ」でフィルタリング（部分一致）
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'memo' => 'ランチ',
        ]));

        // Assert: ランチを含む取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.memo', 'ランチ代')
        );
    }

    #[Test]
    public function 複数のフィルターを組み合わせて取引一覧をフィルタリングできる(): void
    {
        // Arrange
        $user         = User::factory()->create();
        $categoryFood = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $categoryTransport = Category::create([
            'name' => '交通費',
            'type' => FlowType::Expense,
        ]);

        // 食費 + PersonA
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $categoryFood->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);
        // 食費 + PersonB
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Expense,
            'category_id' => $categoryFood->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 800,
            'memo'        => 'ランチ代',
        ]);
        // 交通費 + PersonA
        Transaction::create([
            'date'        => '2026-01-06',
            'type'        => FlowType::Expense,
            'category_id' => $categoryTransport->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 500,
            'memo'        => '電車代',
        ]);

        // Act: 食費 + PersonA + メモ「ランチ」でフィルタリング
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'category_id' => $categoryFood->id,
            'payer'       => 'person_a',
            'memo'        => 'ランチ',
        ]));

        // Assert: 条件に合致する取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.category_id', $categoryFood->id)
                ->where('transactions.0.payer', 'person_a')
                ->where('transactions.0.memo', 'ランチ代')
        );
    }

    #[Test]
    public function 取引一覧に収入と支出の合計が表示される(): void
    {
        // Arrange
        $user            = User::factory()->create();
        $expenseCategory = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $incomeCategory = Category::create([
            'name' => '給与',
            'type' => FlowType::Income,
        ]);

        // 収入: 50000 + 30000 = 80000
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 30000,
        ]);

        // 支出: 1000 + 2000 = 3000
        Transaction::create([
            'date'        => '2026-01-06',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-01-07',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 2000,
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('transactions.index'));

        // Assert
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 4)
                ->where('summary.income', 80000)
                ->where('summary.expense', 3000)
        );
    }

    #[Test]
    public function フィルタリング時は該当する取引のみの合計が表示される(): void
    {
        // Arrange
        $user            = User::factory()->create();
        $expenseCategory = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $incomeCategory = Category::create([
            'name' => '給与',
            'type' => FlowType::Income,
        ]);

        // PersonAの収入: 50000
        Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);
        // PersonBの収入: 30000（フィルタリングで除外）
        Transaction::create([
            'date'        => '2026-01-05',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 30000,
        ]);

        // PersonAの支出: 1000
        Transaction::create([
            'date'        => '2026-01-06',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // Act: PersonAでフィルタリング
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'payer' => 'person_a',
        ]));

        // Assert: PersonAの取引のみの合計
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 2)
                ->where('summary.income', 50000)
                ->where('summary.expense', 1000)
        );
    }
}
