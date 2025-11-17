<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use App\Models\KyThuat\User;
use App\Models\KyThuat\UserDeviceToken;

class CheckCookieLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() && Cookie::has('remember_token')) {
            $token = Cookie::get('remember_token');
            $hashedToken = hash('sha256', $token);
            $user = User::where('cookie_value', $hashedToken)->first();

            if ($user) {
                Auth::login($user);

                session([
                    'user' => $user->full_name,
                    'zone' => $user->zone,
                    'position' => $user->position,
                ]);
            }
        }

        // Nếu đã đăng nhập và có cookie nhưng token không khớp DB thì logout
        if (Auth::check() && Cookie::has('remember_token')) {
            $token = Cookie::get('remember_token');
            $hashedToken = hash('sha256', $token);
            
            if (Auth::user()->cookie_value !== $hashedToken) {
                // Chỉ xóa device token của thiết bị A (thiết bị hiện tại) chứ không xóa tất cả
                $deviceToken = Cookie::get('device_token');
                if ($deviceToken) {
                    $hashedDeviceToken = hash('sha256', $deviceToken);
                    UserDeviceToken::where('device_token', $hashedDeviceToken)
                        ->where('is_active', 1)
                        ->update(['is_active' => 0]);
                }
                
                Auth::logout();
                session()->flush();
                $request->session()->invalidate(); // Hủy session hiện tại
                $request->session()->regenerateToken(); // Tạo lại CSRF token
                Cookie::queue(Cookie::forget('remember_token'));
                Cookie::queue(Cookie::forget('device_token'));
        
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Phiên đăng nhập đã hết hạn.'], 401);
                }
        
                return redirect('/login')->withErrors([
                    'msg' => 'Phiên đăng nhập đã hết hạn.',
                ]);
            }
        }

        return $next($request);
    }
}
