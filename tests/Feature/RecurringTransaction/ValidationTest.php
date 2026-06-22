<?php

declare(strict_types=1);

namespace Tests\Feature\RecurringTransaction;

use App\Models\Category;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ValidationTest extends RecurringTransactionTestCase
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
    public function 定期取引作成時に名前は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $this->category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function 定期取引作成時に登録日は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'        => '家賃',
            'type'        => 'expense',
            'category_id' => $this->category->id,
            'payer'       => 'person_a',
            'amount'      => 80000,
        ]);

        $response->assertSessionHasErrors('day_of_month');
    }

    #[Test]
    public function 定期取引作成時に種別は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'category_id'  => $this->category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function 定期取引作成時にカテゴリは必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    #[Test]
    public function 定期取引作成時に支払元は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $this->category->id,
            'amount'       => 80000,
        ]);

        $response->assertSessionHasErrors('payer');
    }

    #[Test]
    public function 定期取引作成時に金額は必須(): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => 25,
            'type'         => 'expense',
            'category_id'  => $this->category->id,
            'payer'        => 'person_a',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    #[Test]
    #[DataProvider('provide定期取引作成時の登録日は1から28の範囲で受け付けるCases')]
    public function 定期取引作成時の登録日は1から28の範囲で受け付ける(int $dayOfMonth, bool $shouldPass): void
    {
        $response = $this->actingAs($this->user)->post(route('recurring-transactions.store'), [
            'name'         => '家賃',
            'day_of_month' => $dayOfMonth,
            'type'         => 'expense',
            'category_id'  => $this->category->id,
            'payer'        => 'person_a',
            'amount'       => 80000,
        ]);

        if ($shouldPass) {
            $response->assertSessionHasNoErrors();
        } else {
            $response->assertSessionHasErrors('day_of_month');
        }
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function provide定期取引作成時の登録日は1から28の範囲で受け付けるCases(): iterable
    {
        return [
            '下限未満 (0)' => [0, false],
            '下限 (1)'     => [1, true],
            '上限 (28)'    => [28, true],
            '上限超 (29)'  => [29, false],
        ];
    }
}
