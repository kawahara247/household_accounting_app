<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\DashboardService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;

    private Category $incomeCategory;

    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service         = new DashboardService;
        $this->incomeCategory  = Category::factory()->income()->make();
        $this->expenseCategory = Category::factory()->expense()->make();
    }

    #[Test]
    public function calculateBalanceは収入合計支出合計差引を返す(): void
    {
        $transactions = new Collection([
            Transaction::factory()->forCategory($this->incomeCategory)->amount(50000)->make(),
            Transaction::factory()->forCategory($this->incomeCategory)->amount(30000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->amount(10000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->amount(5000)->make(),
        ]);

        $result = $this->service->calculateBalance($transactions);

        $this->assertSame(80000, $result['income']);
        $this->assertSame(15000, $result['expense']);
        $this->assertSame(65000, $result['balance']);
    }

    #[Test]
    public function calculateBalanceは空コレクションで全て0を返す(): void
    {
        $result = $this->service->calculateBalance(new Collection);

        $this->assertSame(0, $result['income']);
        $this->assertSame(0, $result['expense']);
        $this->assertSame(0, $result['balance']);
    }

    #[Test]
    public function calculateDailyBalancesは日付ごとに集計する(): void
    {
        $transactions = new Collection([
            Transaction::factory()->forCategory($this->incomeCategory)->on('2026-01-10')->amount(50000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->on('2026-01-10')->amount(1000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->on('2026-01-15')->amount(2000)->make(),
        ]);

        $daily = $this->service->calculateDailyBalances($transactions);

        $this->assertSame(50000, $daily[10]['income']);
        $this->assertSame(1000, $daily[10]['expense']);
        $this->assertSame(49000, $daily[10]['balance']);
        $this->assertSame(0, $daily[15]['income']);
        $this->assertSame(2000, $daily[15]['expense']);
        $this->assertSame(-2000, $daily[15]['balance']);
    }

    #[Test]
    public function calculatePayerBalancesはpayerごとの収支を返す(): void
    {
        $transactions = new Collection([
            Transaction::factory()->forCategory($this->incomeCategory)->payer(PayerType::PersonA)->amount(100000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->payer(PayerType::PersonA)->amount(30000)->make(),
            Transaction::factory()->forCategory($this->incomeCategory)->payer(PayerType::PersonB)->amount(50000)->make(),
            Transaction::factory()->forCategory($this->expenseCategory)->payer(PayerType::PersonB)->amount(60000)->make(),
        ]);

        $payerBalances = $this->service->calculatePayerBalances($transactions);

        $this->assertSame(70000, $payerBalances['person_a']['balance']);
        $this->assertSame(-10000, $payerBalances['person_b']['balance']);
        $this->assertArrayHasKey('label', $payerBalances['person_a']);
    }

    #[Test]
    public function calculatePayerBalancesは取引のないpayerも0で返す(): void
    {
        $payerBalances = $this->service->calculatePayerBalances(new Collection);

        foreach (PayerType::cases() as $payer) {
            $this->assertSame(0, $payerBalances[$payer->value]['balance']);
        }
    }
}
