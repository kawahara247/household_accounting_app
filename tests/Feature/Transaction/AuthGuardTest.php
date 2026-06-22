<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use PHPUnit\Framework\Attributes\Test;

class AuthGuardTest extends TransactionTestCase
{
    #[Test]
    public function 未認証ユーザーは取引一覧にアクセスできない(): void
    {
        $response = $this->get(route('transactions.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 未認証ユーザーは取引を作成できない(): void
    {
        $category = Category::factory()->expense()->create();

        $response = $this->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を更新できない(): void
    {
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->amount(1000)->create();

        $response = $this->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'amount' => 1000,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引を削除できない(): void
    {
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->create();

        $response = $this->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
