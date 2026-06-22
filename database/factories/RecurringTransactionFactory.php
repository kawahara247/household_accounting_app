<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FlowType;
use App\Enums\PayerType;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => fake()->word(),
            'day_of_month' => 25,
            'type'         => FlowType::Expense,
            'category_id'  => Category::factory(),
            'payer'        => PayerType::PersonA,
            'amount'       => 80000,
            'memo'         => null,
            'is_active'    => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function dayOfMonth(int $day): static
    {
        return $this->state(['day_of_month' => $day]);
    }

    public function name(string $name): static
    {
        return $this->state(['name' => $name]);
    }

    public function payer(PayerType $payer): static
    {
        return $this->state(['payer' => $payer]);
    }

    public function amount(int $amount): static
    {
        return $this->state(['amount' => $amount]);
    }

    public function memo(string $memo): static
    {
        return $this->state(['memo' => $memo]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state([
            'category_id' => $category->id,
            'type'        => $category->type,
        ]);
    }
}
