<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // すでにログイン済みならスキップ
        if (Auth::check()) {
            return $next($request);
        }

        // Cookieからデバイストークンを取得
        $deviceToken = $request->cookie('device_token');

        if ($deviceToken) {
            $device = TrustedDevice::where('token', $deviceToken)
                ->where('expires_at', '>', now())
                ->first();

            if ($device) {
                // 自動ログイン
                Auth::loginUsingId($device->user_id);

                // 最終使用日時を更新
                $device->updateLastUsed();

                return $next($request);
            }
        }

        return $next($request);
    }
}
