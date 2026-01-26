<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\TrustedDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $response = redirect()->intended(route('dashboard', absolute: false));

        // 「このデバイスを信頼する」がチェックされている場合
        if ($request->boolean('trust_device')) {
            $token = Str::random(64);

            TrustedDevice::create([
                'user_id'     => Auth::id(),
                'token'       => $token,
                'device_name' => $request->userAgent(),
                'expires_at'  => now()->addYear(),
            ]);

            // Cookieにトークンを保存（1年間有効）
            $response->cookie('device_token', $token, 525600);
        }

        return $response;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // デバイストークンがあれば削除
        $deviceToken = $request->cookie('device_token');
        if ($deviceToken) {
            TrustedDevice::where('token', $deviceToken)->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->withCookie(cookie()->forget('device_token'));
    }
}
