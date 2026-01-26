<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KyThuat\WarrantyUploadError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Warranty_Upload_Error_Controller extends Controller
{
    public function GetVideosError()
    {
        $data = WarrantyUploadError::select('id', 'warranty_request_id', 'video_upload_error', 'note_error')
            ->whereNotNull('video_upload_error')
            ->whereRaw('TRIM(video_upload_error) <> ""')
            ->where('video_upload_error', 'LIKE', '%videos/%')
            ->where('video_upload_error', 'NOT LIKE', '%uploads%')
            ->whereRaw("video_upload_error NOT LIKE 'https://%'")
            ->whereRaw("video_upload_error NOT LIKE '%drive.google.com%'")
            ->limit(3)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function UpdateVideosError(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', Rule::exists('warranty_upload_error', 'id')],
            'video_path' => 'required|string',
            'video_url' => 'required|string',
        ]);

        $id = $request->input('id');
        $videoPath = $request->input('video_path');
        $videoUrl = $request->input('video_url');

        if (empty($videoUrl) || !str_contains($videoUrl, 'drive.google.com')) {
            return response()->json([
                'success' => false,
                'message' => 'URL không hợp lệ hoặc chưa upload lên Drive',
            ], 400);
        }

        $record = WarrantyUploadError::find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'LỖI CẬP NHẬT',
            ], 400);
        }

        if (!empty($record->video_upload_error) && str_contains($record->video_upload_error, 'drive.google.com')) {
            return response()->json([
                'success' => false,
                'message' => 'Video này đã được xử lý trước đó',
            ], 400);
        }

        $record->video_upload_error = $videoUrl;
        $record->save();

        if ($videoPath && Storage::disk('public')->exists($videoPath)) {
            Storage::disk('public')->delete($videoPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'CẬP NHẬT THÀNH CÔNG',
        ]);
    }

    public function GetImagesError()
    {
        $data = WarrantyUploadError::selectRaw('id, warranty_request_id, image_upload_error as image_upload, note_error')
            ->whereNotNull('image_upload_error')
            ->whereRaw('TRIM(image_upload_error) <> ""')
            ->where('image_upload_error', 'LIKE', '%photos/%')
            ->where('image_upload_error', 'NOT LIKE', '%uploads%')
            ->whereRaw("image_upload_error NOT LIKE 'https://%'")
            ->whereRaw("image_upload_error NOT LIKE '%drive.google.com%'")
            ->limit(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function UpdateImagesError(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', Rule::exists('warranty_upload_error', 'id')],
            'image_path' => 'required|string',
            'image_url' => 'required|string',
        ]);

        $id = $request->input('id');
        $imagePath = $request->input('image_path');
        $imageUrl = $request->input('image_url');

        if (empty($imageUrl) || !str_contains($imageUrl, 'drive.google.com')) {
            return response()->json([
                'success' => false,
                'message' => 'URL không hợp lệ hoặc chưa upload lên Drive',
            ], 400);
        }

        $record = WarrantyUploadError::find($id);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'LỖI CẬP NHẬT',
            ], 400);
        }

        if (!empty($record->image_upload_error) && str_contains($record->image_upload_error, 'drive.google.com')) {
            return response()->json([
                'success' => false,
                'message' => 'Ảnh này đã được xử lý trước đó',
            ], 400);
        }

        $record->image_upload_error = $imageUrl;
        $record->save();

        if ($imagePath) {
            $paths = array_map('trim', explode(',', $imagePath));
            foreach ($paths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'CẬP NHẬT THÀNH CÔNG',
        ]);
    }
}
