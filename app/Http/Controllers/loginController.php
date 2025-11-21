<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KyThuat\User;
use App\Models\KyThuat\UserDeviceToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoginController extends Controller
{
    private const MAX_APPROVED_DEVICES = 2;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 60; // 60 phút = 1 giờ
    
    private function User(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    public function Index(Request $request)
    {
        // 🔵 FIX: Nếu user đã đăng nhập thì chuyển thẳng vào trang chủ
    if (Auth::check()) {
        return redirect()->intended('/');
    }
        // Kiểm tra nếu có username trong old input và tài khoản bị khóa
        $username = old('username');
        if ($username) {
            // Kiểm tra khóa do sai mật khẩu
            $lockoutKey = "login_lockout_{$username}";
            $lockoutUntilValue = Cache::get($lockoutKey);
            
            if ($lockoutUntilValue) {
                $lockoutUntil = $lockoutUntilValue instanceof Carbon ? $lockoutUntilValue : Carbon::parse($lockoutUntilValue);
                
                if (now()->lt($lockoutUntil)) {
                    return view("login")->with([
                        'account_locked' => true,
                        'lockout_until' => $lockoutUntil->timestamp
                    ]);
                }
            }
            
            // Kiểm tra khóa do spam device limit
            $deviceLimitLockoutKey = "device_limit_lockout_{$username}";
            $deviceLimitLockoutUntilValue = Cache::get($deviceLimitLockoutKey);
            
            if ($deviceLimitLockoutUntilValue) {
                $deviceLimitLockoutUntil = $deviceLimitLockoutUntilValue instanceof Carbon ? $deviceLimitLockoutUntilValue : Carbon::parse($deviceLimitLockoutUntilValue);
                
                if (now()->lt($deviceLimitLockoutUntil)) {
                    return view("login")->with([
                        'account_locked' => true,
                        'lockout_until' => $deviceLimitLockoutUntil->timestamp
                    ]);
                }
            }
        }
        
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
            'machine_id' => 'required|string|max:255',
            'browser_info' => 'nullable|json'
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.regex' => 'Tên đăng nhập không được chứa dấu tiếng Việt và không được có dấu cách. Chỉ cho phép chữ cái, số, dấu gạch dưới (_) và dấu gạch ngang (-).',
            'password.required' => 'Vui lòng nhập mật khẩu.'
        ]);

        $username = $request->username;
        
        // Kiểm tra xem tài khoản có bị khóa do sai mật khẩu không
        $lockoutKey = "login_lockout_{$username}";
        $lockoutUntilValue = Cache::get($lockoutKey);
        
        if ($lockoutUntilValue) {
            $lockoutUntil = $lockoutUntilValue instanceof Carbon ? $lockoutUntilValue : Carbon::parse($lockoutUntilValue);
            
            if (now()->lt($lockoutUntil)) {
                $totalSeconds = now()->diffInSeconds($lockoutUntil, false);
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                
                $timeString = '';
                if ($hours > 0) {
                    $timeString = "{$hours} giờ {$minutes} phút {$seconds} giây";
                } elseif ($minutes > 0) {
                    $timeString = "{$minutes} phút {$seconds} giây";
                } else {
                    $timeString = "{$seconds} giây";
                }
                
                return back()->withErrors([
                    'msg' => "Tài khoản đã bị khóa do nhập sai mật khẩu quá nhiều lần. Vui lòng thử lại sau {$timeString}."
                ])->withInput()->with([
                    'account_locked' => true,
                    'lockout_until' => $lockoutUntil->timestamp
                ]);
            }
        }
        
        // Kiểm tra xem tài khoản có bị khóa do spam device limit không
        $deviceLimitLockoutKey = "device_limit_lockout_{$username}";
        $deviceLimitLockoutUntilValue = Cache::get($deviceLimitLockoutKey);
        
        if ($deviceLimitLockoutUntilValue) {
            $deviceLimitLockoutUntil = $deviceLimitLockoutUntilValue instanceof Carbon ? $deviceLimitLockoutUntilValue : Carbon::parse($deviceLimitLockoutUntilValue);
            
            if (now()->lt($deviceLimitLockoutUntil)) {
                $totalSeconds = now()->diffInSeconds($deviceLimitLockoutUntil, false);
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                
                $timeString = '';
                if ($hours > 0) {
                    $timeString = "{$hours} giờ {$minutes} phút {$seconds} giây";
                } elseif ($minutes > 0) {
                    $timeString = "{$minutes} phút {$seconds} giây";
                } else {
                    $timeString = "{$seconds} giây";
                }
                
                return back()->withErrors([
                    'msg' => "Tài khoản đã bị khóa do cố gắng đăng nhập quá nhiều lần khi đạt giới hạn thiết bị. Vui lòng thử lại sau {$timeString}."
                ])->withInput()->with([
                    'account_locked' => true,
                    'lockout_until' => $deviceLimitLockoutUntil->timestamp
                ]);
            }
        }

        $user = User::where('username', $username)
                    ->where('password', md5($request->password))
                    ->first();

        if ($user) {
            // Đăng nhập thành công - xóa số lần thử sai mật khẩu
            $attemptsKey = "login_attempts_{$username}";
            Cache::forget($attemptsKey);
            Cache::forget($lockoutKey);
            
            $machineId = trim((string) $request->input('machine_id'));
            $browserInfo = $this->normalizeBrowserInfo($request->input('browser_info'));
            $ipAddress = $request->ip();

            if (!$machineId) {
                $machineId = $this->generateFallbackMachineId($request);
            }

            $deviceResult = $this->handleDeviceAuthorization($user, $machineId, $ipAddress, $browserInfo);

            if ($deviceResult['status'] === 'error') {
                // Kiểm tra nếu là lỗi "đạt giới hạn 2 thiết bị" - đếm số lần spam
                $isDeviceLimitError = strpos($deviceResult['message'], 'đạt giới hạn 2 thiết bị') !== false;
                
                if ($isDeviceLimitError) {
                    // Đếm số lần spam device limit (giống logic nhập sai mật khẩu)
                    $deviceLimitAttemptsKey = "device_limit_attempts_{$username}";
                    $deviceLimitAttempts = Cache::get($deviceLimitAttemptsKey, 0);
                    $deviceLimitAttempts++;
                    
                    $deviceLimitRemainingAttempts = self::MAX_LOGIN_ATTEMPTS - $deviceLimitAttempts;
                    
                    if ($deviceLimitAttempts >= self::MAX_LOGIN_ATTEMPTS) {
                        // Khóa tài khoản trong 1 giờ do spam device limit
                        $deviceLimitLockoutKey = "device_limit_lockout_{$username}";
                        $deviceLimitLockoutUntil = now()->addMinutes(self::LOCKOUT_DURATION);
                        Cache::put($deviceLimitLockoutKey, $deviceLimitLockoutUntil, now()->addMinutes(self::LOCKOUT_DURATION + 5));
                        Cache::forget($deviceLimitAttemptsKey);
                        
                        $totalSeconds = now()->diffInSeconds($deviceLimitLockoutUntil, false);
                        $hours = floor($totalSeconds / 3600);
                        $minutes = floor(($totalSeconds % 3600) / 60);
                        $seconds = $totalSeconds % 60;
                        
                        $timeString = '';
                        if ($hours > 0) {
                            $timeString = "{$hours} giờ {$minutes} phút {$seconds} giây";
                        } elseif ($minutes > 0) {
                            $timeString = "{$minutes} phút {$seconds} giây";
                        } else {
                            $timeString = "{$seconds} giây";
                        }
                        
                        return back()->withErrors([
                            'msg' => "Bạn đã cố gắng đăng nhập quá nhiều lần khi đạt giới hạn thiết bị. Tài khoản đã bị khóa trong 1 giờ. Vui lòng thử lại sau {$timeString}."
                        ])->withInput()->with([
                            'account_locked' => true,
                            'lockout_until' => $deviceLimitLockoutUntil->timestamp
                        ]);
                    }
                    
                    // Lưu số lần spam (hết hạn sau 2 giờ để tránh tích lũy)
                    Cache::put($deviceLimitAttemptsKey, $deviceLimitAttempts, now()->addHours(2));
                    
                    return back()->withErrors([
                        'msg' => $deviceResult['message']
                    ])->withInput()->with([
                        'device_limit_warning' => true,
                        'remaining_attempts' => $deviceLimitRemainingAttempts,
                        'failed_attempts' => $deviceLimitAttempts
                    ]);
                }
                
                return back()->withErrors([
                    'password' => $deviceResult['message']
                ])->withInput();
            }
            
            // Đăng nhập thành công - xóa số lần spam device limit
            $deviceLimitAttemptsKey = "device_limit_attempts_{$username}";
            $deviceLimitLockoutKey = "device_limit_lockout_{$username}";
            Cache::forget($deviceLimitAttemptsKey);
            Cache::forget($deviceLimitLockoutKey);

            $browserToken = $deviceResult['browser_token'];

            Auth::login($user);
            $minutes = 60 * 24 * 7; // 7 ngày
            session([
                'user' => $user->full_name,
                'zone' => $user->zone, // chi nhánh
                'position' => $user->position, // chức vụ
            ]);
            
            // Remember token
            $token = Str::random(60);
            $user->cookie_value = hash('sha256', $token);
            if (!$user->password_changed_at) {
                $user->password_changed_at = now();
            }
            $user->save();
            Cookie::queue('remember_token', $token, $minutes);

            Cookie::queue('browser_token', $browserToken, $minutes);
            Cookie::queue('machine_id', $machineId, $minutes);
            
            return redirect()->intended('/');
        }
        
        // Đăng nhập thất bại - tăng số lần thử sai
        $attemptsKey = "login_attempts_{$username}";
        $attempts = Cache::get($attemptsKey, 0);
        $attempts++;
        
        $remainingAttempts = self::MAX_LOGIN_ATTEMPTS - $attempts;
        
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            // Khóa tài khoản trong 1 giờ
            $lockoutUntil = now()->addMinutes(self::LOCKOUT_DURATION);
            Cache::put($lockoutKey, $lockoutUntil, now()->addMinutes(self::LOCKOUT_DURATION + 5));
            Cache::forget($attemptsKey);
            
            return back()->withErrors([
                'msg' => 'Bạn đã nhập sai mật khẩu quá 5 lần. Tài khoản đã bị khóa trong 1 giờ.'
            ])->withInput()->with([
                'account_locked' => true,
                'lockout_until' => $lockoutUntil->timestamp
            ]);
        }
        
        // Lưu số lần thử sai (hết hạn sau 2 giờ để tránh tích lũy)
        Cache::put($attemptsKey, $attempts, now()->addHours(2));
        
        return back()->withErrors([
            'msg' => 'Tên đăng nhập hoặc mật khẩu không chính xác'
        ])->withInput()->with([
            'remaining_attempts' => $remainingAttempts,
            'failed_attempts' => $attempts
        ]);
    }
    /** Hàm xác định loại thiết bị truy cập **/
    private function detectDeviceType(): string
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

    if (strpos($agent, 'mobile') !== false || 
        strpos($agent, 'android') !== false ||
        strpos($agent, 'iphone') !== false ||
        strpos($agent, 'ipad') !== false) {
        return 'mobile';
    }

    return 'pc';
}
/** Hàm kiểm tra giới hạn theo loại thiết bị **/

private function hasReachedDeviceLimitByType(int $userId, string $deviceType): bool

{
    return UserDeviceToken::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('device_type', $deviceType)
            ->count() >= 1;
}

    /**
     * Xử lý cấp quyền truy cập thiết bị dựa trên MACHINE_ID.
     */
   private function handleDeviceAuthorization(User $user, string $machineId, string $ipAddress, ?string $browserInfo): array
{
    try {
        Log::info("LOGIN DEBUG – user_id=".$user->id." – machine_id=".$machineId);

        // Xác định loại thiết bị: pc / mobile
        $deviceType = $this->detectDeviceType();

       // 1) LẤY TẤT CẢ RECORD CÓ fingerprint GIỐNG machineId
$deviceMatches = UserDeviceToken::where('device_fingerprint', $machineId)->get();

// CLEANUP – mất record rác user khác (nếu có)
UserDeviceToken::where('device_fingerprint', $machineId)
    ->where('user_id', '!=', $user->id)
    ->delete();


$deviceMatches = UserDeviceToken::where('device_fingerprint', $machineId)->get();
// 🔎 TEST LOG SO SÁNH user_id ĐỂ BẮT LỖI
foreach ($deviceMatches as $d) {
    Log::info("COMPARE – d.user_id=" . gettype($d->user_id) . " " . $d->user_id .
        " | user.id=" . gettype($user->id) . " " . $user->id .
        " | cmp=" . (( $d->user_id !== $user->id ) ? 'TRUE' : 'FALSE')
    );
}
// 2) Block only if another APPROVED user owns this device
$deviceOfOtherUser = $deviceMatches->first(function ($d) use ($user) {
    return (int)$d->user_id !== (int)$user->id
        && $d->status === 'approved';
});


if ($deviceOfOtherUser) {
    return [
        'status' => 'error',
        'message' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác.'
    ];
}



        // ========================================================
        // 🔵 3) LẤY RECORD THUỘC USER HIỆN TẠI (NẾU CÓ)
        // ========================================================
        // (A) ONLY approved device (fix lỗi pending bị lẫn vào)
        $deviceOfUser = $deviceMatches->first(function ($d) use ($user) {
            return $d->user_id == $user->id &&
                   $d->status === 'approved';     // ❗ LOẠI PENDING
        });

        // (B) Reactivable device (approved + inactive)
        $reactivableDevice = $deviceMatches->first(function ($d) use ($user) {
            return $d->user_id == $user->id &&
                   !$d->is_active &&
                   $d->status === 'approved';
        });

        // (C) Pending device
        $pendingDevice = $deviceMatches->first(function ($d) use ($user) {
            return $d->user_id == $user->id &&
                   $d->status === 'pending';
        });

        // ========================================================
        // 🔵 4) ƯU TIÊN XỬ LÝ
        //    (1) reactivate → (2) device cũ → (3) pending → (4) thiết bị mới
        // ========================================================

        if ($reactivableDevice) {
            $device = $reactivableDevice;
        }
        elseif ($deviceOfUser) {
            $device = $deviceOfUser;
        }
        elseif ($pendingDevice) {
            return [
                'status' => 'error',
                'message' => 'Bạn đã đạt giới hạn thiết bị. Thiết bị mới đang chờ Admin duyệt.'
            ];
        }
        else {

            // ========================================================
            // 🔵 5) KIỂM TRA GIỚI HẠN PC / MOBILE (MỖI LOẠI CHỈ 1)
            // ========================================================
            if ($this->hasReachedDeviceLimitByType($user->id, $deviceType)) {

                // Tạo pending device mới
                $this->createPendingDevice($user, $machineId, $ipAddress, $browserInfo, $deviceType);

                return [
                    'status' => 'error',
                    'message' => 'Bạn đã đạt giới hạn thiết bị. Thiết bị mới đang chờ Admin duyệt.'
                ];
            }

            // ========================================================
            // 🔵 6) TẠO DEVICE MỚI (APPROVED)
            // ========================================================
            $device = UserDeviceToken::create([
                'user_id'           => $user->id,
                'device_fingerprint'=> $machineId,
                'device_token'      => hash('sha256', Str::random(60)),
                'ip_address'        => $ipAddress,
                'browser_info'      => $browserInfo,
                'device_type'       => $deviceType,
                'is_active'         => 1,
                'status'            => 'approved',
                'last_used_at'      => now(),
            ]);
        }

        // ========================================================
        // 🔵 7) CẤP BROWSER TOKEN (CẬP NHẬT ACTIVE)
        // ========================================================
        $browserToken = $this->issueBrowserToken($device, $ipAddress, $browserInfo);

        return [
            'status' => 'ok',
            'browser_token' => $browserToken,
        ];
    }
    catch (\Exception $e) {

        Log::error('Error authorizing device: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => 'Thiết bị chưa được cấp phép cho tài khoản này.'
        ];
    }
}

    private function issueBrowserToken(UserDeviceToken $device, string $ipAddress, ?string $browserInfo): string
    {
        $token = Str::random(80);
        $hashedToken = hash('sha256', $token);

        $device->update([
            'device_token' => $hashedToken,
            'ip_address' => $ipAddress,
            'browser_info' => $browserInfo,
            'is_active' => 1,
            'status' => 'approved',
            'approval_requested_at' => null,
            'last_used_at' => now(),
        ]);

        return $token;
    }

    private function createPendingDevice(User $user, string $machineId, string $ipAddress, ?string $browserInfo, string $deviceType): void
{
    UserDeviceToken::updateOrCreate(
        ['device_fingerprint' => $machineId],
        [
            'user_id' => $user->id,
            'device_type' => $deviceType,
            'device_token' => hash('sha256', Str::random(60)),
            'ip_address' => $ipAddress,
            'browser_info' => $browserInfo,
            'is_active' => 0,
            'status' => 'pending',
            'approval_requested_at' => now(),
            'last_used_at' => null,
        ]
    );
}


    private function hasReachedDeviceLimit(int $userId): bool
    {
        return UserDeviceToken::where('user_id', $userId)
            ->where('status', 'approved')
            ->count() >= self::MAX_APPROVED_DEVICES;
    }

    /**
     * Chuẩn hoá browser info thành JSON để lưu DB
     */
    private function normalizeBrowserInfo($browserInfo): ?string
    {
        if (!$browserInfo) {
            return null;
        }

        if (is_string($browserInfo)) {
            $decoded = json_decode($browserInfo, true);
            return $decoded ? json_encode($decoded) : $browserInfo;
        }

        return json_encode($browserInfo);
    }

    /**
     * Tạo MACHINE_ID dự phòng khi client không gửi lên.
     */
    private function generateFallbackMachineId(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent(),
            $request->ip(),
            Str::uuid()->toString(),
        ]));
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
        Cookie::queue(Cookie::forget('browser_token'));
        Cookie::queue(Cookie::forget('machine_id'));
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
        
        // Chặn việc gửi thông tin thiết bị giả mạo từ request body
        if ($request->has('browser_token') || $request->has('machine_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Không được phép thay đổi thông tin thiết bị từ request.'
            ], 403);
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
            
            // Logout user sau khi đổi mật khẩu (tái sử dụng function logout)
            $this->performLogout($request);
        }

        $response = response()->json([
            'success' => true,
            'message' => $message,
            'logout_required' => $shouldLogout
        ]);
        
        // Nếu cần logout, xóa cookie liên quan đến thiết bị
        if ($shouldLogout) {
            $response->cookie(Cookie::forget('browser_token'));
            $response->cookie(Cookie::forget('machine_id'));
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
