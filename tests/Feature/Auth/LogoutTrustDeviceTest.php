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
        // Arrange: ユーザーとトークンを作成
        $user = User::factory()->create();
        TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'logout-test-token',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->addYear(),
        ]);

        // Act: トークンをCookieに持った状態でログアウト
        $response = $this->actingAs($user)
            ->withUnencryptedCookie('device_token', 'logout-test-token')
            ->post(route('logout'));

        // Assert: トークンがDBから削除される
        $this->assertDatabaseMissing('trusted_devices', [
            'token' => 'logout-test-token',
        ]);
    }

    #[Test]
    public function ログアウト時にデバイストークンCookieが削除される(): void
    {
        // Arrange: ユーザーを作成
        $user = User::factory()->create();

        // Act: ログアウト
        $response = $this->actingAs($user)
            ->withUnencryptedCookie('device_token', 'cookie-test-token')
            ->post(route('logout'));

        // Assert: device_token Cookieが削除される（期限切れ）
        $response->assertCookie('device_token', null);
    }

    #[Test]
    public function トークンなしでログアウトしてもエラーにならない(): void
    {
        // Arrange: ユーザーを作成（トークンなし）
        $user = User::factory()->create();

        // Act: トークンなしでログアウト
        $response = $this->actingAs($user)->post(route('logout'));

        // Assert: 正常にログアウトできる
        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
