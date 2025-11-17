<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KyThuat\User;
use App\Models\KyThuat\UserDeviceToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
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
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'device_fingerprint' => 'nullable|string',
            'browser_info' => 'nullable|json'
        ]);

        $user = User::where('username', $request->username)
                    ->where('password', md5($request->password))
                    ->first();

            
        $user = User::where('password', md5($request->password))->first();
        if ($user) {
            // Xử lý device token
            $deviceFingerprint = $request->input('device_fingerprint');
            $browserInfo = $request->input('browser_info');
            
            if ($deviceFingerprint) {
                // Kiểm tra device token trong cookie
                $existingToken = Cookie::get('device_token');
                
                if ($existingToken) {
                    // Kiểm tra token này thuộc về user nào (bao gồm cả inactive)
                    $hashedToken = hash('sha256', $existingToken);
                    $tokenRecord = UserDeviceToken::where('device_token', $hashedToken)
                        ->where('is_active', 1)
                        ->first();
                    
                    if ($tokenRecord) {
                        // Token đã tồn tại và active
                        if ($tokenRecord->user_id !== $user->id) {
                            // Token thuộc về user khác → Chặn
                            return back()->withErrors([
                                'password' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác.Vui lòng sử dụng thiết bị khác.'
                            ])->withInput();
                        }
                        // Token thuộc về user này và active → Cập nhật last_used_at
                        $tokenRecord->update(['last_used_at' => now()]);
                        $deviceToken = $existingToken;
                    } else {
                        // Token không tồn tại hoặc không active → Kiểm tra xem có phải token cũ của user này không
                        $oldTokenRecord = UserDeviceToken::where('device_token', $hashedToken)
                            ->where('is_active', 0)
                            ->where('user_id', $user->id)
                            ->first();
                        
                        if ($oldTokenRecord) {
                            // Token cũ của user này (đã bị deactivate) → Xóa cookie và tạo mới
                            Cookie::queue(Cookie::forget('device_token'));
                        }
                        
                        // Tạo token mới
                        $result = $this->createNewDeviceToken($user, $deviceFingerprint, $request->ip(), $browserInfo);
                        if ($result === false) {
                            return back()->withErrors([
                                'password' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác. Vui lòng sử dụng thiết bị khác.'
                            ])->withInput();
                        }
                        $deviceToken = $result;
                    }
                } else {
                    // Không có token trong cookie → Kiểm tra device fingerprint
                    $existingDevice = UserDeviceToken::where('device_fingerprint', $deviceFingerprint)
                        ->where('is_active', 1)
                        ->first();
                    
                    if ($existingDevice && $existingDevice->user_id !== $user->id) {
                        // Device đã được user khác sử dụng → Chặn
                        return back()->withErrors([
                            'password' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác. Vui lòng sử dụng thiết bị khác.'
                        ])->withInput();
                    }
                    
                    // Tạo token mới
                    $result = $this->createNewDeviceToken($user, $deviceFingerprint, $request->ip(), $browserInfo);
                    if ($result === false) {
                        return back()->withErrors([
                            'password' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác. Vui lòng sử dụng thiết bị khác.'
                        ])->withInput();
                    }
                    $deviceToken = $result;
                }
            }
            
            Auth::login($user);
            $minutes = 60 * 24 * 7; // 7 ngày
            session([
                'user' => $user->full_name,
                'zone' => $user->zone, // chi nhánh
                'position' => $user->position, // chức vụ
            ]);
            
            // Remember token (giữ nguyên logic cũ)
            $token = Str::random(60);
            $user->cookie_value = hash('sha256', $token);
            // Nếu chưa có password_changed_at, set nó thành thời điểm hiện tại
            if (!$user->password_changed_at) {
                $user->password_changed_at = now();
            }
            $user->save();
            Cookie::queue('remember_token', $token, $minutes);
            
            // Device token (mới)
            if (isset($deviceToken)) {
                Cookie::queue('device_token', $deviceToken, $minutes);
            }
            
            return redirect()->intended('/');
        }
        
        return back()->withErrors(['msg' => 'Tên đăng nhập hoặc mật khẩu không chính xác'])->withInput();
    }
    
    /**
     * Tạo device token mới
     * @return string|false Trả về token nếu thành công, false nếu device đã được user khác sử dụng
     */
    private function createNewDeviceToken($user, $deviceFingerprint, $ipAddress, $browserInfo)
    {
        try {
            $token = Str::random(60);
            $hashedToken = hash('sha256', $token);
            
            // Kiểm tra device này đã có token của user khác chưa?
            $existingDevice = UserDeviceToken::where('device_fingerprint', $deviceFingerprint)
                ->where('is_active', 1)
                ->where('user_id', '!=', $user->id)
                ->first();
            
            if ($existingDevice) {
                // Device đã được user khác sử dụng → Trả về false thay vì throw exception
                return false;
            }
            
            // Xử lý browser_info nếu là JSON string
            $browserInfoJson = null;
            if ($browserInfo) {
                if (is_string($browserInfo)) {
                    // Nếu là JSON string, decode nó
                    $decoded = json_decode($browserInfo, true);
                    $browserInfoJson = $decoded ? json_encode($decoded) : $browserInfo;
                } else {
                    $browserInfoJson = json_encode($browserInfo);
                }
            }
            
            // Tạo hoặc cập nhật token
            UserDeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_fingerprint' => $deviceFingerprint
                ],
                [
                    'device_token' => $hashedToken,
                    'ip_address' => $ipAddress,
                    'browser_info' => $browserInfoJson,
                    'is_active' => 1,
                    'last_used_at' => now()
                ]
            );
            
            return $token;
        } catch (\Exception $e) {
            // Log lỗi và trả về false
            Log::error('Error creating device token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Thực hiện logout user (tái sử dụng cho logout và đổi mật khẩu)
     * @param Request $request
     * @return void
     */
    private function performLogout(Request $request)
    {
        $token = $request->cookie('remember_token');
        $user = null;

        if ($token) {
            $hashedToken = hash('sha256', $token);
            $user = User::where('cookie_value', $hashedToken)->first();

            if ($user) {
                $user->cookie_value = null;
                $user->save();
            }
        }
        
        // Xóa tất cả device token của user (nếu có user từ cookie hoặc từ Auth)
        $userId = $user ? $user->id : (Auth::check() ? Auth::id() : null);
        if ($userId) {
            UserDeviceToken::where('user_id', $userId)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        }
        
        Auth::logout();
        session()->flush();
        $request->session()->invalidate(); // Hủy session hiện tại
        $request->session()->regenerateToken(); // Tạo lại CSRF token
        Cookie::queue(Cookie::forget('remember_token'));
        Cookie::queue(Cookie::forget('device_token'));
    }

    public function logout(Request $request)
    {
        $this->performLogout($request);
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
        
        // Chặn việc gửi device_token từ request body (chỉ cho phép từ cookie)
        if ($request->has('device_token') || $request->has('device_fingerprint')) {
            return response()->json([
                'success' => false,
                'message' => 'Không được phép thay đổi thông tin thiết bị từ request.'
            ], 403);
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
            $rules['new_password'] = ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'];
            $rules['confirm_password'] = 'required|string|same:new_password';
            
            $messages['current_password.required'] = 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.';
            $messages['new_password.required'] = 'Vui lòng nhập mật khẩu mới.';
            $messages['new_password.min'] = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
            $messages['new_password.regex'] = 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm cả chữ cái và số.';
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

            // Kiểm tra mật khẩu có ít nhất 8 ký tự, bao gồm chữ cái và số
            $newPassword = $request->new_password;
            if (strlen($newPassword) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'
                ], 422);
            }

            if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm cả chữ cái và số.'
                ], 422);
            }

            // Cập nhật mật khẩu
            $user->password = md5($request->new_password);
            $user->password_changed_at = now();
        }

        $user->save();

        $message = 'Cập nhật thông tin thành công!';
        $shouldLogout = false;
        
        if ($request->filled('new_password')) {
            $message = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại với mật khẩu mới.';
            $shouldLogout = true;
            
            // Logout user sau khi đổi mật khẩu (tái sử dụng function logout)
            $this->performLogout($request);
        }

        $response = response()->json([
            'success' => true,
            'message' => $message,
            'logout_required' => $shouldLogout
        ]);
        
        // Nếu cần logout, xóa cookie device_token
        if ($shouldLogout) {
            $response->cookie(Cookie::forget('device_token'));
            $response->cookie(Cookie::forget('remember_token'));
        }
        
        return $response;
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
