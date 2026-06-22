<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayerType;
use App\Models\Bonus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bonus>
 */
class BonusFactory extends Factory
{
    protected $model = Bonus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year_month' => now()->format('Y-m'),
            'payer'      => PayerType::PersonA,
            'amount'     => 100000,
        ];
    }

    public function yearMonth(string $yearMonth): static
    {
        return $this->state(['year_month' => $yearMonth]);
    }

    public function payer(PayerType $payer): static
    {
        return $this->state(['payer' => $payer]);
    }

    public function amount(int $amount): static
    {
        return $this->state(['amount' => $amount]);
    }
}
