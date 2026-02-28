<?php

namespace App\Http\Controllers;

use App\Models\products_new\Product;
use App\Models\products_new\User;
use App\Mail\KythuatSendTrainingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Trigger email notifications based on type and ID.
     */
    public function trigger(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        try {
            Log::channel('send_mail')->info("--- Bắt đầu quy trình gửi thông báo: [$type] cho ID: $id ---");

            switch ($type) {
                case 'it_new_product':
                    return $this->handleItNewProduct($id);
                
                case 'bgd_action':
                    return $this->handleBgdAction($id);
                
                case 'kythuat_send_training':
                    return $this->handleKythuatSendTraining($id);
                
                default:
                    Log::channel('send_mail')->warning("Cảnh báo: Loại thông báo không hợp lệ: $type");
                    return response()->json(['success' => false, 'message' => 'Loại thông báo không hợp lệ.'], 400);
            }
        } catch (\Exception $e) {
            Log::channel('send_mail')->error("Lỗi hệ thống khi gửi thông báo [$type ID:$id]: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi gửi thông báo.'], 500);
        }
    }

    /**
     * Gửi mail thông báo khi Kỹ thuật gửi tài liệu đến Phòng Đào tạo.
     */
    private function handleKythuatSendTraining($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            Log::channel('send_mail')->error("Lỗi: Không tìm thấy sản phẩm có ID: $id để gửi mail thông báo kỹ thuật.");
            return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
        }

        // Lấy danh sách email của tất cả nhân sự
        $recipients = User::whereNotNull('email')
            ->pluck('email')
            ->toArray();

        if (empty($recipients)) {
            Log::channel('send_mail')->error("Lỗi: Không tìm thấy danh sách email người nhận nào trong bảng users.");
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người nhận.']);
        }

        try {
            Mail::to($recipients)->send(new KythuatSendTrainingNotification($product));
            
            Log::channel('send_mail')->info("Thành công: Đã gửi email thông báo KT gửi tài liệu ĐT tới " . count($recipients) . " người nhận.");
            Log::channel('send_mail')->info("Sản phẩm: " . $product->product_name . " [ID:" . $product->id . "]");

            return response()->json([
                'success' => true, 
                'message' => 'Đã gửi mail thông báo kỹ thuật gửi tài liệu đến phòng đào tạo thành công.'
            ]);
        } catch (\Exception $e) {
            Log::channel('send_mail')->error("Lỗi SMTP khi gửi mail KT gửi ĐT [ID:$id]: " . $e->getMessage());
            throw $e;
        }
    }
}
