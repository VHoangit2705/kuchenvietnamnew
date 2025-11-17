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
            'username' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_-]+$/'
            ],
            'password' => 'required|string',
            'device_fingerprint' => 'nullable|string',
            'browser_info' => 'nullable|json'
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.regex' => 'Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).',
            'password.required' => 'Vui lòng nhập mật khẩu.'
        ]);

        $user = User::where('username', $request->username)
                    ->where('password', md5($request->password))
                    ->first();

            
        if ($user) {
            // Xử lý device token
            $deviceFingerprint = $request->input('device_fingerprint');
            $browserInfo = $request->input('browser_info');
            
            if ($deviceFingerprint) {
                // Kiểm tra device token trong cookie
                $existingToken = Cookie::get('device_token');
                
                if ($existingToken) {
                    // Kiểm tra token này thuộc về user nào
                    $tokenRecord = UserDeviceToken::where('device_token', hash('sha256', $existingToken))
                        ->where('is_active', 1)
                        ->first();
                    
                    if ($tokenRecord) {
                        // Token đã tồn tại và thuộc về user khác
                        if ($tokenRecord->user_id !== $user->id) {
                            return back()->withErrors([
                                'password' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác.Vui lòng sử dụng thiết bị khác.'
                            ])->withInput();
                        }
                        // Token thuộc về user này → Cập nhật last_used_at
                        $tokenRecord->update(['last_used_at' => now()]);
                        $deviceToken = $existingToken;
                    } else {
                        // Token không tồn tại trong DB → Tạo mới
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
        
        // Xóa device token
        $deviceToken = $request->cookie('device_token');
        if ($deviceToken && Auth::check()) {
            $hashedDeviceToken = hash('sha256', $deviceToken);
            UserDeviceToken::where('device_token', $hashedDeviceToken)
                ->where('user_id', Auth::id())
                ->update(['is_active' => 0]);
        }
        
        Auth::logout();
        session()->flush();
        $request->session()->invalidate(); // Hủy session hiện tại
        $request->session()->regenerateToken(); // Tạo lại CSRF token
        Cookie::queue(Cookie::forget('remember_token'));
        Cookie::queue(Cookie::forget('device_token'));
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
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/'
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/'
            ],
        ];

        $messages = [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.regex' => 'Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.regex' => 'Email không đúng định dạng.',
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
            
            // Logout user sau khi đổi mật khẩu
            $token = $request->cookie('remember_token');
            if ($token) {
                $hashedToken = hash('sha256', $token);
                $userToUpdate = User::where('cookie_value', $hashedToken)->first();
                if ($userToUpdate) {
                    $userToUpdate->cookie_value = null;
                    $userToUpdate->save();
                }
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'logout_required' => $shouldLogout
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
