<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\KyThuat\UserDeviceToken;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ kiểm tra khi user đã đăng nhập
        if (!Auth::check()) {
            return $next($request);
        }

        $deviceToken = Cookie::get('device_token');
        
        if (!$deviceToken) {
            // Không có device token → Chặn
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiết bị không được xác thực. Vui lòng đăng nhập lại.'
                ], 403);
            }
            
            Auth::logout();
            session()->flush();
            return redirect()->route('login')->withErrors([
                'msg' => 'Thiết bị không được xác thực. Vui lòng đăng nhập lại.'
            ]);
        }

        // Kiểm tra token có hợp lệ không
        $hashedToken = hash('sha256', $deviceToken);
        $tokenRecord = UserDeviceToken::where('device_token', $hashedToken)
            ->where('is_active', 1)
            ->first();

        if (!$tokenRecord) {
            // Token không tồn tại hoặc đã bị vô hiệu hóa
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token thiết bị không hợp lệ. Vui lòng đăng nhập lại.'
                ], 403);
            }
            
            Auth::logout();
            session()->flush();
            Cookie::queue(Cookie::forget('device_token'));
            return redirect()->route('login')->withErrors([
                'msg' => 'Token thiết bị không hợp lệ. Vui lòng đăng nhập lại.'
            ]);
        }

        // Kiểm tra token có thuộc về user hiện tại không
        if ($tokenRecord->user_id !== Auth::id()) {
            // Token thuộc về user khác → Chặn
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác.'
                ], 403);
            }
            
            Auth::logout();
            session()->flush();
            Cookie::queue(Cookie::forget('device_token'));
            return redirect()->route('login')->withErrors([
                'msg' => 'Thiết bị này đã được đăng nhập bởi nhân viên khác.'
            ]);
        }

        // Cập nhật last_used_at
        $tokenRecord->update(['last_used_at' => now()]);

        return $next($request);
    }
}

