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
use App\Models\products_new\ProductWorkflow;
use App\Models\products_new\ProductDetails;

class TechnicalDocumentController extends Controller
{
    protected function authorizePermission(string $permission): void
    {
        if (!Auth::user() || !Auth::user()->hasPermission($permission)) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }
    }



    public function create()
    {
        $this->authorizePermission('technical_document.manage');
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.create', compact('categories'));
    }



    // Thêm xuất xứ sản phẩm (tạo product_model mới)
    public function storeOrigin(Request $request)
    {
        $this->authorizePermission('technical_document.manage');
        $request->validate([
            'product_id' => 'required|integer',
            'xuat_xu' => 'required|string|max:255',
            'model_code' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'release_year' => 'nullable|integer|min:1990|max:2100',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'xuat_xu.required' => 'Xuất xứ không được để trống.',
        ]);

        $productId = (int) $request->product_id;
        if (!Product::where('id', $productId)->exists()) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 422);
        }

        // Split model codes by comma and trim whitespace, or generate default if empty
        $modelCodesInput = trim($request->model_code ?? '');

        if (empty($modelCodesInput)) {
            // Auto-generate a model code based on product and origin
            $product = Product::find($productId);
            $xuatXu = trim($request->xuat_xu);
            $modelCodesInput = mb_strtoupper(mb_substr($product->name ?? 'MODEL', 0, 3, 'UTF-8'), 'UTF-8') . '_' . mb_strtoupper(mb_substr($xuatXu, 0, 2, 'UTF-8'), 'UTF-8') . '_' . time();
        }

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
                'product_id' => $productId,
                'xuat_xu' => $xuatXu,
                'model_code' => $modelCode,
                'version' => $version,
                'release_year' => $releaseYear,
                'status' => 'active',
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







    // 5. Thêm mã lỗi kỹ thuật (Bước 5)
    public function storeError(Request $request)
    {
        $this->authorizePermission('technical_document.manage');

        $request->validate([
            'product_id' => 'required|integer|exists:mysql3.products,id',
            'xuat_xu' => 'required|string',
            'error_code' => 'required|string|max:100',
            'error_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'nullable|in:normal,common,critical',
        ], [
            'product_id.required' => 'Chưa xác định Sản phẩm.',
            'xuat_xu.required' => 'Chưa xác định Xuất xứ.',
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $productId = $request->product_id;
        $xuatXu = $request->xuat_xu;

        // Kiểm tra trùng mã lỗi
        $exists = CommonError::where('product_id', $productId)
            ->where('xuat_xu', $xuatXu)
            ->where('error_code', $request->error_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho sản phẩm and xuất xứ này.',
            ], 422);
        }

        $error = CommonError::create([
            'product_id' => $productId,
            'xuat_xu' => $xuatXu,
            'error_code' => $request->error_code,
            'error_name' => $request->error_name,
            'description' => $request->description,
            'severity' => $request->severity ?? 'normal',
        ]);

        return response()->json([
            'message' => 'Đã thêm mã lỗi kỹ thuật.',
            'error' => $error
        ]);
    }

    /**
     * Giới hạn dung lượng file tài liệu kỹ thuật: ảnh < 2MB, PDF < 5MB, video < 10MB.
     */
    private const FILE_MAX_IMAGE_BYTES = 2 * 1024 * 1024;   // 2MB
    private const FILE_MAX_PDF_BYTES = 5 * 1024 * 1024;   // 5MB
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

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            if ($size > self::FILE_MAX_IMAGE_BYTES) {
                throw ValidationException::withMessages([
                    $attribute => "File ảnh \"{$name}\" vượt quá giới hạn cho phép (" . (self::FILE_MAX_IMAGE_BYTES / 1024 / 1024) . "MB).",
                ]);
            }
        } elseif ($ext === 'pdf') {
            if ($size > self::FILE_MAX_PDF_BYTES) {
                throw ValidationException::withMessages([
                    $attribute => "File PDF \"{$name}\" vượt quá giới hạn cho phép (" . (self::FILE_MAX_PDF_BYTES / 1024 / 1024) . "MB).",
                ]);
            }
        } elseif (in_array($ext, ['mp4', 'webm'])) {
            if ($size > self::FILE_MAX_VIDEO_BYTES) {
                throw ValidationException::withMessages([
                    $attribute => "File video \"{$name}\" vượt quá giới hạn cho phép (" . (self::FILE_MAX_VIDEO_BYTES / 1024 / 1024) . "MB).",
                ]);
            }
        }
    }

    // 6–7. Thêm hướng dẫn sửa & gắn tài liệu (Bước 6–7)
    public function storeRepairGuide(Request $request)
    {
        $this->authorizePermission('technical_document.manage');
        $request->validate([
            'error_id' => 'required|integer|exists:common_errors,id',
            'title' => 'required|string|max:255',
            'steps' => 'required|string',
            'estimated_time' => 'nullable|integer|min:0',
            'safety_note' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file', // Validation chi tiết (type/size) đã được xử lý trong validateTechnicalDocumentFile
        ], [
            'error_id.required' => 'Vui lòng chọn mã lỗi.',
            'title.required' => 'Tiêu đề hướng dẫn không được để trống.',
            'steps.required' => 'Các bước xử lý không được để trống.',
        ]);

        $uploadedFiles = $request->file('files');
        if (!empty($uploadedFiles)) {
            foreach ($uploadedFiles as $index => $file) {
                $this->validateTechnicalDocumentFile($file, 'files.' . $index);
            }
        }

        $error = CommonError::findOrFail($request->error_id);
        $productId = $error->product_id;
        $xuatXu = $error->xuat_xu;

        $guide = RepairGuide::create([
            'error_id' => $request->error_id,
            'title' => $request->title,
            'steps' => $request->steps,
            'estimated_time' => (int) ($request->estimated_time ?? 0),
            'safety_note' => $request->safety_note,
        ]);

        $uploadedFiles = $request->file('files');
        if (!empty($uploadedFiles)) {
            $disk = 'public';

            // Lấy thông tin model đại diện để đặt tên file (nếu có)
            $productModel = ProductModel::where('product_id', $productId)->where('xuat_xu', $xuatXu)->first();
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

                // Chọn thư mục lưu dựa theo loại file
                $basePath = $this->getDirectoryForFile($ext);

                // Tên file có cấu trúc: {model_code}_{error_code}_v{model_version}_{doc_type}_{timestamp}_{index}.{ext}
                $timestamp = time();
                $fileName = $modelCode . '_' . $errorCode;
                if ($modelVersion) {
                    $fileName .= '_v' . $modelVersion;
                }
                $fileName .= '_' . $docType . '_' . $timestamp . '_' . ($index + 1) . '.' . $ext;

                $path = $file->storeAs($basePath, $fileName, $disk);

                $doc = TechnicalDocument::create([
                    'product_id' => $productId,
                    'xuat_xu' => $xuatXu,
                    'doc_type' => $docType,
                    'title' => $file->getClientOriginalName(), // Lấy tên file gốc làm tiêu đề
                    'description' => 'Đính kèm hướng dẫn sửa: ' . $guide->title,
                    'status' => 'active',
                ]);

                $createData = [
                    'document_id' => $doc->id,
                    'version' => '1.0',
                    'status' => 'active',
                    'uploaded_by' => Auth::id(),
                    'img_upload' => null,
                    'video_upload' => null,
                    'pdf_upload' => null,
                ];

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $createData['img_upload'] = $path;
                } elseif (in_array($ext, ['mp4', 'webm'])) {
                    $createData['video_upload'] = $path;
                } elseif ($ext === 'pdf') {
                    $createData['pdf_upload'] = $path;
                }

                DocumentVersion::create($createData);

                RepairGuideDocument::create([
                    'repair_guide_id' => $guide->id,
                    'document_id' => $doc->id,
                ]);
            }
        }

        return response()->json(['message' => 'Đã lưu hướng dẫn và tài liệu đính kèm.']);
    }



    public function updateError(Request $request, $id)
    {
        $this->authorizePermission('technical_document.manage');
        $error = CommonError::findOrFail($id);
        $request->validate([
            'error_code' => 'required|string|max:100',
            'error_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'nullable|in:normal,common,critical',
        ], [
            'error_code.required' => 'Mã lỗi không được để trống.',
            'error_name.required' => 'Tên lỗi không được để trống.',
        ]);

        $exists = CommonError::where('product_id', $error->product_id)
            ->where('xuat_xu', $error->xuat_xu)
            ->where('error_code', $request->error_code)
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Mã lỗi "' . $request->error_code . '" đã tồn tại cho sản phẩm and xuất xứ này.'], 422);
        }

        $error->update([
            'error_code' => $request->error_code,
            'error_name' => $request->error_name,
            'description' => $request->description,
            'severity' => $request->severity ?? 'normal',
        ]);
        return response()->json(['message' => 'Đã cập nhật mã lỗi.']);
    }

    public function destroyError($id)
    {
        $this->authorizePermission('technical_document.manage');
        $error = CommonError::findOrFail($id);
        $error->delete();
        return response()->json(['message' => 'Đã xóa mã lỗi.']);
    }



    public function editRepairGuide($id)
    {
        $this->authorizePermission('technical_document.manage');
        $guide = RepairGuide::with(['commonError.product', 'technicalDocuments.documentVersions'])->findOrFail($id);
        $categories = Category::where('website_id', 2)->get();
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.repair-guide-edit', compact('guide', 'categories', 'storageUrl'));
    }

    public function updateRepairGuide(Request $request, $id)
    {
        $this->authorizePermission('technical_document.manage');
        $guide = RepairGuide::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'steps' => 'required|string',
            'estimated_time' => 'nullable|integer|min:0',
            'safety_note' => 'nullable|string',
        ], [
            'title.required' => 'Tiêu đề hướng dẫn không được để trống.',
            'steps.required' => 'Các bước xử lý không được để trống.',
        ]);

        $guide->update([
            'title' => $request->title,
            'steps' => $request->steps,
            'estimated_time' => (int) ($request->estimated_time ?? 0),
            'safety_note' => $request->safety_note,
        ]);
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã cập nhật hướng dẫn sửa.']);
        }
        return redirect()->route('warranty.document.create')->with('success', 'Đã cập nhật hướng dẫn sửa.');
    }

    public function destroyRepairGuide($id)
    {
        $this->authorizePermission('technical_document.manage');
        $guide = RepairGuide::findOrFail($id);
        $guide->delete();
        return response()->json(['message' => 'Đã xóa hướng dẫn sửa.']);
    }

    public function attachDocumentsToRepairGuide(Request $request, $id)
    {
        $this->authorizePermission('technical_document.manage');
        $guide = RepairGuide::findOrFail($id);
        $request->validate(['document_ids' => 'required|array', 'document_ids.*' => 'integer|exists:technical_documents,id']);

        $productId = $guide->commonError->product_id;
        $xuatXu = $guide->commonError->xuat_xu;

        foreach ($request->document_ids as $docId) {
            $doc = TechnicalDocument::where('id', $docId)
                ->where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->first();
            if ($doc && !$guide->technicalDocuments()->where('technical_documents.id', $docId)->exists()) {
                RepairGuideDocument::create(['repair_guide_id' => $guide->id, 'document_id' => $docId]);
            }
        }
        return response()->json(['message' => 'Đã gắn tài liệu.']);
    }

    public function detachDocumentFromRepairGuide($id, $documentId)
    {
        $this->authorizePermission('technical_document.manage');
        RepairGuideDocument::where('repair_guide_id', $id)->where('document_id', $documentId)->delete();
        return response()->json(['message' => 'Đã gỡ tài liệu.']);
    }





    public function createDocument()
    {
        $this->authorizePermission('technical_document.manage');
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.document-create', compact('categories'));
    }

    public function storeDocument(Request $request)
    {
        $this->authorizePermission('technical_document.manage');

        $request->validate([
            'product_id' => 'required|integer|exists:mysql3.products,id',
            'xuat_xu' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'xuat_xu.required' => 'Vui lòng chọn xuất xứ.',
            'title.required' => 'Tiêu đề không được để trống.',
            'file.required' => 'Vui lòng chọn file tài liệu.',
        ]);

        // Resolve model if needed for any reason, but we primary save product/origin
        $productModel = ProductModel::where('product_id', $request->product_id)
            ->where('xuat_xu', $request->xuat_xu)
            ->first();

        if (!$productModel) {
            return back()->withErrors(['product_id' => 'Không tìm thấy thông tin phù hợp cho sản phẩm và xuất xứ này.'])->withInput();
        }

        $file = $request->file('file');
        $this->validateTechnicalDocumentFile($file, 'file');
        $ext = strtolower($file->getClientOriginalExtension());
        $directory = $this->getDirectoryForFile($ext);

        // Đổi tên file
        $path = $file->storeAs($directory, time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName()), 'public');

        // Tự động nhận diện loại file
        $docType = match ($ext) {
            'pdf' => 'manual',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'mp4', 'webm' => 'video',
            default => 'manual',
        };

        // Tạo bản ghi
        $doc = TechnicalDocument::create([
            'product_id' => $request->product_id,
            'xuat_xu' => $request->xuat_xu,
            'doc_type' => $docType,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'active',
        ]);

        // Tạo version đầu tiên
        $createData = [
            'document_id' => $doc->id,
            'version' => '1.0',
            'status' => 'active',
            'uploaded_by' => Auth::id(),
            'img_upload' => null,
            'video_upload' => null,
            'pdf_upload' => null,
        ];

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $createData['img_upload'] = $path;
        } elseif (in_array($ext, ['mp4', 'webm'])) {
            $createData['video_upload'] = $path;
        } elseif ($ext === 'pdf') {
            $createData['pdf_upload'] = $path;
        }

        DocumentVersion::create($createData);

        // Xử lý file đính kèm
        $this->processAttachments($request, $doc->id);

        return redirect()->route('warranty.document.documents.index')->with('success', 'Đã thêm tài liệu.');
    }





    public function editDocument($id)
    {
        $this->authorizePermission('technical_document.manage');
        $document = TechnicalDocument::with(['product', 'documentVersions'])->findOrFail($id);
        $categories = Category::where('website_id', 2)->get();
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.document-edit', compact('document', 'categories', 'storageUrl'));
    }

    public function updateDocument(Request $request, $id)
    {
        $this->authorizePermission('technical_document.manage');
        $document = TechnicalDocument::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
            'version' => 'nullable|string|max:50',
        ], [
            'title.required' => 'Tiêu đề không được để trống.',
        ]);

        if ($request->hasFile('file')) {
            $this->validateTechnicalDocumentFile($request->file('file'), 'file');
        }

        $document->update([
            'title' => $request->title,
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

            $createData = [
                'document_id' => $document->id,
                'version' => $newVersion,
                'status' => 'active',
                'uploaded_by' => Auth::id(),
                'img_upload' => null,
                'video_upload' => null,
                'pdf_upload' => null,
            ];

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $createData['img_upload'] = $path;
            } elseif (in_array($ext, ['mp4', 'webm'])) {
                $createData['video_upload'] = $path;
            } elseif ($ext === 'pdf') {
                $createData['pdf_upload'] = $path;
            }

            DocumentVersion::create($createData);

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
        $this->authorizePermission('technical_document.manage');
        $document = TechnicalDocument::findOrFail($id);
        foreach ($document->documentVersions as $ver) {
            $path = $ver->img_upload ?? $ver->video_upload ?? $ver->pdf_upload;
            if ($path) {
                $fullPath = storage_path('app/public/' . ltrim($path, '/'));
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
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
            'attachments_pdf' => 'pdf',
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
                        'file_path' => $path,
                        'file_type' => $forceType, // Lưu luôn loại theo input
                        'file_name' => $originalName,
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

    /**
     * Tải ảnh từ CKEditor lên Server
     */
    public function uploadImageCKEditor(Request $request)
    {
        $this->authorizePermission('technical_document.manage');

        // Bỏ qua cache/error log nếu cần
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $ext = strtolower($file->getClientOriginalExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                return response()->json([
                    'error' => ['message' => 'Định dạng ảnh không hợp lệ (hỗ trợ: ' . implode(', ', $allowed) . ').']
                ], 400);
            }

            if ($file->getSize() > self::FILE_MAX_IMAGE_BYTES) {
                 return response()->json([
                    'error' => ['message' => 'Ảnh vượt quá dung lượng cho phép (' . (self::FILE_MAX_IMAGE_BYTES / 1024 / 1024) . 'MB).']
                ], 400);
            }

            // Lưu vào thư mục photos
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('photos', $fileName, 'public');

            // Định dạng output đúng chuẩn CKEditor 5 Upload Adapter cần
            return response()->json([
                'url' => asset('storage/' . $path)
            ]);
        }

        return response()->json([
            'error' => ['message' => 'Không tìm thấy file ảnh.']
        ], 400);
    }

    /**
     * Danh sách sản phẩm "Lên kệ" (Đã có Seri và Tài liệu kỹ thuật)
     */
    public function shelfList(Request $request)
    {
        $this->authorizePermission('technical_document.view');

        $products = Product::with(['technical_documents', 'warranty_cards', 'product_workflow'])
            ->latest()
            ->paginate(50);

        $countMissingSerials = Product::whereDoesntHave('warranty_cards')->count();
        $countMissingDocs = Product::has('warranty_cards')->whereDoesntHave('technical_documents')->count();

        return view('technicaldocument.shelf-list', compact('products', 'countMissingSerials', 'countMissingDocs'));
    }

    /**
     * Gửi sản phẩm đến phòng đào tạo
     */
    public function sendToTraining(Request $request)
    {
        $this->authorizePermission('technical_document.manage');
        
        $request->validate([
            'product_id' => 'required|exists:mysql3.products,id'
        ]);

        $productId = $request->product_id;

        // Cập nhật hoặc tạo mới workflow
        ProductWorkflow::updateOrCreate(
            ['product_id' => $productId],
            [
                'current_step' => 3, // Bước 3: Phòng đào tạo
                'department_assigned' => 'Đào tạo',
                'status' => 'pending',
                'reviewer_notes' => 'Chờ nạp thông tin HDSD (Phòng đào tạo)'
            ]
        );

        // Khởi tạo bảng chi tiết nếu chưa có
        ProductDetails::updateOrCreate(
            ['product_id' => $productId],
            []
        );

        // Flash session để trigger gửi email thông báo sau khi page reload
        session()->flash('notify_type', 'kythuat_send_training');
        session()->flash('notify_id', $productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã gửi sản phẩm đến phòng đào tạo thành công.'
        ]);
    }
}
