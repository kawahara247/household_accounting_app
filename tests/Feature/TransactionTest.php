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
}
