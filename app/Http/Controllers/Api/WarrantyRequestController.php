<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\KyThuat\WarrantyRequest;
use App\Models\Kho\Product;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WarrantyRequestController extends Controller
{
    public function GetVideos()
    {
        $data = DB::table('warranty_requests')
            ->select('id', 'video_upload')
            ->whereNotNull('video_upload')
            ->whereRaw('TRIM(video_upload) <> ""')
            ->whereRaw("video_upload NOT LIKE 'https%'")
            ->whereRaw("video_upload NOT LIKE 'lỗi%'")
            ->limit(3)
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function UpdateVideos(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:warranty_requests,id',
            'video_path' => 'required|string',
            'video_url' => 'required|string',
        ]);

        $id = $request->input('id');
        $videoPath = $request->input('video_path');
        $videoUrl = $request->input('video_url');
        if($videoUrl && $id){
            $updated = DB::table('warranty_requests')
                ->where('id', $id)
                ->update(['video_upload' => $videoUrl]);
            // xử lý xoá file 
            if ($updated && $videoPath) {
                if (Storage::disk('public')->exists($videoPath)) {
                    Storage::disk('public')->delete($videoPath);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'CẬP NHẬT THÀNH CÔNG',
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'LỖI CẬP NHẬT'
        ], 400);
    }

    public function GetImages()
    {
        $data = DB::table('warranty_requests')
            ->select('id', 'image_upload')
            ->whereNotNull('image_upload')
            ->whereRaw('TRIM(image_upload) <> ""')
            ->where('image_upload', 'LIKE', '%photos/%')
            ->where('image_upload', 'NOT LIKE', '%uploads%')
            ->limit(5)
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function UpdateImages(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:warranty_requests,id',
            'image_path' => 'required|string',
            'image_url' => 'required|string',
        ]);

        $id = $request->input('id');
        $imagePath = $request->input('image_path');
        $imageUrl = $request->input('image_url');
        if($imageUrl && $id){
            $updated = DB::table('warranty_requests')
                ->where('id', $id)
                ->update(['image_upload' => $imageUrl]);
            // xử lý xoá file 
            if ($updated && $imagePath) {
                $paths = explode(',', $imagePath);
                foreach ($paths as $path) {
                    $path = trim($path);
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
        return response()->json([
            'success' => false,
            'message' => 'LỖI CẬP NHẬT'
        ], 400);
    }
    
    public function createWarranty(Request $request)
    {
        // $validProducts = Product::whereIn('view', [1, 3])->pluck('product_name')->toArray();
        $validProducts = Product::whereIn('view', [1, 3])
            ->pluck('product_name')->map(fn($name) => Str::lower(trim($name)))->toArray();
        $request->merge([
            'product' => Str::lower(trim($request->product))
        ]);
        $validated = $request->validate([
            'product' => [
                'required',
                'string',
                'max:255',
                Rule::in($validProducts),
            ],
            'serial_number'     => 'nullable|string|max:20',
            'serial_thanmay'    => 'nullable|string|max:20',
            'type'              => 'required|string|max:255',
            'full_name'         => 'required|string|max:255',
            'phone_number'      => 'required|string|max:15',
            'address'           => 'required|string',
            'staff_received'    => 'required|string',
            'branch'            => 'required|string',
            'shipment_date'     => 'required|date|before:today',
            'return_date'       => 'required|date|after_or_equal:today',
            'initial_fault_condition'       => 'nullable|required_unless:type,agent_component|string',
            'product_fault_condition'       => 'nullable|required_unless:type,agent_component|string',
            'product_quantity_description'  => 'nullable|required_unless:type,agent_component|string',
            'collaborator_phone'    => 'nullable|string',
            'collaborator_name'     => 'nullable|string',
            'collaborator_address'  => 'nullable|string',
        ]);
        $product = Product::where('product_name', $request->product)->select('month', 'view')->first();
        $data = $validated;
        $data['received_date'] = Carbon::today()->toDateString();
        $data['view'] = $product->view;
        $data['warranty_end'] = Carbon::parse($request->shipment_date)->addMonths($product->month)->toDateString();
        $warranty = WarrantyRequest::create($data);
        return response()->json([
            'status' => 'success',
            'data'    => $warranty
        ], 201);
    }
    
    public function getWarrantyRequestByPhoneNumber(Request $request){
        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^0\d{9}$/'
            ],
        ], [
            'phone_number.regex' => 'Phone number invalid',
        ]);

        $warranties = WarrantyRequest::select('id', 'serial_number', 'serial_thanmay', 'product', 'full_name', 'phone_number', 'address', 'staff_received', 'received_date', 'warranty_end', 'branch', 'return_date', 'shipment_date', 'initial_fault_condition', 'product_fault_condition', 'product_quantity_description', 'image_upload', 'video_upload', 'type', 'collaborator_name', 'collaborator_phone', 'collaborator_address', 'Ngaytao', 'status', 'view')
            ->with(['details' => function ($query) {
                $query->select('id', 'warranty_request_id', 'error_type', 'solution', 'replacement', 'replacement_price', 'quantity', 'unit_price', 'total', 'Ngaytao', 'edit_by');
            }])
            ->where('phone_number', $validated['phone_number'])
            ->orderByDesc('Ngaytao')
            ->get();

        if ($warranties->isEmpty()) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No warranty requests found for this phone number'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $warranties
        ]);
    }

    public function GetHuromPrducts(){
        $products = Product::where('view', 3)->select('id','product_name', 'image')->get();

        return response()->json([
            'status' => 'SUCCESS',
            'data' => $products
        ]);
    }
}
