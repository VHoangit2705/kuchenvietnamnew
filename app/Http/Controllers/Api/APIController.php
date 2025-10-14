<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class APIController extends Controller
{
    public function PostError(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào
        $validatedData = $request->validate([
            'yeucau' => 'required|string|max:255',
            'mota' => 'required|string',
            'hinh_anh.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            // Bắt đầu giao dịch
            DB::connection('mysql2')->beginTransaction();

            // Thêm thông tin lỗi vào bảng tech_support
            $data = [
                'error_name' => $validatedData['yeucau'],
                'description' => $validatedData['mota'],
                // 'create_by' => auth()->id() ?? 1,
                // 'update_by' => auth()->id() ?? 1,
                // 'update_at' => now(),
            ];

            $errorId = DB::connection('mysql2')->table('tech_support')->insertGetId($data);

            // Lưu các hình ảnh đã tải lên
            $uploadedFiles = [];
            if ($request->hasFile('hinh_anh')) {
                foreach ($request->file('hinh_anh') as $file) {
                    // Lưu hình ảnh vào thư mục public/error_imgs
                    $path = $file->store('error_imgs', 'public');
                    $uploadedFiles[] = $path;

                    // Thêm vào bảng tech_support_img
                    DB::connection('mysql2')->table('tech_support_img')->insert([
                        'id_error' => $errorId,
                        'error_img' => $path,
                    ]);
                }
            }

            DB::connection('mysql2')->commit();

            // Phản hồi thành công
            return response()->json([
                'status' => 'success',
                'message' => 'Yêu cầu đã được ghi nhận!',
                'error_id' => $errorId,
                'files' => $uploadedFiles,
            ], 200);
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
