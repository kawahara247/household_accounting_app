<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class CrudTest extends TransactionTestCase
{
    #[Test]
    public function 認証済みユーザーは取引一覧を取得できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->name('食費')->create();
        $date     = now()->format('Y-m-d');

        Transaction::factory()
            ->forCategory($category)
            ->on($date)
            ->payer(PayerType::PersonA)
            ->amount(1000)
            ->memo('ランチ代')
            ->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->has('transactions', 1)
                ->has(
                    'transactions.0',
                    fn (Assert $transaction) => $transaction
                        ->has('id')
                        ->where('date', $date)
                        ->where('type', 'expense')
                        ->where('category_id', $category->id)
                        ->where('payer', 'person_a')
                        ->where('amount', 1000)
                        ->where('memo', 'ランチ代')
                        ->has('category')
                        ->etc()
                )
        );
    }

    #[Test]
    public function 認証済みユーザーは取引を作成できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            'memo'        => '夕食代',
        ]);

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
    public function 認証済みユーザーは取引を更新できる(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()
            ->forCategory($category)
            ->on('2026-01-04')
            ->payer(PayerType::PersonA)
            ->amount(1000)
            ->memo('元のメモ')
            ->create();

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
            'memo'        => '更新後のメモ',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'date'   => '2026-01-05',
            'payer'  => 'person_b',
            'amount' => 2000,
            'memo'   => '更新後のメモ',
        ]);
    }

    #[Test]
    public function 月をまたぐ取引更新でdateが正しく書き換わる(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->on('2026-01-04')->amount(1000)->create();

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'date'        => '2026-02-15',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1000,
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id'   => $transaction->id,
            'date' => '2026-02-15',
        ]);
    }

    #[Test]
    public function 認証済みユーザーは取引を削除できる(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->create();

        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
