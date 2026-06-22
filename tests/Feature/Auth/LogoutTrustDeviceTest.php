<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTrustDeviceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ログアウト時にデバイストークンがDBから削除される(): void
    {
        $user = User::factory()->create();
        TrustedDevice::factory()->forUser($user)->token('logout-test-token')->create();

        $this->actingAs($user)
            ->withUnencryptedCookie('device_token', 'logout-test-token')
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertDatabaseMissing('trusted_devices', [
            'token' => 'logout-test-token',
        ]);
    }

    #[Test]
    public function ログアウト時にデバイストークンCookieが削除される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('device_token', 'cookie-test-token')
            ->post(route('logout'));

        $response->assertCookie('device_token', null);
    }

    #[Test]
    public function トークンなしでログアウトしてもエラーにならない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
