<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\DeviceTokenAuth;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceTokenAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 有効なデバイストークンで自動ログインできる(): void
    {
        // Arrange: ユーザーとトークンを作成
        $user = User::factory()->create();
        TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'valid-token-123',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->addYear(),
        ]);

        // Act: ミドルウェアを直接呼び出す
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'valid-token-123');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        // Assert: 認証済みになる
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    #[Test]
    public function 期限切れのトークンでは自動ログインできない(): void
    {
        // Arrange: 期限切れのトークンを作成
        $user = User::factory()->create();
        TrustedDevice::create([
            'user_id'     => $user->id,
            'token'       => 'expired-token',
            'device_name' => 'Test Browser',
            'expires_at'  => now()->subDay(),
        ]);

        // Act: ミドルウェアを直接呼び出す
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'expired-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        // Assert: 未認証のまま
        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function 存在しないトークンでは自動ログインできない(): void
    {
        // Arrange: トークンなし

        // Act: ミドルウェアを直接呼び出す
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'invalid-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        // Assert: 未認証のまま
        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function 自動ログイン時に最終使用日時が更新される(): void
    {
        // Arrange: トークンを作成
        $user   = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id'      => $user->id,
            'token'        => 'tracking-token',
            'device_name'  => 'Test Browser',
            'expires_at'   => now()->addYear(),
            'last_used_at' => null,
        ]);

        // Act: ミドルウェアを直接呼び出す
        $this->travelTo('2026-01-20 15:30:00');
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'tracking-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        // Assert: last_used_at が更新される
        $device->refresh();
        $this->assertNotNull($device->last_used_at);
        $this->assertSame('2026-01-20 15:30:00', $device->last_used_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function 認証済みユーザーはトークン検証をスキップする(): void
    {
        // Arrange: 認証済みユーザーとトークンを用意
        $user   = User::factory()->create();
        $device = TrustedDevice::create([
            'user_id'      => $user->id,
            'token'        => 'should-not-update',
            'device_name'  => 'Test Browser',
            'expires_at'   => now()->addYear(),
            'last_used_at' => null,
        ]);

        Auth::login($user);

        // Act: ミドルウェアを直接呼び出す
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'should-not-update');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        // Assert: 認証済みのまま、last_used_at は更新されない（スキップされた）
        $this->assertTrue(Auth::check());
        $device->refresh();
        $this->assertNull($device->last_used_at);
    }
}
