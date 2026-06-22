<?php

declare(strict_types=1);

namespace Tests\Feature\Bonus;

use App\Enums\PayerType;
use App\Models\Bonus;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class UniqueConstraintTest extends BonusTestCase
{
    #[Test]
    public function 同一年月と支払元のボーナスは重複作成できない(): void
    {
        $user = User::factory()->create();
        Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();

        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 300000,
        ]);

        $response->assertSessionHasErrors('year_month');
        $this->assertDatabaseCount('bonuses', 1);
    }

    #[Test]
    public function 同一年月でも支払元が異なればボーナスを追加作成できる(): void
    {
        $user = User::factory()->create();
        Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();

        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_b',
            'amount'     => 180000,
        ]);

        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseCount('bonuses', 2);
        $this->assertDatabaseHas('bonuses', [
            'year_month' => '2026-06',
            'payer'      => 'person_b',
            'amount'     => 180000,
        ]);
    }

    #[Test]
    public function 同一支払元でも年月が異なればボーナスを追加作成できる(): void
    {
        $user = User::factory()->create();
        Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();

        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-12',
            'payer'      => 'person_a',
            'amount'     => 50000,
        ]);

        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseCount('bonuses', 2);
        $this->assertDatabaseHas('bonuses', [
            'year_month' => '2026-12',
            'payer'      => 'person_a',
            'amount'     => 50000,
        ]);
    }
}
