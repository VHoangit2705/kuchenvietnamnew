<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  (Danh sách các role được phép, ví dụ: 'admin', 'editor'...)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        /** @var \App\Models\KyThuat\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->view(
                'errors.forbidden',
                ['message' => 'Bạn không có quyền truy cập chức năng này. Liên Hệ bộ phận kỹ thuật để được cấp quyền'],
                403
            );
        }

        // Eager load roles để tránh N+1 queries
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Kiểm tra xem user có tồn tại và có bất kỳ role nào trong danh sách $roles không
        // (Sử dụng hàm hasAnyRole chúng ta vừa tạo ở Bước 1)
        if (!$user->hasAnyRole($roles)) {

            // Nếu không có quyền, quay về trang chủ với thông báo lỗi
           return response()->view(
                'errors.forbidden',
                ['message' => 'Bạn không có quyền truy cập chức năng này. Liên Hệ bộ phận kỹ thuật để được cấp quyền'],
                403
            );

            // Hoặc bạn có thể hiển thị trang lỗi 403
            // abort(403, 'BẠN KHÔNG CÓ QUYỀN TRUY CẬP CHỨC NĂNG NÀY.');
        }

        return $next($request);
    }
}