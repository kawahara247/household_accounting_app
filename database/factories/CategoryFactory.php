<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FlowType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('カテゴリ###'),
            'type' => FlowType::Expense,
        ];
    }

    public function expense(): static
    {
        return $this->state(['type' => FlowType::Expense]);
    }

    public function income(): static
    {
        return $this->state(['type' => FlowType::Income]);
    }

    public function name(string $name): static
    {
        return $this->state(['name' => $name]);
    }
}
