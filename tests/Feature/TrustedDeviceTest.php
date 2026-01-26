<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedDeviceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 期限切れのデバイストークンを判定できる(): void
    {
        // Arrange: 期限切れのトークンを作成
        $user   = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'test-token-123',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->subDay(),
        ]);

        // Act & Assert: 期限切れと判定される
        $this->assertTrue($device->isExpired());
    }

    #[Test]
    public function 有効期限内のデバイストークンを判定できる(): void
    {
        // Arrange: 有効なトークンを作成
        $user   = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'test-token-456',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->addDay(),
        ]);

        // Act & Assert: 期限切れではないと判定される
        $this->assertFalse($device->isExpired());
    }

    #[Test]
    public function 最終使用日時を更新できる(): void
    {
        // Arrange: トークンを作成（last_used_at は null）
        $user   = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id'      => $user->id,
            'token'        => 'test-token-789',
            'device_name'  => 'Test Browser',
            'expires_at'   => now()->addYear(),
            'last_used_at' => null,
        ]);

        // Act: 最終使用日時を更新
        $this->travelTo('2026-01-15 10:00:00');
        $device->updateLastUsed();

        // Assert: last_used_at が更新される
        $device->refresh();
        $this->assertNotNull($device->last_used_at);
        $this->assertSame('2026-01-15 10:00:00', $device->last_used_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function ユーザーとのリレーションが正しく設定されている(): void
    {
        // Arrange: ユーザーとトークンを作成
        $user   = User::factory()->create(['name' => 'Test User']);
        $device = TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'test-token-abc',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->addYear(),
        ]);

        // Act: リレーションを通じてユーザーを取得
        $relatedUser = $device->user;

        // Assert: 正しいユーザーが取得できる
        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertSame($user->id, $relatedUser->id);
        $this->assertSame('Test User', $relatedUser->name);
    }

    #[Test]
    public function ユーザーから信頼済みデバイス一覧を取得できる(): void
    {
        // Arrange: ユーザーと複数のトークンを作成
        $user = User::factory()->create();
        TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'token-1',
            'device_name' => 'Chrome on Windows',
            'expires_at'  => now()->addYear(),
        ]);
        TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'token-2',
            'device_name' => 'Safari on iPhone',
            'expires_at'  => now()->addYear(),
        ]);

        // Act: ユーザーから信頼済みデバイスを取得
        $devices = $user->trustedDevices;

        // Assert: 正しい数のデバイスが取得できる
        $this->assertCount(2, $devices);
        $this->assertSame('token-1', $devices[0]->token);
        $this->assertSame('token-2', $devices[1]->token);
    }
}
