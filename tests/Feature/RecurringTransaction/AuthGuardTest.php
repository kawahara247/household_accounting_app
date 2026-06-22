<?php

declare(strict_types=1);

namespace Tests\Feature\RecurringTransaction;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use PHPUnit\Framework\Attributes\Test;

class AuthGuardTest extends RecurringTransactionTestCase
{
    #[Test]
    public function 未認証ユーザーは定期取引一覧にアクセスできない(): void
    {
        $response = $this->get(route('recurring-transactions.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 未認証ユーザーは定期取引を作成できない(): void
    {
        $category = Category::factory()->expense()->name('家賃')->create();

        $response = $this->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('recurring_transactions', [
            'name' => '家賃',
        ]);
    }

    #[Test]
    public function 未認証ユーザーは定期取引を更新できない(): void
    {
        $category  = Category::factory()->expense()->name('家賃')->create();
        $recurring = RecurringTransaction::factory()
            ->forCategory($category)
            ->name('家賃')
            ->dayOfMonth(25)
            ->payer(PayerType::PersonA)
            ->amount(80000)
            ->create();

        $response = $this->put(route('recurring-transactions.update', $recurring), [
            'name'         => '更新後の家賃',
            'day_of_month' => 27,
            'type'         => 'expense',
            'category_id'  => $category->id,
            'payer'        => 'person_b',
            'amount'       => 85000,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id'     => $recurring->id,
            'name'   => '家賃',
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは定期取引を削除できない(): void
    {
        $category  = Category::factory()->expense()->create();
        $recurring = RecurringTransaction::factory()->forCategory($category)->create();

        $response = $this->delete(route('recurring-transactions.destroy', $recurring));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
        ]);
    }
}
