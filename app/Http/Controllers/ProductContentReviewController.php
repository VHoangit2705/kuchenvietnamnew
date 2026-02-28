<?php

namespace App\Http\Controllers;

use App\Models\Kho\Product;
use App\Models\products_new\ProductContentReview;
use App\Models\products_new\ProductDetails;
use App\Models\products_new\ProductWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductContentReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Danh sách sản phẩm chờ duyệt nội dung (Bước 3 đào tạo xong)
     */
    public function index()
    {
        // Lấy danh sách sản phẩm đang ở bước 3 (Đào tạo)
        $step3Ids = ProductWorkflow::where('current_step', 3)->pluck('product_id')->toArray();
        
        // Lấy danh sách sản phẩm có review đang pending hoặc rejected (để sửa lại)
        $reviewIds = ProductContentReview::whereIn('status', ['pending', 'rejected'])
            ->pluck('product_id')->toArray();

        $allIds = array_unique(array_merge($step3Ids, $reviewIds));

        $products = Product::whereIn('id', $allIds)
            ->with(['product_workflow', 'product_details', 'product_content_review'])
            ->latest('id')
            ->paginate(20);

        return view('technicaldocument.content_reviews.index', compact('products'));
    }

    /**
     * Chi tiết nội dung sản phẩm để duyệt
     */
    public function show($productId)
    {
        $product = Product::with(['product_details', 'product_workflow', 'product_content_review'])
            ->findOrFail($productId);

        return view('technicaldocument.content_reviews.show', compact('product'));
    }

    /**
     * Xử lý duyệt nội dung
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:mysql3.products,id',
            'action' => 'required|in:approve,reject',
            'reject_reason' => 'required_if:action,reject|nullable|string',
        ]);

        $productId = $request->product_id;
        $action = $request->action;

        DB::beginTransaction();
        try {
            $review = ProductContentReview::updateOrCreate(
                ['product_id' => $productId],
                [
                    'status' => $action === 'approve' ? 'approved' : 'rejected',
                    'reject_reason' => $action === 'reject' ? $request->reject_reason : null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]
            );

            $workflow = ProductWorkflow::where('product_id', $productId)->first();

            if ($action === 'approve') {
                if ($workflow) {
                    $workflow->update([
                        'current_step' => 4, // Bước 4: Hoàn tất / Marketing
                        'department_assigned' => 'Marketing',
                        'status' => 'approved',
                        'reviewer_notes' => 'Nội dung đã được duyệt. Chuyển Marketing.',
                    ]);
                }
            } else {
                if ($workflow) {
                    $workflow->update([
                        'status' => 'rejected',
                        'reviewer_notes' => 'Nội dung bị từ chối: ' . $request->reject_reason,
                    ]);
                }
            }

            DB::commit();

            // Trả về kèm thêm flag để trigger notification từ frontend (ajax)
            return response()->json([
                'status' => 'success',
                'message' => $action === 'approve' ? 'Đã duyệt nội dung sản phẩm thành công.' : 'Đã từ chối nội dung sản phẩm.',
                'notify_type' => 'content_review_result',
                'notify_id' => $review->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
