<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;
use App\Models\Kho\Category;
use App\Models\Kho\Product;
use App\Models\Kho\ProductModel;
use App\Models\KyThuat\CommonError;
use App\Models\KyThuat\RepairGuide;
use App\Models\KyThuat\TechnicalDocument;
use App\Models\KyThuat\DocumentVersion;
use App\Models\KyThuat\RepairGuideDocument;
use App\Models\KyThuat\TechnicalDocumentAttachment;

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
            ->whereNotNull('xuat_xu')
            ->where('xuat_xu', '!=', '')
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
            'model_code'   => 'required|string',
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

        // Split model codes by comma and trim whitespace
        $modelCodesInput = trim($request->model_code);
        $modelCodes = array_map('trim', explode(',', $modelCodesInput));
        $modelCodes = array_filter($modelCodes); // Remove empty values

        if (empty($modelCodes)) {
            return response()->json(['message' => 'Mã model không được để trống.'], 422);
        }

        $version = $request->filled('version') ? trim($request->version) : null;
        $xuatXu = trim($request->xuat_xu);
        $releaseYear = $request->filled('release_year') ? (int) $request->release_year : null;

        $created = [];
        $skipped = [];
        $errors = [];

        foreach ($modelCodes as $modelCode) {
            // Validate each model code length
            if (strlen($modelCode) > 100) {
                $errors[] = "Mã model '{$modelCode}' quá dài (tối đa 100 ký tự).";
                continue;
            }

            // Check if model code already exists
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
                $skipped[] = $modelCode;
                continue;
            }

            // Create new ProductModel
            ProductModel::create([
                'product_id'   => $productId,
                'xuat_xu'      => $xuatXu,
                'model_code'   => $modelCode,
                'version'      => $version,
                'release_year' => $releaseYear,
                'status'       => 'active',
            ]);

            $created[] = $modelCode;
        }

        // Build response message
        $messages = [];
        if (!empty($created)) {
            $messages[] = 'Đã thêm thành công ' . count($created) . ' mã model: ' . implode(', ', $created);
        }
        if (!empty($skipped)) {
            $messages[] = 'Bỏ qua ' . count($skipped) . ' mã đã tồn tại: ' . implode(', ', $skipped);
        }
        if (!empty($errors)) {
            $messages[] = 'Lỗi: ' . implode('; ', $errors);
        }

        $finalMessage = implode('. ', $messages);

        if (empty($created) && !empty($skipped)) {
            return response()->json(['message' => $finalMessage], 422);
        }

        return response()->json(['message' => $finalMessage]);
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

    // Download toàn bộ tài liệu của mã lỗi (ZIP)
    public function downloadAllDocuments(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId) {
            abort(400, 'Thiếu error_id');
        }

        $error = CommonError::with([
            'productModel',
            'repairGuides.technicalDocuments.documentVersions',
        ])->find($errorId);

        if (!$error) {
            abort(404, 'Không tìm thấy mã lỗi');
        }

        $filePaths = [];
        foreach ($error->repairGuides as $guide) {
            foreach ($guide->technicalDocuments as $doc) {
                $version = $doc->documentVersions->sortByDesc('id')->first();
                if ($version && $version->file_path) {
                    $fullPath = storage_path('app/public/' . ltrim($version->file_path, '/'));
                    if (file_exists($fullPath)) {
                        $filePaths[] = [
                            'path' => $fullPath,
                            'name' => basename($version->file_path),
                        ];
                    }
                }
            }
        }

        if (empty($filePaths)) {
            abort(404, 'Không có tài liệu nào để tải.');
        }

        $modelCode = $error->productModel ? str_replace(' ', '_', $error->productModel->model_code) : 'MODEL';
        $errorCode = str_replace(' ', '_', $error->error_code);
        $zipFileName = $modelCode . '_' . $errorCode . '_Documents_' . time() . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Không tạo được file ZIP.');
        }

        foreach ($filePaths as $file) {
            $zip->addFile($file['path'], $file['name']);
        }
        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
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

    /**
     * Giới hạn dung lượng file tài liệu kỹ thuật: ảnh < 2MB, PDF < 5MB, video < 10MB.
     */
    private const FILE_MAX_IMAGE_BYTES = 2 * 1024 * 1024;   // 2MB
    private const FILE_MAX_PDF_BYTES   = 5 * 1024 * 1024;   // 5MB
    private const FILE_MAX_VIDEO_BYTES = 10 * 1024 * 1024; // 10MB

    private function validateTechnicalDocumentFile($file, string $attribute = 'file'): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize();
        $name = $file->getClientOriginalName();

        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'mp4', 'webm'];
        if (!in_array($ext, $allowed)) {
            throw ValidationException::withMessages([
                $attribute => "File \"{$name}\" có định dạng không hỗ trợ. Chỉ chấp nhận: " . implode(', ', $allowed),
            ]);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            if ($size > 2 * 1024 * 1024) { // 2MB
                throw ValidationException::withMessages([
                    $attribute => "File ảnh \"{$name}\" vượt quá 2MB. Vui lòng chọn file nhỏ hơn.",
                ]);
            }
            return;
        }
        if ($ext === 'pdf') {
            if ($size > 5 * 1024 * 1024) { // 5MB
                throw ValidationException::withMessages([
                    $attribute => "File PDF \"{$name}\" vượt quá 5MB. Vui lòng chọn file nhỏ hơn.",
                ]);
            }
            return;
        }
        if (in_array($ext, ['mp4', 'webm'])) {
            if ($size > 10 * 1024 * 1024) { // 10MB
                throw ValidationException::withMessages([
                    $attribute => "File video \"{$name}\" vượt quá 10MB. Vui lòng chọn file nhỏ hơn.",
                ]);
            }
            return;
        }
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
            'files.*'        => 'file|mimes:pdf,jpg,jpeg,png,mp4,webm',
        ], [
            'error_id.required' => 'Vui lòng chọn mã lỗi.',
            'title.required'    => 'Tiêu đề hướng dẫn không được để trống.',
            'steps.required'    => 'Các bước xử lý không được để trống.',
        ]);

        $uploadedFiles = $request->file('files');
        if (!empty($uploadedFiles)) {
            foreach ($uploadedFiles as $index => $file) {
                $this->validateTechnicalDocumentFile($file, 'files.' . $index);
            }
        }

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

    // --- Common errors CRUD (update, destroy) ---
    public function getErrorById($id)
    {
        $error = CommonError::where('id', (int) $id)->first(['id', 'model_id', 'error_code', 'error_name', 'severity', 'description']);
        if (!$error) {
            return response()->json(['error' => 'Không tìm thấy mã lỗi'], 404);
        }
        return response()->json($error);
    }

    public function updateError(Request $request, $id)
    {
        $error = CommonError::findOrFail($id);
        $request->validate([
            'error_code'  => 'required|string|max:100',
            'error_name'  => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity'    => 'nullable|in:normal,common,critical',
        ], [
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $exists = CommonError::where('model_id', $error->model_id)
            ->where('error_code', $request->error_code)
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho model này.'], 422);
        }

        $error->update([
            'error_code'  => $request->error_code,
            'error_name'  => $request->error_name,
            'description' => $request->description,
            'severity'    => $request->severity ?? 'normal',
        ]);
        return response()->json(['message' => 'Đã cập nhật mã lỗi.']);
    }

    public function destroyError($id)
    {
        $error = CommonError::findOrFail($id);
        $error->delete();
        return response()->json(['message' => 'Đã xóa mã lỗi.']);
    }

    // --- Repair guides CRUD (edit, update, destroy) ---
    public function getRepairGuidesByError(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId) {
            return response()->json([]);
        }
        $guides = RepairGuide::where('error_id', $errorId)
            ->orderBy('id')
            ->get(['id', 'error_id', 'title', 'steps', 'estimated_time', 'safety_note']);
        return response()->json($guides);
    }

    public function editRepairGuide($id)
    {
        $guide = RepairGuide::with(['commonError.productModel', 'technicalDocuments.documentVersions'])->findOrFail($id);
        $categories = Category::where('website_id', 2)->get();
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.repair-guide-edit', compact('guide', 'categories', 'storageUrl'));
    }

    public function updateRepairGuide(Request $request, $id)
    {
        $guide = RepairGuide::findOrFail($id);
        $request->validate([
            'title'          => 'required|string|max:255',
            'steps'          => 'required|string',
            'estimated_time' => 'nullable|integer|min:0',
            'safety_note'    => 'nullable|string',
        ], [
            'title.required' => 'Tiêu đề hướng dẫn không được để trống.',
            'steps.required' => 'Các bước xử lý không được để trống.',
        ]);

        $guide->update([
            'title'          => $request->title,
            'steps'          => $request->steps,
            'estimated_time' => (int) ($request->estimated_time ?? 0),
            'safety_note'    => $request->safety_note,
        ]);
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã cập nhật hướng dẫn sửa.']);
        }
        return redirect()->route('warranty.document.create')->with('success', 'Đã cập nhật hướng dẫn sửa.');
    }

    public function destroyRepairGuide($id)
    {
        $guide = RepairGuide::findOrFail($id);
        $guide->delete();
        return response()->json(['message' => 'Đã xóa hướng dẫn sửa.']);
    }

    public function attachDocumentsToRepairGuide(Request $request, $id)
    {
        $guide = RepairGuide::findOrFail($id);
        $request->validate(['document_ids' => 'required|array', 'document_ids.*' => 'integer|exists:technical_documents,id']);
        $modelId = $guide->commonError->model_id;
        foreach ($request->document_ids as $docId) {
            $doc = TechnicalDocument::where('id', $docId)->where('model_id', $modelId)->first();
            if ($doc && !$guide->technicalDocuments()->where('technical_documents.id', $docId)->exists()) {
                RepairGuideDocument::create(['repair_guide_id' => $guide->id, 'document_id' => $docId]);
            }
        }
        return response()->json(['message' => 'Đã gắn tài liệu.']);
    }

    public function detachDocumentFromRepairGuide($id, $documentId)
    {
        RepairGuideDocument::where('repair_guide_id', $id)->where('document_id', $documentId)->delete();
        return response()->json(['message' => 'Đã gỡ tài liệu.']);
    }

    // --- Technical documents CRUD ---
    public function indexDocuments(Request $request)
    {
        $categories = Category::where('website_id', 2)->get();
        $modelId = (int) $request->get('model_id');
        $documents = collect();
        $productModel = null;
        $filter = ['category_id' => '', 'product_id' => '', 'xuat_xu' => ''];
        if ($modelId) {
            $productModel = ProductModel::with('product')->find($modelId);
            if ($productModel) {
                $documents = TechnicalDocument::where('model_id', $modelId)
                    ->withCount('documentVersions')
                    ->orderBy('doc_type')
                    ->orderBy('title')
                    ->get();
                $filter = [
                    'category_id' => $productModel->product->category_id ?? '',
                    'product_id'  => $productModel->product_id,
                    'xuat_xu'     => $productModel->xuat_xu ?? '',
                ];
            }
        }
        return view('technicaldocument.documents-index', compact('categories', 'documents', 'productModel', 'filter'));
    }

    public function getDocumentsByModel(Request $request)
    {
        $modelId = (int) $request->get('model_id');
        if (!$modelId) {
            return response()->json([]);
        }
        $documents = TechnicalDocument::where('model_id', $modelId)
            ->orderBy('doc_type')
            ->orderBy('title')
            ->get(['id', 'doc_type', 'title', 'description', 'status']);
        return response()->json($documents);
    }

    public function createDocument()
    {
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.document-create', compact('categories'));
    }

    public function storeDocument(Request $request)
    {
        $request->validate([
            'model_id'    => 'required|integer',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'required|file',
        ], [
            'model_id.required' => 'Vui lòng chọn model.',
            'title.required'    => 'Tiêu đề không được để trống.',
            'file.required'     => 'Vui lòng chọn file tài liệu.',
        ]);
        if (!ProductModel::where('id', $request->model_id)->exists()) {
            return back()->withErrors(['model_id' => 'Model không tồn tại.'])->withInput();
        }

        $file = $request->file('file');
        $this->validateTechnicalDocumentFile($file, 'file');
        $ext = strtolower($file->getClientOriginalExtension());
        $ext = strtolower($file->getClientOriginalExtension());
        $directory = $this->getDirectoryForFile($ext);
        $path = $file->storeAs($directory, time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName()), 'public');

        // Auto-detect doc_type from extension
        $docType = match ($ext) {
            'pdf' => 'manual',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'mp4', 'webm' => 'video',
            default => 'manual',
        };

        $doc = TechnicalDocument::create([
            'model_id'    => $request->model_id,
            'doc_type'    => $docType,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => 'active',
        ]);

        DocumentVersion::create([
            'document_id' => $doc->id,
            'version'     => '1.0',
            'file_path'   => $path,
            'file_type'   => $ext,
            'status'     => 'active',
            'uploaded_by' => Auth::id(),
        ]);

        // Xử lý file đính kèm ngay khi tạo
        $this->processAttachments($request, $doc->id);

        return redirect()->route('warranty.document.documents.index', ['model_id' => $request->model_id])->with('success', 'Đã thêm tài liệu.');
    }

    /**
     * Stream file tài liệu qua Laravel (tránh 403 khi xem trực tiếp từ /storage/...).
     * Chỉ user đã đăng nhập + có quyền xem tài liệu mới truy cập được.
     */
    public function streamDocumentFile(Request $request, $id)
    {
        $document = TechnicalDocument::with('documentVersions')->findOrFail($id);
        $versionId = $request->query('version_id');
        $version = $versionId
            ? $document->documentVersions->firstWhere('id', (int) $versionId)
            : $document->documentVersions->sortByDesc('id')->first();

        if (!$version || !$version->file_path) {
            abort(404, 'Không tìm thấy file.');
        }

        $path = Storage::disk('public')->path($version->file_path);
        if (!is_file($path)) {
            abort(404, 'File không tồn tại trên đĩa.');
        }

        $disposition = $request->query('download') ? 'attachment' : 'inline';
        $mime = $this->mimeForExtension($version->file_type ?? '');
        $filename = basename($version->file_path);

        return response()->file($path, [
            'Content-Type'        => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    private function mimeForExtension(string $ext): string
    {
        $map = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];
        return $map[strtolower($ext)] ?? 'application/octet-stream';
    }

    public function showDocument($id)
    {
        $document = TechnicalDocument::with(['productModel', 'documentVersions', 'repairGuides.commonError', 'attachments'])->findOrFail($id);
        $fileRouteName = 'warranty.document.documents.file';
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.document-show', compact('document', 'fileRouteName', 'storageUrl'));
    }

    public function editDocument($id)
    {
        $document = TechnicalDocument::with(['productModel', 'documentVersions', 'attachments'])->findOrFail($id);
        $categories = Category::where('website_id', 2)->get();
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.document-edit', compact('document', 'categories', 'storageUrl'));
    }

    public function updateDocument(Request $request, $id)
    {
        $document = TechnicalDocument::findOrFail($id);
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'nullable|file',
            'version'     => 'nullable|string|max:50',
        ], [
            'title.required' => 'Tiêu đề không được để trống.',
        ]);

        if ($request->hasFile('file')) {
            $this->validateTechnicalDocumentFile($request->file('file'), 'file');
        }

        $document->update([
            'title'       => $request->title,
            'description' => $request->description,
        ]);

        $message = 'Đã cập nhật thông tin tài liệu.';

        if ($request->hasFile('file')) {
            // Tự động tăng version
            $latestVersion = $document->documentVersions()->orderByDesc('id')->first();
            $newVersion = '1.0';

            if ($latestVersion) {
                $parts = explode('.', $latestVersion->version);
                if (count($parts) >= 2 && is_numeric($end = end($parts))) {
                    array_pop($parts);
                    $parts[] = $end + 1;
                    $newVersion = implode('.', $parts);
                } else {
                    // Fallback nếu version cũ không đúng định dạng số (ví dụ "v1") -> nối thêm .1
                    $newVersion = $latestVersion->version . '.1';
                }
            }

            // Kiểm tra trùng (dù lý thuyết khó xảy ra nếu auto-increment chuẩn, nhưng safety first)
            while ($document->documentVersions()->where('version', $newVersion)->exists()) {
                $parts = explode('.', $newVersion);
                $end = array_pop($parts);
                $parts[] = $end + 1;
                $newVersion = implode('.', $parts);
            }

            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());

            // Auto-update doc_type if file changes
            $docType = match ($ext) {
                'pdf' => 'manual',
                'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
                'mp4', 'webm' => 'video',
                default => 'manual',
            };
            $document->update(['doc_type' => $docType]);

            $directory = $this->getDirectoryForFile($ext);
            
            // Thêm version vào tên file để tránh trùng lặp/cache
            $fileName = time() . '_v' . $newVersion . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            
            $path = $file->storeAs($directory, $fileName, 'public');

            DocumentVersion::create([
                'document_id' => $document->id,
                'version'     => $newVersion,
                'file_path'   => $path,
                'file_type'   => $ext,
                'status'      => 'active',
                'uploaded_by' => Auth::id(),
            ]);

            $message = 'Đã cập nhật tài liệu. Phiên bản mới: ' . $newVersion;
        }

        // Xử lý file đính kèm theo từng loại
        $this->processAttachments($request, $document->id);

        return redirect()->route('warranty.document.documents.edit', $id)->with('success', $message);
    }
    
    // Xóa file đính kèm
    public function destroyAttachment($id)
    {
        $attachment = TechnicalDocumentAttachment::findOrFail($id);
        $fullPath = storage_path('app/public/' . ltrim($attachment->file_path, '/'));
        
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        
        $attachment->delete();
        
        return back()->with('success', 'Đã xóa file đính kèm.');
    }

    public function destroyDocument($id)
    {
        $document = TechnicalDocument::findOrFail($id);
        foreach ($document->documentVersions as $ver) {
            $fullPath = storage_path('app/public/' . ltrim($ver->file_path, '/'));
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        $document->delete();
        return response()->json(['message' => 'Đã xóa tài liệu.']);
    }

    /**
     * Helper xử lý lưu attachments từ nhiều nguồn input
     */
    private function processAttachments(Request $request, $documentId)
    {
        $inputs = [
            'attachments_image' => 'image',
            'attachments_pdf'   => 'pdf',
            'attachments_video' => 'video',
        ];

        foreach ($inputs as $inputName => $forceType) {
            if ($request->hasFile($inputName)) {
                $files = $request->file($inputName);
                foreach ($files as $file) {
                    // Validate sơ bộ
                    $this->validateTechnicalDocumentFile($file, $inputName);

                    $ext = strtolower($file->getClientOriginalExtension());
                    $directory = $this->getDirectoryForFile($ext);
                    
                    $originalName = $file->getClientOriginalName();
                    // Thêm prefix loại file để dễ debug
                    $fileName = time() . '_' . $forceType . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    
                    $path = $file->storeAs($directory, $fileName, 'public');

                    TechnicalDocumentAttachment::create([
                        'document_id' => $documentId,
                        'file_path'   => $path,
                        'file_type'   => $forceType, // Lưu luôn loại theo input
                        'file_name'   => $originalName,
                    ]);
                }
            }
        }
    }

    private function getDirectoryForFile(string $ext): string
    {
        return match ($ext) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'photos',
            'pdf' => 'reports',
            'mp4', 'webm' => 'videos',
            default => 'technical_documents', // Fallback
        };
    }
}
