<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\KyThuat\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function CreateToken()
    {
        $user = User::find(34);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User không tồn tại'
            ], 401);
        }
        
        $tokenResult = $user->createToken('api-token');
        return response()->json([
            'success' => true,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
    
    public function Login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $username = $request->input('username');
        $password = $request->input('password');

        $user = User::where('username', $username)
                    ->where('password', md5($password))
                    ->first();
                    
        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'user' => $user
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'
        ], 401);
        
    }
    
    public function getToken(Request $request)
    {
        $request->validate([
            'grant_type' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'scope' => 'required|string',
        ]);
        
        // Tạo 1 request nội bộ tới /oauth/token (không dùng cURL)
        $proxy = Request::create('/oauth/token', 'POST', [
            'grant_type'    => $request->grant_type,
            'client_id'     => $request->client_id,
            'client_secret' => $request->client_secret,
            'username'      => $request->username,
            'password'      => $request->password,
            'scope'         => $request->scope,
        ]);

        // Trả về y nguyên response JSON của Passport
        return app()->handle($proxy);

        // $response = Http::asForm()->post(url('/oauth/token'), [
        //     'grant_type' => 'password',
        //     'client_id' => $request->client_id,
        //     'client_secret' => $request->client_secret,
        //     'username' => $request->username,
        //     'password' => $request->password,
        //     'scope' => $request->scope,
        // ]);

        // if ($response->successful()) {
        //     return response()->json($response->json());
        // } else {
        //     return response()->json([
        //         'error' => 'Unauthorized',
        //         'message' => $response->json(),
        //     ], $response->status());
        // }
    }
}
