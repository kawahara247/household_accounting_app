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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 認証済みユーザーはダッシュボードにアクセスできる(): void
    {
        // Arrange: 認証ユーザーを作成
        $user = User::factory()->create();

        // Act: ダッシュボードページにアクセス
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert: 必要なpropsを含むInertiaページが返される
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
        // Arrange: 同じ日と異なる日に収入・支出の取引を作成
        $user            = User::factory()->create();
        $incomeCategory  = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $expenseCategory = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        $this->travelTo('2026-01-15');

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
        ]);
        Transaction::create([
            'date'        => '2026-01-15',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 2000,
        ]);

        // Act: ダッシュボードページにアクセス
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert: 日別の収入・支出・差引が正しく計算される
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
        // Arrange: 同月内に複数の収入・支出取引を作成
        $user            = User::factory()->create();
        $incomeCategory  = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $expenseCategory = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        $this->travelTo('2026-01-15');

        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 100000,
        ]);
        Transaction::create([
            'date'        => '2026-01-15',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 30000,
        ]);
        Transaction::create([
            'date'        => '2026-01-20',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 20000,
        ]);

        // Act: ダッシュボードページにアクセス
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Assert: 月間の収入・支出・差引の合計が正しく計算される
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
        // Arrange: 複数日に取引を作成（1/10に2件、1/11に1件）
        $user            = User::factory()->create();
        $incomeCategory  = Category::create(['name' => '給与', 'type' => FlowType::Income]);
        $expenseCategory = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        $transaction1 = Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Income,
            'category_id' => $incomeCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 50000,
            'memo'        => '給与',
        ]);
        $transaction2 = Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonB,
            'amount'      => 1000,
            'memo'        => 'ランチ',
        ]);
        Transaction::create([
            'date'        => '2026-01-11',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 2000,
        ]);

        // Act: 特定日（1/10）の取引一覧APIを呼び出す
        $response = $this->actingAs($user)->getJson(route('dashboard.transactions', ['date' => '2026-01-10']));

        // Assert: 指定日の取引のみが返される
        $response->assertOk();
        $response->assertJsonCount(2, 'transactions');
        $response->assertJsonFragment(['id' => $transaction1->id, 'amount' => 50000, 'payer' => 'person_a']);
        $response->assertJsonFragment(['id' => $transaction2->id, 'amount' => 1000, 'payer' => 'person_b']);
    }

    #[Test]
    public function 年月を指定してダッシュボードを表示できる(): void
    {
        // Arrange: 異なる月（2025年12月と2026年1月）に取引を作成
        $user            = User::factory()->create();
        $expenseCategory = Category::create(['name' => '食費', 'type' => FlowType::Expense]);

        $this->travelTo('2026-01-15');

        Transaction::create([
            'date'        => '2025-12-10',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 5000,
        ]);
        Transaction::create([
            'date'        => '2026-01-10',
            'type'        => FlowType::Expense,
            'category_id' => $expenseCategory->id,
            'payer'       => PayerType::PersonA,
            'amount'      => 3000,
        ]);

        // Act: 過去の年月（2025年12月）を指定してダッシュボードにアクセス
        $response = $this->actingAs($user)->get(route('dashboard', ['year' => 2025, 'month' => 12]));

        // Assert: 指定した年月の取引のみが集計される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->where('year', 2025)
                ->where('month', 12)
                ->where('dailyBalances.10.expense', 5000)
                ->where('monthlyBalance.expense', 5000)
        );
    }
}
