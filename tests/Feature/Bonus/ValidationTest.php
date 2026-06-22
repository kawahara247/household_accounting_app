<?php

declare(strict_types=1);

namespace Tests\Feature\Bonus;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class ValidationTest extends BonusTestCase
{
    #[Test]
    public function ボーナス作成時に年月と支払元と金額は必須(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('bonuses.store'), []);

        $response->assertSessionHasErrors([
            'year_month',
            'payer',
            'amount',
        ]);
    }
}
