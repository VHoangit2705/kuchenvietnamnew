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
        return view('technicaldocument.document-show', compact('document', 'latestVersion'));
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
        $error = CommonError::with([
            'repairGuides' => function ($q) {
                $q->with('documents');
            }
        ])->find($errorId);

        if (!$error)
            return response()->json(null);

        return response()->json($error);
    }

    public function getRepairGuidesByError(Request $request)
    {
        $errorId = (int) $request->get('error_id');
        if (!$errorId)
            return response()->json([]);

        $guides = RepairGuide::where('common_error_id', $errorId)
            ->with(['documents'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($guides);
    }

    public function getErrorById($id)
    {
        // For common error details page if any
        $error = CommonError::with(['repairGuides.documents'])->findOrFail($id);
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
        $error = CommonError::with(['repairGuides.documents.documentVersions'])->find($errorId);

        if (!$error)
            abort(404);

        $zip = new \ZipArchive;
        $fileName = 'documents_error_' . $error->error_code . '.zip';
        $zipPath = storage_path('app/public/temp/' . $fileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($error->repairGuides as $guide) {
                foreach ($guide->documents as $doc) {
                    $ver = $doc->documentVersions->sortByDesc('created_at')->first();
                    if ($ver) {
                        $filePath = $ver->img_upload ?? $ver->video_upload ?? $ver->pdf_upload;
                        if ($filePath) {
                            $fullPath = storage_path('app/public/' . ltrim($filePath, '/'));
                            if (file_exists($fullPath)) {
                                $zip->addFile($fullPath, $doc->title . '.' . pathinfo($fullPath, PATHINFO_EXTENSION));
                            }
                        }
                    }
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
