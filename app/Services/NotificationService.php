<?php

namespace App\Services;

use App\Models\KyThuat\Notification;
use App\Models\KyThuat\RequestAgency;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Gửi thông báo khi trạng thái yêu cầu lắp đặt thay đổi
     */
    public static function sendStatusChangeNotification(
        RequestAgency $requestAgency,
        string $oldStatus,
        string $newStatus
    ): ?Notification {
        try {
            // Chỉ gửi thông báo nếu có agency_id và trạng thái thay đổi
            if (!$requestAgency->agency_id || $oldStatus === $newStatus) {
                return null;
            }

            // Lấy tên trạng thái
            $statuses = RequestAgency::getStatuses();
            $oldStatusName = $statuses[$oldStatus] ?? $oldStatus;
            $newStatusName = $statuses[$newStatus] ?? $newStatus;

            // Tạo title và message
            $title = "Trạng thái đơn hàng lắp đặt đã được cập nhật";
            $message = sprintf(
                "Đơn hàng lắp đặt %s đã chuyển trạng thái từ '%s' sang '%s'",
                $requestAgency->order_code,
                $oldStatusName,
                $newStatusName
            );

            // Tạo thông báo
            $notification = Notification::create([
                'agency_id' => $requestAgency->agency_id,
                'request_agency_id' => $requestAgency->id,
                'type' => Notification::TYPE_STATUS_CHANGED,
                'title' => $title,
                'message' => $message,
                'status_old' => $oldStatus,
                'status_new' => $newStatus,
                'is_read' => false,
            ]);

            // Gửi email cho đại lý
            self::sendEmailNotification($notification);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Lỗi khi gửi thông báo trạng thái: ' . $e->getMessage(), [
                'request_agency_id' => $requestAgency->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            return null;
        }
    }

    /**
     * Gửi thông báo khi cập nhật thông tin đơn hàng
     */
    public static function sendOrderUpdateNotification(
        RequestAgency $requestAgency,
        string $message = null
    ): ?Notification {
        try {
            if (!$requestAgency->agency_id) {
                return null;
            }

            $title = "Thông tin đơn hàng đã được cập nhật";
            $defaultMessage = sprintf(
                "Thông tin đơn hàng %s đã được cập nhật",
                $requestAgency->order_code
            );

            $notification = Notification::create([
                'agency_id' => $requestAgency->agency_id,
                'request_agency_id' => $requestAgency->id,
                'type' => Notification::TYPE_ORDER_UPDATED,
                'title' => $title,
                'message' => $message ?? $defaultMessage,
                'status_old' => $requestAgency->status,
                'status_new' => $requestAgency->status,
                'is_read' => false,
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Lỗi khi gửi thông báo cập nhật đơn hàng: ' . $e->getMessage(), [
                'request_agency_id' => $requestAgency->id,
            ]);
            return null;
        }
    }

    /**
     * Gửi email thông báo cho đại lý dựa trên notification
     */
    public static function sendEmailNotification(Notification $notification): bool
    {
        try {
            Log::channel('custom')->info('Bắt đầu ========================================');
            Log::channel('custom')->debug('Bắt đầu gửi email notification', [
                'notification_id' => $notification->id,
                'agency_id' => $notification->agency_id,
                'type' => $notification->type,
            ]);

            // Load quan hệ agency và requestAgency
            $notification->load(['agency', 'requestAgency']);

            // Kiểm tra có agency không
            if (!$notification->agency) {
                Log::channel('custom')->debug('Không tìm thấy agency', [
                    'notification_id' => $notification->id,
                    'agency_id' => $notification->agency_id,
                ]);
                return false;
            }

            // Lấy email từ agency
            // Giả sử agency có trường email, nếu không có thì thử lấy từ các nguồn khác
            $email = $notification->agency->email ?? null;

            // Nếu không có email trực tiếp, thử lấy từ user_agency
            if (!$email && $notification->agency->userAgencies) {
                // Có thể lấy email từ user_agency nếu có
                // Hoặc sử dụng phone để tạo email nếu cần
                Log::channel('custom')->debug('Không có email trong agency, thử tìm từ user_agency', [
                    'agency_id' => $notification->agency_id,
                ]);
            }

            // Nếu vẫn không có email, log và return false
            if (!$email) {
                Log::channel('custom')->debug('Không tìm thấy email để gửi', [
                    'notification_id' => $notification->id,
                    'agency_id' => $notification->agency_id,
                    'agency_name' => $notification->agency->name ?? 'N/A',
                ]);
                return false;
            }

            Log::channel('custom')->debug('Đã tìm thấy email, chuẩn bị gửi', [
                'notification_id' => $notification->id,
                'email' => $email,
            ]);

            // Gửi email
            Mail::to($email)->send(new OrderStatusChangeMail($notification));

            Log::channel('custom')->info('Đã gửi email thành công', [
                'notification_id' => $notification->id,
                'email' => $email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('custom')->error('Lỗi khi gửi email notification: ' . $e->getMessage(), [
                'notification_id' => $notification->id,
                'agency_id' => $notification->agency_id,
                'error_trace' => $e->getTraceAsString(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            return false;
        }
    }
}

