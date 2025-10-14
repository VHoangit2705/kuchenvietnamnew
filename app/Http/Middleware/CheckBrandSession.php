<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckBrandSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('brand')) {
            // Auth::logout();
            Session::flush();
            // session()->flush();
            // if ($request->expectsJson()) {
            //     return response()->json(['message' => 'Phiên đăng nhập đã hết hạn.'], 401);
            // }
    
            // return redirect('/login')->withErrors([
            //     'msg' => 'Phiên đăng nhập đã hết hạn.',
            // ]);
            return redirect()->route('home');
        }

        return $next($request);
    }
}
