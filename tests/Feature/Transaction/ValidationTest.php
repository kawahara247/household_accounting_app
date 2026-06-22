<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Models\Category;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class ValidationTest extends TransactionTestCase
{
    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->category = Category::factory()->expense()->create();
    }

    #[Test]
    public function 取引作成時に日付は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'type'        => 'expense',
            'category_id' => $this->category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        $response->assertSessionHasErrors('date');
    }

    #[Test]
    public function 取引作成時に種別は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'category_id' => $this->category->id,
            'payer'       => 'person_a',
            'amount'      => 1500,
        ]);

        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 取引作成時にカテゴリは必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date'   => '2026-01-04',
            'type'   => 'expense',
            'payer'  => 'person_a',
            'amount' => 1500,
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 取引作成時に支払元は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $this->category->id,
            'amount'      => 1500,
        ]);

        $response->assertSessionHasErrors('payer');
    }

    #[Test]
    public function 取引作成時に金額は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'date'        => '2026-01-04',
            'type'        => 'expense',
            'category_id' => $this->category->id,
            'payer'       => 'person_a',
        ]);

        $response->assertSessionHasErrors('amount');
    }
}
