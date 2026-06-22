<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        $category  = Category::factory()->expense()->name('家賃')->create();
        $recurring = RecurringTransaction::factory()
            ->forCategory($category)
            ->dayOfMonth(15)
            ->active()
            ->state(fn () => [
                'name'   => '家賃',
                'payer'  => PayerType::PersonA,
                'amount' => 80000,
                'memo'   => '毎月の家賃',
            ])
            ->create();

        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        $transaction = Transaction::where('recurring_transaction_id', $recurring->id)->first();
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
        $category = Category::factory()->expense()->name('家賃')->create();
        RecurringTransaction::factory()
            ->forCategory($category)
            ->dayOfMonth(25)
            ->active()
            ->state(fn () => [
                'name'   => '家賃',
                'payer'  => PayerType::PersonA,
                'amount' => 80000,
            ])
            ->create();

        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        $this->assertDatabaseMissing('transactions', [
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function is_activeがfalseの定期取引は生成されない(): void
    {
        $category = Category::factory()->expense()->name('家賃')->create();
        RecurringTransaction::factory()
            ->forCategory($category)
            ->dayOfMonth(15)
            ->inactive()
            ->state(fn () => [
                'name'   => '家賃',
                'payer'  => PayerType::PersonA,
                'amount' => 80000,
            ])
            ->create();

        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        $this->assertDatabaseMissing('transactions', [
            'amount' => 80000,
        ]);
    }

    #[Test]
    public function 当月分が既に存在する場合はスキップされる(): void
    {
        $category  = Category::factory()->expense()->name('家賃')->create();
        $recurring = RecurringTransaction::factory()
            ->forCategory($category)
            ->dayOfMonth(15)
            ->active()
            ->state(fn () => [
                'name'   => '家賃',
                'payer'  => PayerType::PersonA,
                'amount' => 80000,
            ])
            ->create();

        // 既に当月分が存在
        Transaction::factory()
            ->forCategory($category)
            ->on('2026-01-15')
            ->payer(PayerType::PersonA)
            ->amount(80000)
            ->state(fn () => ['recurring_transaction_id' => $recurring->id])
            ->create();

        $this->artisan('transactions:generate-recurring', ['--date' => '2026-01-15'])
            ->assertSuccessful();

        $this->assertCount(1, Transaction::where('recurring_transaction_id', $recurring->id)->get());
    }
}
