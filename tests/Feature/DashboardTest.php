<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 認証済みユーザーはダッシュボードにアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('year')
                ->has('month')
                ->has('dailyBalances')
                ->has('monthlyBalance')
                ->has('categories')
                ->has('payers')
        );
    }

    #[Test]
    public function 日別の収支合計を取得できる(): void
    {
        $user            = User::factory()->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $this->travelTo('2026-01-15');

        Transaction::factory()->forCategory($incomeCategory)->on('2026-01-10')->payer(PayerType::PersonA)->amount(50000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-10')->payer(PayerType::PersonA)->amount(1000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-15')->payer(PayerType::PersonB)->amount(2000)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('dailyBalances.10.income', 50000)
                ->where('dailyBalances.10.expense', 1000)
                ->where('dailyBalances.10.balance', 49000)
                ->where('dailyBalances.15.income', 0)
                ->where('dailyBalances.15.expense', 2000)
                ->where('dailyBalances.15.balance', -2000)
        );
    }

    #[Test]
    public function 月間の収支合計を取得できる(): void
    {
        $user            = User::factory()->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $this->travelTo('2026-01-15');

        Transaction::factory()->forCategory($incomeCategory)->on('2026-01-10')->payer(PayerType::PersonA)->amount(100000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-15')->payer(PayerType::PersonA)->amount(30000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-20')->payer(PayerType::PersonB)->amount(20000)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('monthlyBalance.income', 100000)
                ->where('monthlyBalance.expense', 50000)
                ->where('monthlyBalance.balance', 50000)
        );
    }

    #[Test]
    public function 特定日の取引一覧を取得できる(): void
    {
        $user            = User::factory()->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $transaction1 = Transaction::factory()
            ->forCategory($incomeCategory)
            ->on('2026-01-10')
            ->payer(PayerType::PersonA)
            ->amount(50000)
            ->memo('給与')
            ->create();
        $transaction2 = Transaction::factory()
            ->forCategory($expenseCategory)
            ->on('2026-01-10')
            ->payer(PayerType::PersonB)
            ->amount(1000)
            ->memo('ランチ')
            ->create();
        Transaction::factory()
            ->forCategory($expenseCategory)
            ->on('2026-01-11')
            ->payer(PayerType::PersonA)
            ->amount(2000)
            ->create();

        $response = $this->actingAs($user)->getJson(route('dashboard.transactions', ['date' => '2026-01-10']));

        $response->assertOk();
        $response->assertJsonCount(2, 'transactions');
        $response->assertJsonFragment([
            'id'          => $transaction1->id,
            'date'        => '2026-01-10',
            'category_id' => $incomeCategory->id,
            'amount'      => 50000,
            'payer'       => 'person_a',
        ]);
        $response->assertJsonFragment([
            'id'          => $transaction2->id,
            'date'        => '2026-01-10',
            'category_id' => $expenseCategory->id,
            'amount'      => 1000,
            'payer'       => 'person_b',
        ]);
    }

    #[Test]
    public function 年月を指定してダッシュボードを表示できる(): void
    {
        $user            = User::factory()->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $this->travelTo('2026-01-15');

        Transaction::factory()->forCategory($expenseCategory)->on('2025-12-10')->payer(PayerType::PersonA)->amount(5000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-10')->payer(PayerType::PersonA)->amount(3000)->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['year' => 2025, 'month' => 12]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('year', 2025)
                ->where('month', 12)
                ->where('dailyBalances.10.expense', 5000)
                ->where('monthlyBalance.expense', 5000)
        );
    }

    #[Test]
    public function payer別の月次収支を取得できる(): void
    {
        $user            = User::factory()->create();
        $incomeCategory  = Category::factory()->income()->name('給与')->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $this->travelTo('2026-01-15');

        // PersonA: 100000 - 30000 = 70000
        Transaction::factory()->forCategory($incomeCategory)->on('2026-01-10')->payer(PayerType::PersonA)->amount(100000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-15')->payer(PayerType::PersonA)->amount(30000)->create();

        // PersonB: 50000 - 60000 = -10000
        Transaction::factory()->forCategory($incomeCategory)->on('2026-01-10')->payer(PayerType::PersonB)->amount(50000)->create();
        Transaction::factory()->forCategory($expenseCategory)->on('2026-01-20')->payer(PayerType::PersonB)->amount(60000)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('payerBalances')
                ->where('payerBalances.person_a.balance', 70000)
                ->where('payerBalances.person_b.balance', -10000)
        );
    }

    #[Test]
    public function 月末日にdatetime形式で保存された取引もカレンダーに正しく集計される(): void
    {
        $user            = User::factory()->create();
        $expenseCategory = Category::factory()->expense()->name('食費')->create();

        $this->travelTo('2026-03-31');

        // date形式で保存（'2026-03-31'）
        DB::table('transactions')->insert([
            'date'        => '2026-03-31',
            'type'        => FlowType::Expense->value,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA->value,
            'amount'      => 1000,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // datetime形式で保存（'2026-03-31 00:00:00'）
        DB::table('transactions')->insert([
            'date'        => '2026-03-31 00:00:00',
            'type'        => FlowType::Expense->value,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonB->value,
            'amount'      => 2000,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', ['year' => 2026, 'month' => 3]));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('dailyBalances.31.expense', 3000)
                ->where('monthlyBalance.expense', 3000)
        );
    }
}
