<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KyThuat\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function Index()
    {
        return view("login");
    }
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required'
        ]);
        $user = User::where('password', md5($request->password))->first();
        if ($user) {
            Auth::login($user);
            $minutes = 60 * 24 * 7; // 7 ngày
            session([
                'user' => $user->full_name,
                'zone' => $user->zone, // chi nhánh
                'position' => $user->position, // chức vụ
            ]);
            $token = Str::random(60);
            $user->cookie_value = hash('sha256', $token);
            $user->save();
            Cookie::queue('remember_token', $token, $minutes);
            return redirect()->intended('/');
        }
        return back()->withErrors(['password' => 'Mật khẩu không chính xác'])->withInput();
    }

    public function logout(Request $request)
    {
        $token = $request->cookie('remember_token');

        if ($token) {
            $hashedToken = hash('sha256', $token);
            $user = User::where('cookie_value', $hashedToken)->first();

            if ($user) {
                $user->cookie_value = null;
                $user->save();
            }
        }
        Auth::logout();
        session()->flush();
        $request->session()->invalidate(); // Hủy session hiện tại
        $request->session()->regenerateToken(); // Tạo lại CSRF token
        Cookie::queue(Cookie::forget('remember_token'));
        return redirect('/login');
    }
}
