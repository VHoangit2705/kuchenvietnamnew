<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Kho\Category;
use App\Models\Kho\Product;
use App\Models\Kho\ProductModel;
use App\Models\KyThuat\CommonError;
use App\Models\KyThuat\TechnicalDocument;
use App\Models\KyThuat\RepairGuide;

class PublicTechnicalDocumentController extends Controller
{
    // Public Index Page
    public function index()
    {
        if (!session()->has('brand')) {
            session(['brand' => 'kuchen']);
        }
        $categories = Category::where('website_id', 2)->get();
        return view('technicaldocument.index', compact('categories'));
    }

    // LIST DOCUMENTS PAGE
    public function indexDocuments(Request $request)
    {
        if (!session()->has('brand')) {
            session(['brand' => 'kuchen']);
        }
        $categories = Category::where('website_id', 2)->get();

        $productId = (int) $request->get('product_id');
        $xuatXu = $request->get('xuat_xu');

        $documents = collect();
        $productModel = null;
        $filter = [
            'category_id' => $request->get('category_id', ''),
            'product_id' => $productId ?: '',
            'xuat_xu' => $xuatXu ?: ''
        ];

        if ($productId && $xuatXu) {
            $productModel = ProductModel::with('product')->where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->first();

            $documents = TechnicalDocument::where('product_id', $productId)
                ->where('xuat_xu', $xuatXu)
                ->withCount('documentVersions')
                ->orderBy('doc_type')
                ->orderBy('title')
                ->get();

            if ($productModel && $productModel->product) {
                $filter['category_id'] = $productModel->product->category_id;
            }
        }
        return view('technicaldocument.documents-index', compact('categories', 'documents', 'productModel', 'filter'));
    }

    // SHOW SINGLE DOCUMENT
    public function showDocument($id)
    {
        $document = TechnicalDocument::with([
            'documentVersions' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        $latestVersion = $document->documentVersions->first();
        $storageUrl = rtrim(asset('storage'), '/');
        return view('technicaldocument.document-show', compact('document', 'latestVersion', 'storageUrl'));
    }

    // STREAM FILE
    public function streamDocumentFile($id)
    {
        // NO PERMISSION CHECK - Publicly accessible if link known
        $document = TechnicalDocument::findOrFail($id);
        $latestVersion = $document->documentVersions()->latest()->first();

        if (!$latestVersion) {
            abort(404);
        }

        $path = $latestVersion->img_upload ?? $latestVersion->video_upload ?? $latestVersion->pdf_upload;
        if (!$path) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . ltrim($path, '/'));

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }

    // AJAX METHODS
    public function getProductsByCategory(Request $request)
    {
        $categoryId = (int) $request->get('category_id');
        if (!$categoryId) {
            return response()->json([]);
        }
        $products = Product::getProductsByCategoryId($categoryId, 1);
        return response()->json($products);
    }

    public function getOriginsByProduct(Request $request)
    {
        return ProductModel::where('product_id', $request->product_id)
            ->whereNotNull('xuat_xu')
            ->where('xuat_xu', '!=', '')
            ->select('xuat_xu')
            ->distinct()
            ->get();
    }

    public function getModelsByOrigin(Request $request)
    {
        return ProductModel::where('product_id', $request->product_id)
            ->where('xuat_xu', $request->xuat_xu)
            ->get(['id', 'model_code', 'version', 'release_year']);
    }

    public function getErrorsByModel(Request $request)
    {
        $productId = (int) $request->get('product_id');
        $xuatXu = $request->get('xuat_xu');

        if (!$productId || !$xuatXu) {
            return response()->json([]);
        }

        $errors = CommonError::where('product_id', $productId)
            ->where('xuat_xu', $xuatXu)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($errors);
    }

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
                // Ensure version exists
                if (!$version)
                    continue;

                $filePath = $version->img_upload ?? $version->video_upload ?? $version->pdf_upload;
                if (!$filePath) {
                    continue;
                }
                $fileUrl = '';
                if ($version->img_upload) {
                    $fileUrl = $storageUrl . '/' . ltrim($version->img_upload, '/');
                } elseif ($version->video_upload) {
                    $fileUrl = $storageUrl . '/' . ltrim($version->video_upload, '/');
                } elseif ($version->pdf_upload) {
                    $fileUrl = $storageUrl . '/' . ltrim($version->pdf_upload, '/');
                }

                if (!$fileUrl)
                    continue;

                $documents[] = [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'doc_type' => $doc->doc_type,
                    'file_url' => $fileUrl,
                    'file_type' => $doc->doc_type === 'image' ? 'jpg' : ($doc->doc_type === 'video' ? 'mp4' : 'pdf'), // Simplified type hint or define based on extension if needed
                ];
            }
            return [
                'id' => $guide->id,
                'title' => $guide->title,
                'steps' => $guide->steps,
                'estimated_time' => $guide->estimated_time,
                'safety_note' => $guide->safety_note,
                'documents' => $documents,
            ];
        });

        return response()->json([
            'error' => [
                'id' => $error->id,
                'error_code' => $error->error_code,
                'error_name' => $error->error_name,
                'description' => $error->description,
                'severity' => $error->severity,
            ],
            'repair_guides' => $repairGuides,
        ]);
    }

    public function getRepairGuidesByError(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId)
            return response()->json([]);

        $guides = RepairGuide::where('error_id', $errorId) // Foreign key is error_id, not common_error_id based on Model definition? Model says belongsTo CommonError::class, 'error_id'.
            ->orderBy('id')
            ->get(['id', 'error_id', 'title', 'steps', 'estimated_time', 'safety_note']);

        return response()->json($guides);
    }

    public function getErrorById($id)
    {
        // For common error details page if any
        $error = CommonError::where('id', (int) $id)->first(['id', 'product_id', 'xuat_xu', 'error_code', 'error_name', 'severity', 'description']);
        if (!$error) {
            return response()->json(['error' => 'Không tìm thấy mã lỗi'], 404);
        }
        return response()->json($error);
    }

    public function getDocumentsByModel(Request $request)
    {
        $productId = (int) $request->get('product_id');
        $xuatXu = $request->get('xuat_xu');
        if (!$productId || !$xuatXu) {
            return response()->json([]);
        }

        $documents = TechnicalDocument::where('product_id', $productId)
            ->where('xuat_xu', $xuatXu)
            ->orderBy('doc_type')
            ->orderBy('title')
            ->get(['id', 'doc_type', 'title', 'description', 'status']);

        return response()->json($documents);
    }

    public function downloadAllDocuments(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId) {
            abort(400, 'Thiếu error_id');
        }

        $error = CommonError::with([
            'product',
            'repairGuides.technicalDocuments.documentVersions',
        ])->find($errorId);

        if (!$error)
            abort(404, 'Không tìm thấy mã lỗi');

        $filePaths = [];
        foreach ($error->repairGuides as $guide) {
            foreach ($guide->technicalDocuments as $doc) { // Correct relationship name
                $version = $doc->documentVersions->sortByDesc('id')->first();
                if ($version) {
                    $filePath = $version->img_upload ?? $version->video_upload ?? $version->pdf_upload;
                    if ($filePath) {
                        $fullPath = storage_path('app/public/' . ltrim($filePath, '/'));
                        if (file_exists($fullPath)) {
                            $filePaths[] = [
                                'path' => $fullPath,
                                'name' => basename($filePath),
                            ];
                        }
                    }
                }
            }
        }

        if (empty($filePaths)) {
            abort(404, 'Không có tài liệu nào để tải.');
        }

        $productName = $error->product ? str_replace(' ', '_', $error->product->product_name) : 'PRODUCT';
        $errorCode = str_replace(' ', '_', $error->error_code);
        $zipFileName = $productName . '_' . $errorCode . '_Documents_' . time() . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            abort(500, 'Không tạo được file ZIP.');
        }

        foreach ($filePaths as $file) {
            $zip->addFile($file['path'], $file['name']);
        }
        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
