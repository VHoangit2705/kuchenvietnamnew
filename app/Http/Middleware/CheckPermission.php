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
    
    public function handle(Request $request, Closure $next, $permission): Response
    {
        /** @var \App\Models\KyThuat\User $user */
        $user = Auth::user();
        if (!$user || !$user->hasPermission($permission)) {
            // abort(403, 'Bạn không có quyền truy cập chức năng này. Liên Hệ bộ phận kỹ thuật để được cấp quyền');
            return response()->view(
                'errors.forbidden',
                ['message' => 'Bạn không có quyền truy cập chức năng này. Liên Hệ bộ phận kỹ thuật để được cấp quyền'],
                403
            );
        }

        return $next($request);
    }
}
