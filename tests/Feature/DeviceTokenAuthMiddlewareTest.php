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
        $user = User::factory()->create();
        TrustedDevice::factory()->forUser($user)->token('valid-token-123')->valid()->create();

        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'valid-token-123');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    #[Test]
    public function 期限切れのトークンでは自動ログインできない(): void
    {
        $user = User::factory()->create();
        TrustedDevice::factory()->forUser($user)->token('expired-token')->expired()->create();

        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'expired-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function 存在しないトークンでは自動ログインできない(): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'invalid-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function 自動ログイン時に最終使用日時が更新される(): void
    {
        $user   = User::factory()->create();
        $device = TrustedDevice::factory()->forUser($user)->token('tracking-token')->valid()->create();

        $this->travelTo('2026-01-20 15:30:00');
        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'tracking-token');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        $device->refresh();
        $this->assertNotNull($device->last_used_at);
        $this->assertSame('2026-01-20 15:30:00', $device->last_used_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function 認証済みユーザーはトークン検証をスキップする(): void
    {
        $user   = User::factory()->create();
        $device = TrustedDevice::factory()->forUser($user)->token('should-not-update')->valid()->create();

        Auth::login($user);

        $request = Request::create('/', 'GET');
        $request->cookies->set('device_token', 'should-not-update');

        $middleware = new DeviceTokenAuth;
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertTrue(Auth::check());
        $device->refresh();
        $this->assertNull($device->last_used_at);
    }
}
