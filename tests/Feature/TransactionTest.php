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
        // Arrange: 取引に必要なカテゴリを作成
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: Eloquentモデルで取引を作成
        $transaction = Transaction::create([
            'date'        => '2026-01-04',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);

        // Assert: データベースに取引が保存され、日付も正しくキャストされる
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
        // Arrange: カテゴリと取引を作成
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

        // Act: リレーション経由でカテゴリを取得
        $relatedCategory = $transaction->category;

        // Assert: 正しいカテゴリインスタンスが取得できる
        $this->assertInstanceOf(Category::class, $relatedCategory);
        $this->assertSame($category->id, $relatedCategory->id);
    }

    #[Test]
    public function 認証済みユーザーは取引一覧を取得できる(): void
    {
        // Arrange: 認証ユーザー、カテゴリ、取引を作成
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

        // Act: 取引一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('transactions.index'));

        // Assert: 取引・カテゴリ・支払元の情報を含むページが返される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->has(
                    'transactions.0',
                    fn(Assert $transaction) => $transaction
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
                    fn(Assert $cat) => $cat
                        ->where('name', '食費')
                        ->where('type', 'expense')
                        ->etc()
                )
                ->has('payers', 2)
                ->has(
                    'payers.0',
                    fn(Assert $payer) => $payer
                        ->has('value')
                        ->has('label')
                )
        );
    }

    #[Test]
    public function 未認証ユーザーは取引一覧にアクセスできない(): void
    {
        // Arrange: 認証なしの状態

        // Act: 取引一覧ページにアクセス
        $response = $this->get(route('transactions.index'));

        // Assert: ログインページへリダイレクトされる
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーは取引を作成できる(): void
    {
        // Arrange: 認証ユーザーとカテゴリを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 取引作成エンドポイントにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            'memo'        => '夕食代',
        ]);

        // Assert: 一覧にリダイレクトされ、データベースに保存される
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
        // Arrange: 認証ユーザーとカテゴリを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: _redirectパラメータでダッシュボードを指定してPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            '_redirect'   => 'dashboard',
        ]);

        // Assert: 指定先（ダッシュボード）にリダイレクトされ、データは保存される
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を作成できない(): void
    {
        // Arrange: カテゴリを準備（認証なし）
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 認証なしで取引作成を試みる
        $response = $this->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert: ログインページへリダイレクトされ、データは保存されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 取引作成時に日付は必須(): void
    {
        // Arrange: 日付なしの取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 日付を指定せずにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert: dateフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('date');
    }

    #[Test]
    public function 取引作成時に種別は必須(): void
    {
        // Arrange: 種別なしの取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 種別を指定せずにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        // Assert: typeフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 取引作成時にカテゴリは必須(): void
    {
        // Arrange: カテゴリIDなしの取引データを準備
        $user = User::factory()->create();

        // Act: カテゴリを指定せずにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'   => '2026-01-04',
            'type'   => 'expense',
            'payer'  => 'person_a',
            'amount' => 1500,
        ]);

        // Assert: category_idフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 取引作成時に支払元は必須(): void
    {
        // Arrange: 支払元なしの取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 支払元を指定せずにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'amount'      => 1500,
        ]);

        // Assert: payerフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('payer');
    }

    #[Test]
    public function 取引作成時に金額は必須(): void
    {
        // Arrange: 金額なしの取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // Act: 金額を指定せずにPOST
        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
        ]);

        // Assert: amountフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function 認証済みユーザーは取引を更新できる(): void
    {
        // Arrange: 既存の取引と更新データを準備
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

        // Act: 取引更新エンドポイントにPUT
        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
            'memo'        => '更新後のメモ',
        ]);

        // Assert: 一覧にリダイレクトされ、データベースが更新される
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
        // Arrange: 既存の取引と更新データを準備（認証なし）
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

        // Act: 認証なしで取引更新を試みる
        $response = $this->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
        ]);

        // Assert: ログインページへリダイレクトされ、データは更新されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'amount' => 1000,
        ]);
    }

    #[Test]
    public function 認証済みユーザーは取引を削除できる(): void
    {
        // Arrange: 削除対象の取引を作成
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

        // Act: 取引削除エンドポイントにDELETE
        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        // Assert: 一覧にリダイレクトされ、データベースから削除される
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を削除できない(): void
    {
        // Arrange: 削除対象の取引を作成（認証なし）
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

        // Act: 認証なしで取引削除を試みる
        $response = $this->delete(route('transactions.destroy', $transaction));

        // Assert: ログインページへリダイレクトされ、データは削除されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);
    }

    #[Test]
    public function カテゴリで取引一覧をフィルタリングできる(): void
    {
        // Arrange: 異なるカテゴリの取引を作成
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
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.category_id', $categoryFood->id)
        );
    }

    #[Test]
    public function 支払元で取引一覧をフィルタリングできる(): void
    {
        // Arrange: 異なる支払元の取引を作成
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
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.payer', 'person_a')
        );
    }

    #[Test]
    public function 種別で取引一覧をフィルタリングできる(): void
    {
        // Arrange: 収入と支出の取引を作成
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
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.type', 'income')
        );
    }

    #[Test]
    public function メモで取引一覧をフィルタリングできる(): void
    {
        // Arrange: 異なるメモの取引を作成
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
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.memo', 'ランチ代')
        );
    }

    #[Test]
    public function 複数のフィルターを組み合わせて取引一覧をフィルタリングできる(): void
    {
        // Arrange: 複数条件のテスト用に様々な組み合わせの取引を作成
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

        // Assert: 全ての条件に合致する取引のみ取得される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
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
        // Arrange: 複数の収入・支出取引を作成（収入:80000、支出:3000）
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

        // Act: 取引一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('transactions.index'));

        // Assert: summaryに収入・支出の合計が含まれる
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 4)
                ->where('summary.income', 80000)
                ->where('summary.expense', 3000)
        );
    }

    #[Test]
    public function フィルタリング時は該当する取引のみの合計が表示される(): void
    {
        // Arrange: PersonAとPersonBの取引を作成
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
        // PersonBの収入: 30000（フィルタリングで除外される）
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

        // Assert: PersonAの取引のみの合計が表示される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 2)
                ->where('summary.income', 50000)
                ->where('summary.expense', 1000)
        );
    }

    #[Test]
    public function 取引一覧は年月フィルターパラメータを受け取る(): void
    {
        // Arrange: 認証ユーザーと複数月の取引を作成
        $user = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // 2026年1月の取引
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // 2026年2月の取引
        Transaction::create([
            'date'        => '2026-02-10',
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 2000,
        ]);

        // Act: 年月フィルターで2026年1月を指定
        $response = $this->actingAs($user)->get(route('transactions.index', ['year_month' => '2026-01']));

        // Assert: 指定月の取引のみが返される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 1000)
                ->where('filters.year_month', '2026-01')
        );
    }

    #[Test]
    public function 取引一覧はデフォルトで現在の年月でフィルターされる(): void
    {
        // Arrange: 認証ユーザーと複数月の取引を作成
        $user = User::factory()->create();
        $category = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);

        // 現在の年月の取引
        $currentYearMonth = now()->format('Y-m');
        Transaction::create([
            'date'        => now()->format('Y-m-d'),
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 3000,
        ]);

        // 過去の年月の取引
        Transaction::create([
            'date'        => now()->subMonth()->format('Y-m-d'),
            'type'        => FlowType::Expense,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 5000,
        ]);

        // Act: フィルターパラメータなしで取得
        $response = $this->actingAs($user)->get(route('transactions.index'));

        // Assert: 現在の年月の取引のみが返される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 3000)
                ->where('filters.year_month', $currentYearMonth)
        );
    }

    #[Test]
    public function 年月フィルターは他のフィルターと組み合わせられる(): void
    {
        // Arrange: 認証ユーザーと複数のカテゴリ・月の取引を作成
        $user = User::factory()->create();
        $foodCategory = Category::create([
            'name' => '食費',
            'type' => FlowType::Expense,
        ]);
        $transportCategory = Category::create([
            'name' => '交通費',
            'type' => FlowType::Expense,
        ]);

        // 2026年1月・食費
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $foodCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);

        // 2026年1月・交通費
        Transaction::create([
            'date'        => '2026-01-15',
            'type'        => FlowType::Expense,
            'category_id' => $transportCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 500,
        ]);

        // 2026年2月・食費
        Transaction::create([
            'date'        => '2026-02-10',
            'type'        => FlowType::Expense,
            'category_id' => $foodCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 2000,
        ]);

        // Act: 年月と カテゴリの両方でフィルター
        $response = $this->actingAs($user)->get(route('transactions.index', [
            'year_month'  => '2026-01',
            'category_id' => $foodCategory->id,
        ]));

        // Assert: 指定月かつ指定カテゴリの取引のみが返される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->where('transactions.0.amount', 1000)
                ->where('transactions.0.category_id', $foodCategory->id)
                ->where('filters.year_month', '2026-01')
                ->where('filters.category_id', $foodCategory->id)
        );
    }
}
