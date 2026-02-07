<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDocsSubdomain
{
    public function handle(Request $request, Closure $next)
    {
        // Domain hợp lệ
        $allowedHost = 'docs.kuchenvietnam.vn';

        // Lấy host hiện tại
        $currentHost = $request->getHost();

        // Nếu không phải docs.kuchenvietnam.vn → 404
        if ($currentHost !== $allowedHost) {
            abort(404);
        }

        return $next($request);
    }
}
