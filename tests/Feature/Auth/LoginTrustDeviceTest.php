<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTrustDeviceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function trust_deviceをチェックしてログインするとデバイストークンが生成される(): void
    {
        // Arrange: ユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act: trust_deviceをチェックしてログイン
        $response = $this->post(route('login'), [
            'email'        => $user->email,
            'password'     => 'password',
            'trust_device' => true,
        ]);

        // Assert: ログイン成功しダッシュボードにリダイレクト
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Assert: デバイストークンがDBに保存される
        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $user->id,
        ]);

        // Assert: Cookieにトークンが設定される
        $response->assertCookie('device_token');
    }

    #[Test]
    public function trust_deviceをチェックしないとデバイストークンは生成されない(): void
    {
        // Arrange: ユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act: trust_deviceなしでログイン
        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        // Assert: ログイン成功
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Assert: デバイストークンが生成されない
        $this->assertDatabaseMissing('trusted_devices', [
            'user_id' => $user->id,
        ]);

        // Assert: Cookieが設定されない
        $response->assertCookieMissing('device_token');
    }

    #[Test]
    public function デバイストークンの有効期限は1年後に設定される(): void
    {
        // Arrange: ユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act: 固定日時でログイン
        $this->travelTo('2026-01-20 10:00:00');
        $this->post(route('login'), [
            'email'        => $user->email,
            'password'     => 'password',
            'trust_device' => true,
        ]);

        // Assert: 有効期限が1年後に設定される
        $device = TrustedDevice::where('user_id', $user->id)->first();
        $this->assertNotNull($device);
        $this->assertSame('2027-01-20 10:00:00', $device->expires_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function デバイストークンにユーザーエージェントが記録される(): void
    {
        // Arrange: ユーザーを作成
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act: 特定のUserAgentでログイン
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ])->post(route('login'), [
            'email'        => $user->email,
            'password'     => 'password',
            'trust_device' => true,
        ]);

        // Assert: device_nameにUserAgentが記録される
        $device = TrustedDevice::where('user_id', $user->id)->first();
        $this->assertNotNull($device);
        $this->assertStringContainsString('Chrome', $device->device_name);
    }
}
