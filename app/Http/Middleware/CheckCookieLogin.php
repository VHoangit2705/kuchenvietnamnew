<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use App\Models\KyThuat\User;

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
                Auth::logout();
                session()->flush();
                Cookie::queue(Cookie::forget('remember_token'));
        
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
