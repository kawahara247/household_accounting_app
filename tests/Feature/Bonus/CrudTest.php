<?php

declare(strict_types=1);

namespace Tests\Feature\Bonus;

use App\Enums\PayerType;
use App\Models\Bonus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;

class CrudTest extends BonusTestCase
{
    #[Test]
    public function 認証済みユーザーはボーナス一覧を取得できる(): void
    {
        $user = User::factory()->create();
        Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();
        Bonus::factory()->yearMonth('2026-12')->payer(PayerType::PersonA)->amount(50000)->create();
        Bonus::factory()->yearMonth('2026-07')->payer(PayerType::PersonB)->amount(180000)->create();

        $response = $this->actingAs($user)->get(route('bonuses.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Bonuses/Index')
                ->has('bonuses', 3)
                ->has(
                    'bonuses.0',
                    fn (Assert $bonus) => $bonus
                        ->hasAll(['id', 'year_month', 'payer', 'amount', 'created_at', 'updated_at'])
                        ->where('year_month', '2026-12')
                        ->where('payer', 'person_a')
                        ->where('amount', 50000)
                )
        );
    }

    #[Test]
    public function ボーナス一覧でpayerごとの合計が集計される(): void
    {
        $user = User::factory()->create();
        Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();
        Bonus::factory()->yearMonth('2026-12')->payer(PayerType::PersonA)->amount(50000)->create();
        Bonus::factory()->yearMonth('2026-07')->payer(PayerType::PersonB)->amount(180000)->create();

        $response = $this->actingAs($user)->get(route('bonuses.index'));

        $response->assertInertia(
            fn (Assert $page) => $page
                ->has('bonusTotals', 2)
                ->has(
                    'bonusTotals.0',
                    fn (Assert $total) => $total
                        ->where('value', 'person_a')
                        ->where('amount', 300000)
                        ->where('label', config('payers.person_a'))
                )
                ->has(
                    'bonusTotals.1',
                    fn (Assert $total) => $total
                        ->where('value', 'person_b')
                        ->where('amount', 180000)
                        ->where('label', config('payers.person_b'))
                )
        );
    }

    #[Test]
    public function ボーナス一覧画面はpayer一覧をプロップスで返す(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('bonuses.index'));

        $response->assertInertia(
            fn (Assert $page) => $page->has('payers', 2)
        );
    }

    #[Test]
    public function 認証済みユーザーはボーナスを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 250000,
        ]);

        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseHas('bonuses', [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 250000,
        ]);
    }

    #[Test]
    public function 認証済みユーザーはボーナスを更新できる(): void
    {
        $user  = User::factory()->create();
        $bonus = Bonus::factory()->yearMonth('2026-06')->payer(PayerType::PersonA)->amount(250000)->create();

        $response = $this->actingAs($user)->put(route('bonuses.update', $bonus), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 300000,
        ]);

        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseHas('bonuses', [
            'id'         => $bonus->id,
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 300000,
        ]);
    }

    #[Test]
    public function 認証済みユーザーはボーナスを削除できる(): void
    {
        $user  = User::factory()->create();
        $bonus = Bonus::factory()->create();

        $response = $this->actingAs($user)->delete(route('bonuses.destroy', $bonus));

        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseMissing('bonuses', [
            'id' => $bonus->id,
        ]);
    }
}
