<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class SummaryTest extends TransactionTestCase
{
    #[Test]
    public function 取引一覧に収入と支出の合計が表示される(): void
    {
        $user            = User::factory()->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();

        // 収入: 50000 + 30000 = 80000
        Transaction::factory()->forCategory($incomeCategory)->payer(PayerType::PersonA)->amount(50000)->create();
        Transaction::factory()->forCategory($incomeCategory)->payer(PayerType::PersonB)->amount(30000)->create();

        // 支出: 1000 + 2000 = 3000
        Transaction::factory()->forCategory($expenseCategory)->payer(PayerType::PersonA)->amount(1000)->create();
        Transaction::factory()->forCategory($expenseCategory)->payer(PayerType::PersonB)->amount(2000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

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
        $user            = User::factory()->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();

        // PersonA: 収入50000 + 支出1000
        Transaction::factory()->forCategory($incomeCategory)->payer(PayerType::PersonA)->amount(50000)->create();
        Transaction::factory()->forCategory($expenseCategory)->payer(PayerType::PersonA)->amount(1000)->create();
        // PersonB: 収入30000（フィルタで除外）
        Transaction::factory()->forCategory($incomeCategory)->payer(PayerType::PersonB)->amount(30000)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', [
            'payer' => 'person_a',
        ]));

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
