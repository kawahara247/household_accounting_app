<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use PHPUnit\Framework\Attributes\Test;

class ModelTest extends TransactionTestCase
{
    #[Test]
    public function Transactionをモデルで作成できる(): void
    {
        $category = Category::factory()->expense()->name('食費')->create();

        $transaction = Transaction::factory()
            ->forCategory($category)
            ->on('2026-01-04')
            ->payer(PayerType::PersonA)
            ->amount(1000)
            ->memo('ランチ代')
            ->create();

        $this->assertDatabaseHas('transactions', [
            'id'          => $transaction->id,
            'type'        => FlowType::Expense->value,
            'category_id' => $category->id,
            'payer'       => PayerType::PersonA->value,
            'amount'      => 1000,
            'memo'        => 'ランチ代',
        ]);
        $this->assertSame('2026-01-04', $transaction->date->format('Y-m-d'));
    }

    #[Test]
    public function Transactionはカテゴリとのリレーションを持つ(): void
    {
        $category    = Category::factory()->expense()->name('食費')->create();
        $transaction = Transaction::factory()->forCategory($category)->create();

        $relatedCategory = $transaction->category;

        $this->assertInstanceOf(Category::class, $relatedCategory);
        $this->assertSame($category->id, $relatedCategory->id);
    }
}
