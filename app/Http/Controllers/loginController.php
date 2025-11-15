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
    public function Index()
    {
        return view("login");
    }
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'device_fingerprint' => 'nullable|string',
            'browser_info' => 'nullable|json'
        ]);
        
        $user = User::where('password', md5($request->password))->first();
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
            $user->save();
            Cookie::queue('remember_token', $token, $minutes);
            
            // Device token (mới)
            if (isset($deviceToken)) {
                Cookie::queue('device_token', $deviceToken, $minutes);
            }
            
            return redirect()->intended('/');
        }
        return back()->withErrors(['password' => 'Mật khẩu không chính xác'])->withInput();
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
}
