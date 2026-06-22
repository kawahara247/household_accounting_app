<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date'        => now()->format('Y-m-d'),
            'type'        => FlowType::Expense,
            'category_id' => Category::factory(),
            'payer'       => PayerType::PersonA,
            'amount'      => 1000,
            'memo'        => null,
        ];
    }

    public function on(string $date): static
    {
        return $this->state(['date' => $date]);
    }

    public function amount(int $amount): static
    {
        return $this->state(['amount' => $amount]);
    }

    public function payer(PayerType $payer): static
    {
        return $this->state(['payer' => $payer]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state([
            'category_id' => $category->id,
            'type'        => $category->type,
        ]);
    }

    public function memo(string $memo): static
    {
        return $this->state(['memo' => $memo]);
    }
}
