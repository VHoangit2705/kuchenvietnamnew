<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Kho\Product;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\District;
use App\Models\KyThuat\Wards;

class ProductController extends Controller
{
    public function getProduct(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'view'    => 'nullable|integer',
                'product' => 'nullable|string|max:255',
            ],
            [
                'view.integer'   => 'View must be integer.',
                'product.string' => 'Product must be a string.',
                'product.max'    => 'Product must not exceed 255 characters.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $products = Product::select('product_name', 'price', 'month', 'view')
            ->when($request->filled('view'), fn($q) => $q->where('view', $request->view))
            ->when($request->filled('product'), fn($q) => $q->where('product_name', 'like', "%{$request->product}%"))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    public function getLstProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'view' => 'required|integer',
        ], [
            'view.required' => 'Trường view là bắt buộc.',
            'view.integer'  => 'Giá trị view phải là một số nguyên.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        $view = $request->input('view');
    
        $products = Product::select('product_name', 'price', 'month')
            ->where('view', $view)
            ->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    public function getDistrictByIdProvince(Request $request)
    {
        // try {
        //     $provinceId = $request->province_id;
        //     $province = Province::findOrFail($provinceId);

        //     return response()->json([
        //         'status' => 'success',
        //         'data'   => $province
        //     ]);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => $e->getMessage()
        //     ], 500);
        // }
        try {
            $provinceId = $request->province_id;
            $province = Wards::findOrFail($provinceId);

            return response()->json([
                'status' => 'success',
                'data'   => $province
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getWardByIdDistrict(Request $request)
    {
        try {
            $districtId = $request->district_id;
            $district = District::with('wards')->findOrFail($districtId);

            return response()->json([
                'status' => 'success',
                'data'   => $district->wards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
