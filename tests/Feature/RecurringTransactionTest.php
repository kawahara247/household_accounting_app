<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 認証済みユーザーは定期取引一覧を取得できる(): void
    {
        // Arrange: 認証ユーザー、カテゴリ、定期取引を作成
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'memo'         => '毎月の家賃',
        ]);

        // Act: 定期取引一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('recurring-transactions.index'));

        // Assert: 定期取引・カテゴリ・支払元の情報を含むページが返される
        $response->assertOk();
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('RecurringTransactions/Index')
                ->has('recurringTransactions', 1)
                ->has(
                    'recurringTransactions.0',
                    fn(Assert $recurring) => $recurring
                        ->has('id')
                        ->where('name', '家賃')
                        ->where('day_of_month', 25)
                        ->where('type', 'expense')
                        ->where('payer', 'person_a')
                        ->where('amount', 80000)
                        ->where('memo', '毎月の家賃')
                        ->has('category')
                        ->etc()
                )
                ->has('categories')
                ->has('payers', 2)
        );
    }

    #[Test]
    public function 未認証ユーザーは定期取引一覧にアクセスできない(): void
    {
        // Arrange: 認証なしの状態

        // Act: 定期取引一覧ページにアクセス
        $response = $this->get(route('recurring-transactions.index'));

        // Assert: ログインページへリダイレクトされる
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーは定期取引を作成できる(): void
    {
        // Arrange: 認証ユーザーとカテゴリを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 定期取引作成エンドポイントにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
            'memo'         => '毎月の家賃',
        ]);

        // Assert: 一覧にリダイレクトされ、データベースに保存される
        $response->assertRedirect(route('recurring-transactions.index'));
        $this->assertDatabaseHas('recurring_transactions', [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
            'memo'         => '毎月の家賃',
        ]);
    }

    #[Test]
    public function 未認証ユーザーは定期取引を作成できない(): void
    {
        // Arrange: カテゴリを準備（認証なし）
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 認証なしで定期取引作成を試みる
        $response = $this->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        // Assert: ログインページへリダイレクトされ、データは保存されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('recurring_transactions', [
            'name' => '家賃',
        ]);
    }

    #[Test]
    public function 定期取引作成時に名前は必須(): void
    {
        // Arrange: 名前なしの定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 名前を指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        // Assert: nameフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function 定期取引作成時に登録日は必須(): void
    {
        // Arrange: 登録日なしの定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 登録日を指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'        => '家賃',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 80000,
        ]);

        // Assert: day_of_monthフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('day_of_month');
    }

    #[Test]
    public function 定期取引作成時に登録日は1から28の範囲(): void
    {
        // Arrange: 範囲外の登録日の定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 29日を指定してPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 29,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        // Assert: day_of_monthフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('day_of_month');
    }

    #[Test]
    public function 定期取引作成時に種別は必須(): void
    {
        // Arrange: 種別なしの定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 種別を指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        // Assert: typeフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 定期取引作成時にカテゴリは必須(): void
    {
        // Arrange: カテゴリIDなしの定期取引データを準備
        $user = User::factory()->create();

        // Act: カテゴリを指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        // Assert: category_idフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 定期取引作成時に支払元は必須(): void
    {
        // Arrange: 支払元なしの定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 支払元を指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'amount'       => 80000,
        ]);

        // Assert: payerフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('payer');
    }

    #[Test]
    public function 定期取引作成時に金額は必須(): void
    {
        // Arrange: 金額なしの定期取引データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);

        // Act: 金額を指定せずにPOST
        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
        ]);

        // Assert: amountフィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    public function 認証済みユーザーは定期取引を更新できる(): void
    {
        // Arrange: 既存の定期取引と更新データを準備
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'memo'         => '元のメモ',
        ]);

        // Act: 定期取引更新エンドポイントにPUT
        $response = $this->actingAs($user)->put(route('recurring-transactions.update', $recurringTransaction), [
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_b',
            'amount'       => 85000,
            'memo'         => '更新後のメモ',
        ]);

        // Assert: 一覧にリダイレクトされ、データベースが更新される
        $response->assertRedirect(route('recurring-transactions.index'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id'           => $recurringTransaction->id,
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'payer'        => 'person_b',
            'amount'       => 85000,
            'memo'         => '更新後のメモ',
        ]);
    }

    #[Test]
    public function 未認証ユーザーは定期取引を更新できない(): void
    {
        // Arrange: 既存の定期取引と更新データを準備（認証なし）
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
        ]);

        // Act: 認証なしで定期取引更新を試みる
        $response = $this->put(route('recurring-transactions.update', $recurringTransaction), [
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_b',
            'amount'       => 85000,
        ]);

        // Assert: ログインページへリダイレクトされ、データは更新されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id'     => $recurringTransaction->id,
            'name'   => '家賃',
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function 認証済みユーザーは定期取引を削除できる(): void
    {
        // Arrange: 削除対象の定期取引を作成
        $user     = User::factory()->create();
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
        ]);

        // Act: 定期取引削除エンドポイントにDELETE
        $response = $this->actingAs($user)->delete(route('recurring-transactions.destroy', $recurringTransaction));

        // Assert: 一覧にリダイレクトされ、データベースから削除される
        $response->assertRedirect(route('recurring-transactions.index'));
        $this->assertDatabaseMissing('recurring_transactions', [
            'id' => $recurringTransaction->id,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは定期取引を削除できない(): void
    {
        // Arrange: 削除対象の定期取引を作成（認証なし）
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
        ]);

        // Act: 認証なしで定期取引削除を試みる
        $response = $this->delete(route('recurring-transactions.destroy', $recurringTransaction));

        // Assert: ログインページへリダイレクトされ、データは削除されない
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurringTransaction->id,
        ]);
    }
}
