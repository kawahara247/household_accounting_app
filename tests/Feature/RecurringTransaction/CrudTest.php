<?php

declare(strict_types=1);

namespace Tests\Feature\RecurringTransaction;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class CrudTest extends RecurringTransactionTestCase
{
    #[Test]
    public function 認証済みユーザーは定期取引一覧を取得できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->name('家賃')->create();
        RecurringTransaction::factory()
            ->forCategory($category)
            ->name('家賃')
            ->dayOfMonth(25)
            ->payer(PayerType::PersonA)
            ->amount(80000)
            ->memo('毎月の家賃')
            ->create();

        $response = $this->actingAs($user)->get(route('recurring-transactions.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('RecurringTransactions/Index')
                ->has('recurringTransactions', 1)
                ->has(
                    'recurringTransactions.0',
                    fn (Assert $recurring) => $recurring
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
    public function 認証済みユーザーは定期取引を作成できる(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->expense()->name('家賃')->create();

        $response = $this->actingAs($user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
            'memo'         => '毎月の家賃',
        ]);

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
    public function 認証済みユーザーは定期取引を更新できる(): void
    {
        $user      = User::factory()->create();
        $category  = Category::factory()->expense()->name('家賃')->create();
        $recurring = RecurringTransaction::factory()
            ->forCategory($category)
            ->name('家賃')
            ->dayOfMonth(25)
            ->payer(PayerType::PersonA)
            ->amount(80000)
            ->memo('元のメモ')
            ->create();

        $response = $this->actingAs($user)->put(route('recurring-transactions.update', $recurring), [
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_b',
            'amount'       => 85000,
            'memo'         => '更新後のメモ',
        ]);

        $response->assertRedirect(route('recurring-transactions.index'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id'           => $recurring->id,
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'payer'        => 'person_b',
            'amount'       => 85000,
            'memo'         => '更新後のメモ',
        ]);
    }

    #[Test]
    public function 認証済みユーザーは定期取引を削除できる(): void
    {
        $user      = User::factory()->create();
        $category  = Category::factory()->expense()->create();
        $recurring = RecurringTransaction::factory()->forCategory($category)->create();

        $response = $this->actingAs($user)->delete(route('recurring-transactions.destroy', $recurring));

        $response->assertRedirect(route('recurring-transactions.index'));
        $this->assertDatabaseMissing('recurring_transactions', [
            'id' => $recurring->id,
        ]);
    }
}
