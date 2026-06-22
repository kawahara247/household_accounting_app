<?php

declare(strict_types=1);

namespace Tests\Feature\Bonus;

use App\Enums\PayerType;
use App\Models\Bonus;
use PHPUnit\Framework\Attributes\Test;

class AuthGuardTest extends BonusTestCase
{
    #[Test]
    public function 未認証ユーザーはボーナス一覧にアクセスできない(): void
    {
        $response = $this->get(route('bonuses.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 未認証ユーザーはボーナスを作成できない(): void
    {
        $response = $this->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 250000,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('bonuses', ['year_month' => '2026-06']);
    }

    #[Test]
    public function 未認証ユーザーはボーナスを更新できない(): void
    {
        $bonus = Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();

        $response = $this->put(route('bonuses.update', $bonus), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 999999,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('bonuses', [
            'id'     => $bonus->id,
            'amount' => 250000,
        ]);
    }

    #[Test]
    public function 未認証ユーザーはボーナスを削除できない(): void
    {
        $bonus = Bonus::factory()->create();

        $response = $this->delete(route('bonuses.destroy', $bonus));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('bonuses', ['id' => $bonus->id]);
    }
}
