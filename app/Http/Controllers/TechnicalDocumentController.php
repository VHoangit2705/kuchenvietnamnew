<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Kho\Category;
use App\Models\Kho\Product;
use App\Models\Kho\ProductModel;
use App\Models\KyThuat\CommonError;
use App\Models\KyThuat\RepairGuide;
use App\Models\KyThuat\TechnicalDocument;
use App\Models\KyThuat\DocumentVersion;
use App\Models\KyThuat\RepairGuideDocument;

class TechnicalDocumentController extends Controller
{
    public function index()
    {
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.create', compact('categories'));
    }

    // 1. Sản phẩm theo danh mục (dùng model Product đã định nghĩa)
    public function getProductsByCategory(Request $request)
    {
        $categoryId = (int) $request->get('category_id');
        if (!$categoryId) {
            return response()->json([]);
        }

        $products = Product::getProductsByCategoryId($categoryId, 1);
        return response()->json($products);
    }

    // 2. Xuất xứ theo sản phẩm
    public function getOriginsByProduct(Request $request)
    {
        return ProductModel::where('product_id', $request->product_id)
            ->select('xuat_xu')
            ->distinct()
            ->get();
    }

    // 3. Model theo xuất xứ
    public function getModelsByOrigin(Request $request)
    {
        return ProductModel::where('product_id', $request->product_id)
            ->where('xuat_xu', $request->xuat_xu)
            ->get(['id', 'model_code', 'version']);
    }

    // 4. Danh sách mã lỗi theo model (Bước 5)
    public function getErrorsByModel(Request $request)
    {
        $modelId = (int) $request->get('model_id');
        if (!$modelId) {
            return response()->json([]);
        }

        $errors = CommonError::where('model_id', $modelId)
            ->orderBy('error_code')
            ->get(['id', 'error_code', 'error_name', 'severity', 'description']);

        return response()->json($errors);
    }

    // 5. Thêm mã lỗi kỹ thuật (Bước 5)
    public function storeError(Request $request)
    {
        $request->validate([
            'model_id'   => 'required|integer',
            'error_code' => 'required|string|max:100',
            'error_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity'   => 'nullable|in:normal,common,critical',
        ], [
            'model_id.required'   => 'Vui lòng chọn model sản phẩm.',
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $modelId = (int) $request->model_id;
        $exists = CommonError::where('model_id', $modelId)
            ->where('error_code', $request->error_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho model này.',
            ], 422);
        }

        CommonError::create([
            'model_id'    => $modelId,
            'error_code'  => $request->error_code,
            'error_name'  => $request->error_name,
            'description' => $request->description,
            'severity'    => $request->severity ?? 'normal',
        ]);

        return response()->json(['message' => 'Đã thêm mã lỗi thành công.']);
    }

    // 6–7. Thêm hướng dẫn sửa & gắn tài liệu (Bước 6–7)
    public function storeRepairGuide(Request $request)
    {
        $request->validate([
            'error_id'       => 'required|integer|exists:common_errors,id',
            'title'          => 'required|string|max:255',
            'steps'          => 'required|string',
            'estimated_time' => 'nullable|integer|min:0',
            'safety_note'    => 'nullable|string',
            'files'          => 'nullable|array',
            'files.*'        => 'file|mimes:pdf,jpg,jpeg,png,mp4,webm|max:20480',
        ], [
            'error_id.required' => 'Vui lòng chọn mã lỗi.',
            'title.required'    => 'Tiêu đề hướng dẫn không được để trống.',
            'steps.required'    => 'Các bước xử lý không được để trống.',
        ]);

        $error = CommonError::findOrFail($request->error_id);
        $modelId = $error->model_id;

        $guide = RepairGuide::create([
            'error_id'       => $request->error_id,
            'title'          => $request->title,
            'steps'          => $request->steps,
            'estimated_time' => (int) ($request->estimated_time ?? 0),
            'safety_note'    => $request->safety_note,
        ]);

        $uploadedFiles = $request->file('files');
        if (!empty($uploadedFiles)) {
            $disk = 'public';
            $basePath = 'technical_documents/' . date('Y/m/d');

            foreach ($uploadedFiles as $file) {
                $ext = strtolower($file->getClientOriginalExtension());
                $docType = match ($ext) {
                    'pdf' => 'manual',
                    'jpg', 'jpeg', 'png' => 'image',
                    'mp4', 'webm' => 'video',
                    default => 'repair',
                };

                $path = $file->store($basePath, $disk);

                $doc = TechnicalDocument::create([
                    'model_id'    => $modelId,
                    'doc_type'    => $docType,
                    'title'       => $file->getClientOriginalName(),
                    'description' => 'Đính kèm hướng dẫn sửa: ' . $guide->title,
                    'status'      => 'active',
                ]);

                DocumentVersion::create([
                    'document_id'  => $doc->id,
                    'version'      => '1.0',
                    'file_path'    => $path,
                    'file_type'    => $ext,
                    'status'       => 'active',
                    'uploaded_by'  => Auth::id(),
                ]);

                RepairGuideDocument::create([
                    'repair_guide_id' => $guide->id,
                    'document_id'     => $doc->id,
                ]);
            }
        }

        return response()->json(['message' => 'Đã lưu hướng dẫn và tài liệu đính kèm.']);
    }
}
