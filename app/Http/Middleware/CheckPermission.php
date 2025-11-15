<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    
    public function handle(Request $request, Closure $next, $permissionOrRole): Response
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

        // Kiểm tra nếu là role (có method hasRole) hoặc permission (có method hasPermission)
        $hasAccess = false;
        
        // Thử kiểm tra role trước
        if (method_exists($user, 'hasRole')) {
            $hasAccess = $user->hasRole($permissionOrRole);
        }
        
        // Nếu không có role, thử kiểm tra permission
        if (!$hasAccess && method_exists($user, 'hasPermission')) {
            $hasAccess = $user->hasPermission($permissionOrRole);
        }

        if (!$hasAccess) {
            return response()->view(
                'errors.forbidden',
                ['message' => 'Bạn không có quyền truy cập chức năng này. Liên Hệ bộ phận kỹ thuật để được cấp quyền'],
                403
            );
        }

        return $next($request);
    }
}