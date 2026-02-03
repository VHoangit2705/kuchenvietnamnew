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

    // Thêm xuất xứ sản phẩm (tạo product_model mới)
    public function storeOrigin(Request $request)
    {
        $request->validate([
            'product_id'   => 'required|integer',
            'xuat_xu'      => 'required|string|max:255',
            'model_code'   => 'required|string|max:100',
            'version'      => 'nullable|string|max:50',
            'release_year' => 'nullable|integer|min:1990|max:2100',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'xuat_xu.required'   => 'Xuất xứ không được để trống.',
            'model_code.required'=> 'Mã model không được để trống.',
        ]);

        $productId = (int) $request->product_id;
        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 422);
        }

        $modelCode = trim($request->model_code);
        $version = $request->filled('version') ? trim($request->version) : null;

        $exists = ProductModel::where('product_id', $productId)
            ->where('model_code', $modelCode)
            ->where(function ($q) use ($version) {
                if ($version === null || $version === '') {
                    $q->whereNull('version');
                } else {
                    $q->where('version', $version);
                }
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Mã model "' . $modelCode . '"' . ($version ? ' (phiên bản ' . $version . ')' : '') . ' đã tồn tại cho sản phẩm này.',
            ], 422);
        }

        ProductModel::create([
            'product_id'   => $productId,
            'xuat_xu'      => trim($request->xuat_xu),
            'model_code'   => $modelCode,
            'version'      => $version,
            'release_year' => $request->filled('release_year') ? (int) $request->release_year : null,
            'status'       => 'active',
        ]);

        return response()->json(['message' => 'Đã thêm xuất xứ thành công.']);
    }

    // Chi tiết lỗi: hướng dẫn sửa + tài liệu/ảnh/video đính kèm (cho modal Tra cứu)
    public function getErrorDetail(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId) {
            return response()->json(['error' => 'Thiếu error_id'], 400);
        }

        $error = CommonError::with([
            'repairGuides.technicalDocuments.documentVersions',
        ])->find($errorId);

        if (!$error) {
            return response()->json(['error' => 'Không tìm thấy mã lỗi'], 404);
        }

        $storageUrl = rtrim(asset('storage'), '/');

        $repairGuides = $error->repairGuides->map(function ($guide) use ($storageUrl) {
            $documents = [];
            foreach ($guide->technicalDocuments as $doc) {
                $version = $doc->documentVersions->sortByDesc('id')->first();
                if (!$version) {
                    continue;
                }
                $fileUrl = $storageUrl . '/' . ltrim($version->file_path, '/');
                $documents[] = [
                    'id'        => $doc->id,
                    'title'     => $doc->title,
                    'doc_type'  => $doc->doc_type,
                    'file_url'  => $fileUrl,
                    'file_type' => $version->file_type,
                ];
            }
            return [
                'id'             => $guide->id,
                'title'          => $guide->title,
                'steps'          => $guide->steps,
                'estimated_time' => $guide->estimated_time,
                'safety_note'    => $guide->safety_note,
                'documents'      => $documents,
            ];
        });

        return response()->json([
            'error'         => [
                'id'          => $error->id,
                'error_code'  => $error->error_code,
                'error_name'  => $error->error_name,
                'description' => $error->description,
                'severity'    => $error->severity,
            ],
            'repair_guides' => $repairGuides,
        ]);
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

        $error = CommonError::with('productModel')->findOrFail($request->error_id);
        $modelId = $error->model_id;
        $productModel = $error->productModel;

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

            // Lấy thông tin model để đặt tên file
            $modelCode = $productModel ? str_replace(' ', '_', $productModel->model_code) : 'MODEL';
            $modelVersion = $productModel && $productModel->version ? str_replace(' ', '_', $productModel->version) : '';
            $errorCode = str_replace(' ', '_', $error->error_code);

            foreach ($uploadedFiles as $index => $file) {
                $ext = strtolower($file->getClientOriginalExtension());
                $docType = match ($ext) {
                    'pdf' => 'manual',
                    'jpg', 'jpeg', 'png' => 'image',
                    'mp4', 'webm' => 'video',
                    default => 'repair',
                };

                // Tên file có cấu trúc: {model_code}_{error_code}_v{model_version}_{doc_type}_{timestamp}_{index}.{ext}
                $timestamp = time();
                $fileName = $modelCode . '_' . $errorCode;
                if ($modelVersion) {
                    $fileName .= '_v' . $modelVersion;
                }
                $fileName .= '_' . $docType . '_' . $timestamp . '_' . ($index + 1) . '.' . $ext;

                $path = $file->storeAs($basePath, $fileName, $disk);

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
