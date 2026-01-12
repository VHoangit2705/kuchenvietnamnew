<?php

namespace App\Http\Controllers;

use App\Models\Kho\InstallationOrder;
use App\Models\KyThuat\RequestAgency;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CollaboratorInstallBulkController extends Controller
{
    public function BulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $ids = $request->input('ids');
        $updatedCount = 0;

        try {
            $installationOrders = InstallationOrder::whereIn('id', $ids)->get();

            foreach ($installationOrders as $installationOrder) {
                if ($installationOrder->status_install == 2) {
                    $installationOrder->status_install = 3;
                    $installationOrder->paid_at = now();
                    $installationOrder->save();

                    $orderCode = $installationOrder->order_code;
                    $productName = $installationOrder->product;

                    if ($orderCode) {
                        $requestAgency = RequestAgency::where('order_code', $orderCode)
                            ->when($productName, function ($q) use ($productName) {
                                $q->where(function ($sub) use ($productName) {
                                    $sub->whereNull('product_name')
                                        ->orWhere('product_name', $productName);
                                });
                            })
                            ->first();

                        if ($requestAgency) {
                            $requestAgency->status = RequestAgency::STATUS_DA_THANH_TOAN;
                            $requestAgency->assigned_to = session('user', 'system');
                            $requestAgency->save();

                            NotificationService::sendStatusChangeNotification(
                                $requestAgency,
                                RequestAgency::STATUS_HOAN_THANH,
                                RequestAgency::STATUS_DA_THANH_TOAN
                            );
                        }
                    }

                    $updatedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã cập nhật $updatedCount đơn hàng sang trạng thái Đã thanh toán."
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function BulkUpdateByExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excelFile' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'list_codes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $orderCodes = [];

        if ($request->filled('list_codes')) {
            $rawCodes = explode("\n", $request->input('list_codes'));
            foreach ($rawCodes as $code) {
                $trimmed = trim($code);
                if (!empty($trimmed)) {
                    $orderCodes[] = $trimmed;
                }
            }
        }

        if ($request->hasFile('excelFile')) {
            try {
                $file = $request->file('excelFile');
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestDataRow();

                for ($row = 3; $row <= $highestRow; $row++) {
                    $cellValue = $worksheet->getCell('B' . $row)->getValue();
                    if (!empty($cellValue)) {
                        $orderCodes[] = trim($cellValue);
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi đọc file Excel: ' . $e->getMessage()
                ], 500);
            }
        }

        $orderCodes = array_unique($orderCodes);

        if (empty($orderCodes)) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy mã đơn hàng nào để xử lý.'
            ], 400);
        }

        $performUpdate = $request->boolean('confirm', false);

        $updatedCount = 0;
        $notFoundCount = 0;
        $invalidStatusCount = 0;
        $previewUpdatableCount = 0;
        $updatedCodes = [];
        $notFoundCodes = [];
        $invalidStatusCodes = [];
        $statusTextMap = [
            0   => 'Chưa điều phối',
            1   => 'Đã điều phối',
            2   => 'Hoàn thành',
            3   => 'Đã thanh toán',
            null => 'Chưa điều phối',
        ];

        try {
            foreach ($orderCodes as $code) {
                $originalCode = trim($code);
                $normalizedCode = preg_replace('/\s+/', ' ', $originalCode);
                $baseCode = trim(preg_replace('/\s*\(.*\)$/', '', $normalizedCode));
                if (empty($baseCode)) {
                    $baseCode = $normalizedCode;
                }

                // Ưu tiên tìm record có status_install = 2 trước (để auto update)
                // Tìm theo nhiều cách: chính xác, và với pattern có dấu ngoặc trong DB
                $ordersWithStatus2 = InstallationOrder::where(function($query) use ($baseCode, $originalCode, $normalizedCode) {
                    $query->where(function($q) use ($baseCode, $originalCode, $normalizedCode) {
                        // Tìm chính xác với các biến thể
                        $q->where('order_code', $baseCode)
                          ->orWhere('order_code', trim($baseCode))
                          ->orWhere('order_code', $originalCode)
                          ->orWhere('order_code', trim($originalCode))
                          ->orWhere('order_code', $normalizedCode)
                          ->orWhere('order_code', trim($normalizedCode));
                        
                        // LUÔN thử tìm với LIKE để tìm cả trường hợp DB có dấu ngoặc
                        // Trường hợp 1: Input không có ngoặc, DB có ngoặc (ví dụ: input "VNI04003347668", DB "VNI04003347668 (2)")
                        // Trường hợp 2: Input có ngoặc, DB có ngoặc khác (ví dụ: input "VNI04003347668 (1)", DB "VNI04003347668 (2)")
                        // Trường hợp 3: Input thiếu số cuối, DB có số cuối + ngoặc (ví dụ: input "VNI0740312628", DB "VNI07403126287 (2)")
                        $q->orWhere('order_code', 'LIKE', $baseCode . ' (%')  // baseCode + khoảng trắng + (số)
                          ->orWhere('order_code', 'LIKE', $baseCode . '(%')   // baseCode + (số) không có khoảng trắng
                          ->orWhere('order_code', 'LIKE', $baseCode . '% (%') // baseCode + số + khoảng trắng + (số) - cho trường hợp thiếu số cuối
                          ->orWhere('order_code', 'LIKE', $baseCode . '%(%'); // baseCode + số + (số) không có khoảng trắng
                    });
                })->where('status_install', 2)->get();

                // Nếu không tìm thấy record có status_install = 2, lấy tất cả để kiểm tra
                if ($ordersWithStatus2->isEmpty()) {
                    $allOrders = InstallationOrder::where(function($query) use ($baseCode, $originalCode, $normalizedCode) {
                        $query->where(function($q) use ($baseCode, $originalCode, $normalizedCode) {
                            // Tìm chính xác với các biến thể
                            $q->where('order_code', $baseCode)
                              ->orWhere('order_code', trim($baseCode))
                              ->orWhere('order_code', $originalCode)
                              ->orWhere('order_code', trim($originalCode))
                              ->orWhere('order_code', $normalizedCode)
                              ->orWhere('order_code', trim($normalizedCode));
                            
                            // LUÔN thử tìm với LIKE để tìm cả trường hợp DB có dấu ngoặc
                            // Trường hợp 1: Input không có ngoặc, DB có ngoặc
                            // Trường hợp 2: Input có ngoặc, DB có ngoặc khác
                            // Trường hợp 3: Input thiếu số cuối, DB có số cuối + ngoặc (ví dụ: input "VNI0740312628", DB "VNI07403126287 (2)")
                            $q->orWhere('order_code', 'LIKE', $baseCode . ' (%')  // baseCode + khoảng trắng + (số)
                              ->orWhere('order_code', 'LIKE', $baseCode . '(%')   // baseCode + (số) không có khoảng trắng
                              ->orWhere('order_code', 'LIKE', $baseCode . '% (%') // baseCode + số + khoảng trắng + (số) - cho trường hợp thiếu số cuối
                              ->orWhere('order_code', 'LIKE', $baseCode . '%(%'); // baseCode + số + (số) không có khoảng trắng
                        });
                    })->get();

                    if ($allOrders->isEmpty()) {
                        $notFoundCount++;
                        $notFoundCodes[] = $originalCode;
                        continue;
                    }

                    // Xử lý các record không có status_install = 2
                    foreach ($allOrders as $installationOrder) {
                        $invalidStatusCount++;
                        $statusVal = $installationOrder->status_install;
                        $invalidStatusCodes[] = [
                            'code' => $originalCode,
                            'status' => $statusVal,
                            'status_text' => $statusTextMap[$statusVal] ?? 'Không xác định',
                        ];
                    }
                    continue;
                }

                // Xử lý các record có status_install = 2 (đã được lọc ở trên)
                foreach ($ordersWithStatus2 as $installationOrder) {
                    if ($performUpdate) {
                        $installationOrder->status_install = 3;
                        $installationOrder->paid_at = now();
                        $installationOrder->save();

                        $productName = $installationOrder->product;
                        $actualOrderCode = $installationOrder->order_code;
                        $requestAgency = RequestAgency::where('order_code', $actualOrderCode)
                            ->when($productName, function ($q) use ($productName) {
                                $q->where(function ($sub) use ($productName) {
                                    $sub->whereNull('product_name')
                                        ->orWhere('product_name', $productName);
                                });
                            })
                            ->first();

                        if ($requestAgency) {
                            $requestAgency->status = RequestAgency::STATUS_DA_THANH_TOAN;
                            $requestAgency->assigned_to = session('user', 'system');
                            $requestAgency->save();

                            NotificationService::sendStatusChangeNotification(
                                $requestAgency,
                                RequestAgency::STATUS_HOAN_THANH,
                                RequestAgency::STATUS_DA_THANH_TOAN
                            );
                        }

                        $updatedCount++;
                        $updatedCodes[] = $originalCode;
                    } else {
                        // chỉ thống kê số có thể cập nhật
                        $previewUpdatableCount++;
                        $updatedCodes[] = $originalCode;
                    }
                }
            }

            $lines = [];
            $lines[] = "Xử lý hoàn tất.";
            $lines[] = "- Tổng mã: " . count($orderCodes);
            $lines[] = $performUpdate
                ? "- Đã cập nhật: $updatedCount"
                : "- Có thể cập nhật: $previewUpdatableCount";
            $lines[] = "- Sai trạng thái (không phải 'Hoàn thành'): $invalidStatusCount";
            $lines[] = "- Không tìm thấy: $notFoundCount";

            if (!empty($invalidStatusCodes)) {
                $lines[] = "";
                $lines[] = "Sai trạng thái (mã - trạng thái hiện tại):";
                $invalidList = collect($invalidStatusCodes)->map(function ($item) {
                    $text = $item['status_text'] ?? 'Không xác định';
                    return $item['code'] . ' (' . $text . ')';
                })->implode(', ');
                $lines[] = $invalidList;
            }

            if (!empty($notFoundCodes)) {
                $lines[] = "";
                $lines[] = "Không tìm thấy:";
                $lines[] = implode(', ', $notFoundCodes);
            }

            $message = implode("\n", $lines);

            if ($performUpdate) {
                Log::channel('bulk_update_by_excel')->info('bulk_update_by_excel', [
                    'user' => session('user', 'system'),
                    'total' => count($orderCodes),
                    'updated' => $updatedCodes,
                    'invalid_status' => $invalidStatusCodes,
                    'not_found' => $notFoundCodes,
                    'timestamp' => now()->toDateTimeString(),
                ]);

                return response()->json([
                    'success' => true,
                    'confirm_required' => false,
                    'message' => $message
                ]);
            }

            // Preview only, yêu cầu xác nhận trước khi import
            return response()->json([
                'success' => true,
                'confirm_required' => true,
                'message' => $message
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
