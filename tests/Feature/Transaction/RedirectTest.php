<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class RedirectTest extends TransactionTestCase
{
    #[Test]
    public function 取引作成時にリダイレクト先を指定できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
            '_redirect'   => 'dashboard',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('transactions', [
            'amount' => 1500,
        ]);
    }

    #[Test]
    public function 取引削除時にリダイレクト先を指定できる(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->create();

        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction), [
            '_redirect' => 'dashboard',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    #[Test]
    public function 取引更新時にリダイレクト先を指定できる(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->expense()->create();
        $transaction = Transaction::factory()->forCategory($category)->on('2026-01-04')->amount(1000)->memo('元のメモ')->create();

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'date'        => '2026-01-05',
            'type'        => 'expense',
            'category_id' => $category->id,
            'payer'       => 'person_b',
            'amount'      => 2000,
            'memo'        => '更新後のメモ',
            '_redirect'   => 'dashboard',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'payer'  => 'person_b',
            'amount' => 2000,
            'memo'   => '更新後のメモ',
        ]);
    }
}
