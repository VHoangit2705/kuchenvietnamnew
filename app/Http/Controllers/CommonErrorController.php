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
        $errors = collect();
        $productModel = null;
        $filter = ['category_id' => '', 'product_id' => '', 'xuat_xu' => ''];

        if ($modelId) {
            $productModel = ProductModel::with('product')->find($modelId);
            if ($productModel) {
                $errors = CommonError::where('model_id', $modelId)
                    ->orderBy('error_code')
                    ->get();

                $filter = [
                    'category_id' => $productModel->product->category_id ?? '',
                    'product_id'  => $productModel->product_id,
                    'xuat_xu'     => $productModel->xuat_xu ?? '',
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
        $modelId = (int) $request->get('model_id');
        $productModel = null;
        if ($modelId) {
            $productModel = ProductModel::find($modelId);
        }
        
        // Pass categories for the filter in case user wants to change model context (though usually simpler to just fix the model)
        // For simplicity, we just assume entry from index with selected model, or we can load categories to select model.
        // Let's reuse the same filter structure if we want full flexibility, but for now strict to model.
        
        $categories = Category::where('website_id', 2)->get();
        
        return view('technicaldocument.error.create', compact('productModel', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'model_id'    => 'required|integer|exists:product_models,id',
            'error_code'  => 'required|string|max:100',
            'error_name'  => 'required|string|max:255',
            'severity'    => 'required|in:normal,common,critical',
            'description' => 'nullable|string',
        ], [
            'model_id.required'   => 'Vui lòng chọn model sản phẩm.',
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $exists = CommonError::where('model_id', $request->model_id)
            ->where('error_code', $request->error_code)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error_code' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho model này.'])->withInput();
        }

        CommonError::create([
            'model_id'    => $request->model_id,
            'error_code'  => $request->error_code,
            'error_name'  => $request->error_name,
            'severity'    => $request->severity,
            'description' => $request->description,
        ]);

        return redirect()->route('warranty.document.errors.index', ['model_id' => $request->model_id])
            ->with('success', 'Đã thêm mã lỗi thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $error = CommonError::with('productModel')->findOrFail($id);
        return view('technicaldocument.error.edit', compact('error'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $error = CommonError::findOrFail($id);
        
        $request->validate([
            'error_code'  => 'required|string|max:100',
            'error_name'  => 'required|string|max:255',
            'severity'    => 'required|in:normal,common,critical',
            'description' => 'nullable|string',
        ], [
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        // Check duplicate if code changed
        if ($request->error_code !== $error->error_code) {
            $exists = CommonError::where('model_id', $error->model_id)
                ->where('error_code', $request->error_code)
                ->exists();
            if ($exists) {
                return back()->withErrors(['error_code' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại.'])->withInput();
            }
        }

        $error->update([
            'error_code'  => $request->error_code,
            'error_name'  => $request->error_name,
            'severity'    => $request->severity,
            'description' => $request->description,
        ]);

        return redirect()->route('warranty.document.errors.index', ['model_id' => $error->model_id])
            ->with('success', 'Đã cập nhật mã lỗi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $error = CommonError::findOrFail($id);
        $modelId = $error->model_id;
        $error->delete();

        return response()->json(['message' => 'Đã xóa mã lỗi.']);
    }
}
