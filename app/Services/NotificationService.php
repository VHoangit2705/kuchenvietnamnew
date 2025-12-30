<?php

namespace App\Services;

use App\Models\KyThuat\Notification;
use App\Models\KyThuat\RequestAgency;
use Illuminate\Support\Facades\Log;

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
            $title = "Trạng thái đơn hàng đã thay đổi";
            $message = sprintf(
                "Đơn hàng %s đã chuyển từ '%s' sang '%s'",
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
}

