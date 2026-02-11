<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KyThuat\CommonError;
use App\Models\KyThuat\TechnicalDocument;
use App\Models\Kho\Category;
use App\Models\Kho\ProductModel;

class CommonErrorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::where('website_id', 2)->get();

        $modelId = (int) $request->get('model_id');
        $productId = (int) $request->get('product_id');
        $xuatXu = $request->get('xuat_xu');

        $errors = collect();
        $productModel = null;
        $filter = [
            'category_id' => $request->get('category_id', ''),
            'product_id' => $productId ?: '',
            'xuat_xu' => $xuatXu ?: ''
        ];

        // If no model_id but we have product and origin, find the first model
        if (!$modelId && $productId && $xuatXu) {
            $productModel = ProductModel::with('product')->where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->first();
            if ($productModel) {
                $modelId = $productModel->id;
            }
        }

        if ($productId && $xuatXu) {
            if (!$productModel) {
                $productModel = ProductModel::with('product')->where('product_id', $productId)
                    ->where('xuat_xu', $xuatXu)
                    ->first();
            }

            $errors = CommonError::where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->orderBy('error_code')
                ->get();

            if ($productModel) {
                $filter = [
                    'category_id' => $productModel->product->category_id ?? '',
                    'product_id' => $productId,
                    'xuat_xu' => $xuatXu,
                ];
            }
        }

        return view('technicaldocument.error.index', compact('categories', 'errors', 'productModel', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $productId = (int) $request->get('product_id');
        $xuatXu = $request->get('xuat_xu');
        $productModel = null;
        if ($productId && $xuatXu) {
            $productModel = ProductModel::with('product')
                ->where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->first();
        }

        $categories = Category::where('website_id', 2)->get();

        return view('technicaldocument.error.create', compact('productModel', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:mysql3.products,id',
            'xuat_xu' => 'required|string',
            'error_code' => 'required|string|max:100',
            'error_name' => 'required|string|max:255',
            'severity' => 'required|in:normal,common,critical',
            'description' => 'nullable|string',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'xuat_xu.required' => 'Vui lòng chọn xuất xứ.',
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $exists = CommonError::where('product_id', $request->product_id)
            ->where('xuat_xu', $request->xuat_xu)
            ->where('error_code', $request->error_code)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error_code' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho sản phẩm này.'])->withInput();
        }

        CommonError::create([
            'product_id' => $request->product_id,
            'xuat_xu' => $request->xuat_xu,
            'error_code' => $request->error_code,
            'error_name' => $request->error_name,
            'severity' => $request->severity,
            'description' => $request->description,
        ]);

        return redirect()->route('warranty.document.errors.index', [
            'product_id' => $request->product_id,
            'xuat_xu' => $request->xuat_xu
        ])->with('success', 'Đã thêm mã lỗi thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $error = CommonError::findOrFail($id);
        $productModel = ProductModel::with('product')
            ->where('product_id', $error->product_id)
            ->where('xuat_xu', $error->xuat_xu)
            ->first();
        return view('technicaldocument.error.edit', compact('error', 'productModel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $error = CommonError::findOrFail($id);

        $request->validate([
            'error_code' => 'required|string|max:100',
            'error_name' => 'required|string|max:255',
            'severity' => 'required|in:normal,common,critical',
            'description' => 'nullable|string',
        ], [
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        // Check duplicate if code changed
        if ($request->error_code !== $error->error_code) {
            $exists = CommonError::where('product_id', $error->product_id)
                ->where('xuat_xu', $error->xuat_xu)
                ->where('error_code', $request->error_code)
                ->exists();
            if ($exists) {
                return back()->withErrors(['error_code' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại.'])->withInput();
            }
        }

        $error->update([
            'error_code' => $request->error_code,
            'error_name' => $request->error_name,
            'severity' => $request->severity,
            'description' => $request->description,
        ]);

        return redirect()->route('warranty.document.errors.index', [
            'product_id' => $error->product_id,
            'xuat_xu' => $error->xuat_xu
        ])
            ->with('success', 'Đã cập nhật mã lỗi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $error = CommonError::findOrFail($id);
        $error->delete();

        return response()->json(['message' => 'Đã xóa mã lỗi.']);
    }
}
