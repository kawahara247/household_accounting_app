<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PayerType;
use App\Models\Bonus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BonusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 認証済みユーザーはボーナス一覧を取得できる(): void
    {
        // Arrange: 認証ユーザーと受取人別集計対象のボーナスを作成
        $user = User::factory()->create();
        Bonus::create([
            'year_month' => '2026-06',
            'payer'      => PayerType::PersonA,
            'amount'     => 250000,
        ]);
        Bonus::create([
            'year_month' => '2026-12',
            'payer'      => PayerType::PersonA,
            'amount'     => 50000,
        ]);
        Bonus::create([
            'year_month' => '2026-07',
            'payer'      => PayerType::PersonB,
            'amount'     => 180000,
        ]);

        // Act: ボーナス一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('bonuses.index'));

        // Assert: ボーナスと受取人別合計を含むページが返される
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Bonuses/Index')
                ->has('bonuses', 3)
                ->has(
                    'bonuses.0',
                    fn (Assert $bonus) => $bonus
                        ->has('id')
                        ->where('year_month', '2026-12')
                        ->where('payer', 'person_a')
                        ->where('amount', 50000)
                        ->etc()
                )
                ->has('payers', 2)
                ->has('bonusTotals', 2)
                ->where('bonusTotals.0.value', 'person_a')
                ->where('bonusTotals.0.label', config('payers.person_a'))
                ->where('bonusTotals.0.amount', 300000)
                ->where('bonusTotals.1.value', 'person_b')
                ->where('bonusTotals.1.label', config('payers.person_b'))
                ->where('bonusTotals.1.amount', 180000)
        );
    }

    #[Test]
    public function 未認証ユーザーはボーナス一覧にアクセスできない(): void
    {
        // Arrange: 認証なしの状態

        // Act: ボーナス一覧ページにアクセス
        $response = $this->get(route('bonuses.index'));

        // Assert: ログインページへリダイレクトされる
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function 認証済みユーザーはボーナスを作成できる(): void
    {
        // Arrange: 認証ユーザーを準備
        $user = User::factory()->create();

        // Act: ボーナス作成エンドポイントにPOST
        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 250000,
        ]);

        // Assert: 一覧にリダイレクトされ、データベースに保存される
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
        // Arrange: 認証ユーザーと既存ボーナスを準備
        $user  = User::factory()->create();
        $bonus = Bonus::create([
            'year_month' => '2026-06',
            'payer'      => PayerType::PersonA,
            'amount'     => 250000,
        ]);

        // Act: ボーナス更新エンドポイントにPUT
        $response = $this->actingAs($user)->put(route('bonuses.update', $bonus), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 300000,
        ]);

        // Assert: 一覧にリダイレクトされ、金額が更新される
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
        // Arrange: 認証ユーザーと既存ボーナスを準備
        $user  = User::factory()->create();
        $bonus = Bonus::create([
            'year_month' => '2026-06',
            'payer'      => PayerType::PersonA,
            'amount'     => 250000,
        ]);

        // Act: ボーナス削除エンドポイントにDELETE
        $response = $this->actingAs($user)->delete(route('bonuses.destroy', $bonus));

        // Assert: 一覧にリダイレクトされ、データベースから削除される
        $response->assertRedirect(route('bonuses.index'));
        $this->assertDatabaseMissing('bonuses', [
            'id' => $bonus->id,
        ]);
    }

    #[Test]
    public function ボーナス作成時に年月と支払元と金額は必須(): void
    {
        // Arrange: 必須項目なしのボーナスデータを準備
        $user = User::factory()->create();

        // Act: 必須項目を指定せずにPOST
        $response = $this->actingAs($user)->post(route('bonuses.store'), []);

        // Assert: 必須フィールドにバリデーションエラーが発生
        $response->assertSessionHasErrors([
            'year_month',
            'payer',
            'amount',
        ]);
    }

    #[Test]
    public function 同一年月と支払元のボーナスは重複作成できない(): void
    {
        // Arrange: 同一年月と支払元の既存ボーナスを作成
        $user = User::factory()->create();
        Bonus::create([
            'year_month' => '2026-06',
            'payer'      => PayerType::PersonA,
            'amount'     => 250000,
        ]);

        // Act: 同じ年月と支払元でPOST
        $response = $this->actingAs($user)->post(route('bonuses.store'), [
            'year_month' => '2026-06',
            'payer'      => 'person_a',
            'amount'     => 300000,
        ]);

        // Assert: year_monthフィールドにバリデーションエラーが発生し、件数は増えない
        $response->assertSessionHasErrors('year_month');
        $this->assertDatabaseCount('bonuses', 1);
    }
}
