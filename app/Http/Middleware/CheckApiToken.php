<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class CheckApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !Str::startsWith($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Token missing'], 401);
        }

        $plainToken = Str::after($authHeader, 'Bearer ');
        $tokenModel = PersonalAccessToken::findToken($plainToken);

        if (!$tokenModel) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if (Carbon::parse($tokenModel->created_at)->addHour()->isPast()) {
            $tokenModel->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }

        $request->merge(['user' => $tokenModel->tokenable]);

        return $next($request);
    }
}
