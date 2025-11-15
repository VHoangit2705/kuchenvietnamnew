<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KyThuat\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    
    private function User(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    public function Index()
    {
        return view("login");
    }
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.'
        ]);

        $user = User::where('username', $request->username)
                    ->where('password', md5($request->password))
                    ->first();

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
            // Nếu chưa có password_changed_at, set nó thành thời điểm hiện tại
            if (!$user->password_changed_at) {
                $user->password_changed_at = now();
            }
            $user->save();
            Cookie::queue('remember_token', $token, $minutes);
            return redirect()->intended('/');
        }
        
        return back()->withErrors(['msg' => 'Tên đăng nhập hoặc mật khẩu không chính xác'])->withInput();
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

    public function changePassword(Request $request)
    {
        $user = $this->User();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng chưa đăng nhập.'
            ], 401);
        }
        
        // Validation rules
        $rules = [
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ];

        $messages = [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ];

        // Nếu có nhập mật khẩu mới thì bắt buộc phải có mật khẩu hiện tại
        if ($request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = 'required|string|min:6';
            $rules['confirm_password'] = 'required|string|same:new_password';
            
            $messages['current_password.required'] = 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.';
            $messages['new_password.required'] = 'Vui lòng nhập mật khẩu mới.';
            $messages['new_password.min'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            $messages['confirm_password.required'] = 'Vui lòng xác nhận mật khẩu mới.';
            $messages['confirm_password.same'] = 'Mật khẩu xác nhận không khớp.';
        }

        $request->validate($rules, $messages);

        // Kiểm tra username đã tồn tại chưa (trừ user hiện tại)
        $existingUser = User::where('username', $request->username)
            ->where('id', '!=', $user->id)
            ->first();
        
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Tên đăng nhập đã được sử dụng bởi tài khoản khác.'
            ], 422);
        }

        // Kiểm tra email đã tồn tại chưa (trừ user hiện tại)
        if ($request->filled('email')) {
            $existingEmail = User::where('email', $request->email)
                ->where('id', '!=', $user->id)
                ->first();
            
            if ($existingEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email đã được sử dụng bởi tài khoản khác.'
                ], 422);
            }
        }

        // Cập nhật username và email
        $user->username = $request->username;
        $user->email = $request->email;

        // Xử lý đổi mật khẩu nếu có
        if ($request->filled('new_password')) {
            // Kiểm tra mật khẩu hiện tại
            if (md5($request->current_password) !== $user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu hiện tại không chính xác.'
                ], 422);
            }

            // Kiểm tra mật khẩu mới không được trùng với mật khẩu cũ
            if (md5($request->new_password) === $user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại.'
                ], 422);
            }

            // Cập nhật mật khẩu
            $user->password = md5($request->new_password);
            $user->password_changed_at = now();
        }

        $user->save();

        $message = 'Cập nhật thông tin thành công!';
        if ($request->filled('new_password')) {
            $message = 'Cập nhật thông tin và đổi mật khẩu thành công!';
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    public function checkPasswordExpiry(Request $request)
    {
        $user = $this->User();
        
        if (!$user) {
            return response()->json([
                'should_warn' => false
            ]);
        }

        // Nếu chưa có password_changed_at, coi như chưa đổi mật khẩu lần nào
        if (!$user->password_changed_at) {
            return response()->json([
                'should_warn' => true,
                'days_remaining' => 0,
                'message' => 'Bạn chưa đổi mật khẩu lần nào. Vui lòng đổi mật khẩu để bảo mật tài khoản.'
            ]);
        }

        $passwordChangedAt = \Carbon\Carbon::parse($user->password_changed_at);
        $daysSinceChange = $passwordChangedAt->diffInDays(now());
        $daysRemaining = 30 - $daysSinceChange;

        // Cảnh báo nếu đã quá 30 ngày hoặc còn ít hơn 7 ngày
        if ($daysSinceChange >= 30 || $daysRemaining <= 7) {
            return response()->json([
                'should_warn' => true,
                'days_remaining' => max(0, $daysRemaining),
                'days_since_change' => $daysSinceChange
            ]);
        }

        return response()->json([
            'should_warn' => false,
            'days_remaining' => $daysRemaining
        ]);
    }
}
