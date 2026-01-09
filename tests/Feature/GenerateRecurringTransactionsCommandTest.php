<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateRecurringTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 対象日の定期取引から取引が生成される(): void
    {
        // Arrange: day_of_month=15の定期取引を作成
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 15,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'memo'         => '毎月の家賃',
            'is_active'    => true,
        ]);

        // Act: 15日を指定してコマンド実行
        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        // Assert: 取引が生成される
        $transaction = Transaction::where('recurring_transaction_id', $recurringTransaction->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('2026-01-15', $transaction->date->format('Y-m-d'));
        $this->assertSame('expense', $transaction->type->value);
        $this->assertSame($category->id, $transaction->category_id);
        $this->assertSame('person_a', $transaction->payer->value);
        $this->assertSame(80000, $transaction->amount);
        $this->assertSame('毎月の家賃', $transaction->memo);
    }

    #[Test]
    public function day_of_monthが異なる定期取引は生成されない(): void
    {
        // Arrange: day_of_month=25の定期取引を作成
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'is_active'    => true,
        ]);

        // Act: 15日を指定してコマンド実行（day_of_monthが異なる）
        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        // Assert: 取引は生成されない
        $this->assertDatabaseMissing('transactions', [
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function is_activeがfalseの定期取引は生成されない(): void
    {
        // Arrange: is_active=falseの定期取引を作成
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 15,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'is_active'    => false,
        ]);

        // Act: 15日を指定してコマンド実行
        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        // Assert: 取引は生成されない
        $this->assertDatabaseMissing('transactions', [
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function 当月分が既に存在する場合はスキップされる(): void
    {
        // Arrange: 定期取引と当月分の取引を作成
        $category = Category::create([
            'name' => '家賃',
            'type' => FlowType::Expense,
        ]);
        $recurringTransaction = RecurringTransaction::create([
            'name'         => '家賃',
            'day_of_month' => 15,
            'type'         => FlowType::Expense,
            'category_id'  => $category->id,
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'is_active'    => true,
        ]);
        // 既に当月分が存在
        Transaction::create([
            'date'                     => '2026-01-15',
            'type'                     => FlowType::Expense,
            'category_id'              => $category->id,
            'payer'                    => PayerType::PersonA,
            'amount'                   => 80000,
            'recurring_transaction_id' => $recurringTransaction->id,
        ]);

        // Act: 15日を指定してコマンド再実行
        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        // Assert: 取引は1件のまま（二重生成されない）
        $this->assertCount(1, Transaction::where('recurring_transaction_id', $recurringTransaction->id)->get());
    }
}
