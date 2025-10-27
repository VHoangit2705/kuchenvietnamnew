<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'accountNo' => ['required', 'string', 'regex:/^\d{4,20}$/'],
            'bin' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $clientId = config('services.vietqr.client_id');
        $apiKey = config('services.vietqr.api_key');

        if (!$clientId || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu cấu hình VietQR (VIETQR_CLIENT_ID/VIETQR_API_KEY)'
            ], 200);
        }

        try {
            $response = Http::withHeaders([
                'x-client-id' => $clientId,
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.vietqr.io/v2/lookup', [
                'accountNumber' => $validated['accountNo'],
                'bin' => $validated['bin'],
            ]);

            if (!$response->ok()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tra cứu tài khoản (HTTP)'
                ], 200);
            }

            $data = $response->json();
            if (($data['code'] ?? '') !== '00') {
                return response()->json([
                    'success' => false,
                    'message' => $data['desc'] ?? 'Tra cứu thất bại',
                    'raw' => $data,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'accountName' => $data['data']['accountName'] ?? null,
                'raw' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            ], 200);
        }
    }
}


