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
        $device = TrustedDevice::factory()->expired()->create();

        $this->assertTrue($device->isExpired());
    }

    #[Test]
    public function 有効期限内のデバイストークンを判定できる(): void
    {
        $device = TrustedDevice::factory()->valid()->create();

        $this->assertFalse($device->isExpired());
    }

    #[Test]
    public function 最終使用日時を更新できる(): void
    {
        $device = TrustedDevice::factory()->create();

        $this->travelTo('2026-01-15 10:00:00');
        $device->updateLastUsed();

        $device->refresh();
        $this->assertNotNull($device->last_used_at);
        $this->assertSame('2026-01-15 10:00:00', $device->last_used_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function ユーザーとのリレーションが正しく設定されている(): void
    {
        $user   = User::factory()->create(['name' => 'Test User']);
        $device = TrustedDevice::factory()->forUser($user)->create();

        $relatedUser = $device->user;

        $this->assertInstanceOf(User::class, $relatedUser);
        $this->assertSame($user->id, $relatedUser->id);
        $this->assertSame('Test User', $relatedUser->name);
    }

    #[Test]
    public function ユーザーから信頼済みデバイス一覧を取得できる(): void
    {
        $user = User::factory()->create();
        TrustedDevice::factory()->forUser($user)->token('token-1')->deviceName('Chrome on Windows')->create();
        TrustedDevice::factory()->forUser($user)->token('token-2')->deviceName('Safari on iPhone')->create();

        $tokens = $user->trustedDevices->pluck('token')->all();

        $this->assertCount(2, $tokens);
        $this->assertContains('token-1', $tokens);
        $this->assertContains('token-2', $tokens);
    }
}
