<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\Kho\Agency;
use App\Models\Kho\OrderProduct;
use Illuminate\Support\Facades\Cache;
use App\Enum;
use App\Models\Kho\Order;
use App\Models\Kho\InstallationOrder;
use App\Models\KyThuat\WarrantyRequest;

class ImportExcelSyncController extends Controller
{
    /**
     * Đảm bảo CTV flag "Đại lý lắp đặt" với ID = 1 luôn tồn tại
     * Đây chỉ là một flag để đánh dấu, không phải thông tin thật
     */
    private function ensureAgencyCollaboratorExists()
    {
        try {
            WarrantyCollaborator::updateOrCreate(
                ['id' => Enum::AGENCY_INSTALL_FLAG_ID], // điều kiện duy nhất
                [
                    'full_name' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'phone' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'province' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'district' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'ward' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'address' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'sotaikhoan' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'chinhanh' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'cccd' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Lỗi tạo/đồng bộ CTV flag Đại lý lắp đặt: ' . $e->getMessage());
        }
    }
    
    /**
     * Chuẩn hóa và validate trường product
     */
    private function normalizeProduct($product)
    {
        $product = trim($product ?? '');
        if (empty($product)) {
            return 'Không xác định';
        }
        
        // Đảm bảo encoding UTF-8 đúng
        $product = mb_convert_encoding($product, 'UTF-8', 'auto');
        
        // Chỉ loại bỏ ký tự điều khiển, giữ lại ký tự tiếng Việt
        $product = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $product);
        
        return $product;
    }
    
    /**
     * Đồng bộ dữ liệu từ Excel với logic upsert cho các bảng liên quan
     * Tối ưu hóa cho file lớn với nhiều sheet
     */
    public function ImportExcelSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excelFile' => 'required|file|mimes:xlsx,xls|max:51200', // Tăng limit lên 50MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Tăng thời gian thực thi và memory cho file lớn
        ini_set('memory_limit', '4096M');      // 4GB memory
        ini_set('max_execution_time', '3600');  // 60 phút
        set_time_limit(3600);                   // 60 phút
        ini_set('default_socket_timeout', '300'); // 5 phút cho socket timeout

        try {
            $file = $request->file('excelFile');
            
            // Tối ưu hóa việc đọc Excel - cải thiện để xử lý ngày tháng
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false); // Đọc cả formatting để xử lý ngày tháng đúng
            $reader->setReadEmptyCells(false); // Bỏ qua ô trống
            $reader->setReadFilter(new \PhpOffice\PhpSpreadsheet\Reader\DefaultReadFilter()); // Đọc tất cả dữ liệu
            
            // Cấu hình để xử lý ngày tháng đúng
            \PhpOffice\PhpSpreadsheet\Shared\Date::setExcelCalendar(\PhpOffice\PhpSpreadsheet\Shared\Date::CALENDAR_WINDOWS_1900);
            
            $spreadsheet = $reader->load($file->getRealPath());
            $sheetCount = $spreadsheet->getSheetCount();

            $stats = [
                'imported' => 0,
                'collaborators_created' => 0,
                'agencies_created' => 0,
                'products_created' => 0,
                'order_products_created' => 0,
                'orders_created' => 0,
                'orders_updated' => 0,
                'installation_orders_created' => 0,
                'installation_orders_updated' => 0,
                'warranty_requests_created' => 0,
                'warranty_requests_updated' => 0,
                'sheets_processed' => 0,
                'errors' => []
            ];

            // Cache để tối ưu performance - sử dụng array để tăng tốc độ lookup
            $collaboratorCache = [];
            $agencyCache = [];
            $productCache = [];
            
            // ĐẢM BẢO CTV flag "Đại lý lắp đặt" với ID = 1 LUÔN TỒN TẠI trước khi import
            $this->ensureAgencyCollaboratorExists();
            
            // Pre-load existing data để giảm database queries
            $existingCollaborators = WarrantyCollaborator::pluck('id', 'phone')->toArray();
            $existingAgencies = Agency::pluck('id', 'phone')->toArray();
            $existingProducts = OrderProduct::pluck('id', 'product_name')->toArray();
            
            // Cache CTV flag để tránh query lặp lại - ĐẢM BẢO flag đã tồn tại
            $flagCollaborator = WarrantyCollaborator::find(Enum::AGENCY_INSTALL_FLAG_ID);
            $flagPhone = $flagCollaborator ? $flagCollaborator->phone : null;
            $flagName = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
            
            // Đảm bảo flag ID = 1 có trong existingCollaborators để tránh tạo trùng
            if ($flagCollaborator && $flagPhone) {
                $existingCollaborators[$flagPhone] = Enum::AGENCY_INSTALL_FLAG_ID;
            }

            // Hàm chuẩn hóa số điện thoại - tối ưu hóa
            $sanitizePhone = function ($value) {
                if (empty($value)) return '';
                $digits = preg_replace('/\D+/', '', $value);
                return mb_strlen($digits) > 11 ? mb_substr($digits, -11) : $digits;
            };

            // Tạo map phone đã chuẩn hóa cho Agency để chống tạo trùng khi định dạng khác nhau
            $existingAgenciesByNormalizedPhone = [];
            foreach ($existingAgencies as $phone => $id) {
                $normalized = null;
                try { $normalized = preg_replace('/\D+/', '', (string)$phone); } catch (\Throwable $e) { $normalized = (string)$phone; }
                if ($normalized !== null && $normalized !== '') {
                    $normalized = mb_strlen($normalized) > 11 ? mb_substr($normalized, -11) : $normalized;
                    $existingAgenciesByNormalizedPhone[$normalized] = $id;
                }
            }

            // Hàm chuẩn hóa ngày tháng - cải thiện để xử lý nhiều format
            $parseDate = function ($dateRaw) {
                if (empty($dateRaw)) return null;
                
                $dateRaw = trim($dateRaw);
                
                // 1. Kiểm tra Excel date serial number (số nguyên hoặc số thập phân)
                if (is_numeric($dateRaw)) {
                    try {
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw);
                        $result = $excelDate->format('Y-m-d H:i:s');
                        return $result;
                    } catch (\Exception $e) {
                        Log::warning('Excel serial date parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                    }
                }
                
                // 2. Xử lý đặc biệt cho trường hợp Excel có thể lưu m/d/Y nhưng cần hiểu là d-m-Y
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateRaw, $matches)) {
                    $first = intval($matches[1]);
                    $second = intval($matches[2]);
                    $year = $matches[3];
                    
                    // LUÔN thử d-m-Y trước (ngày-tháng-năm) - đây là format Việt Nam
                    try {
                        $date = Carbon::createFromFormat('d-m-Y', $first . '-' . $second . '-' . $year);
                        if ($date->isValid()) {
                            $result = $date->format('Y-m-d H:i:s');
                            return $result;
                        }
                    } catch (\Exception $e) {
                        Log::warning('d-m-Y parse failed, trying m-d-Y', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                        // Nếu d-m-Y không hợp lệ, thử m-d-Y
                        try {
                            $date = Carbon::createFromFormat('m-d-Y', $first . '-' . $second . '-' . $year);
                            if ($date->isValid()) {
                                $result = $date->format('Y-m-d H:i:s');
                                return $result;
                            }
                        } catch (\Exception $e2) {
                            Log::warning('Both d-m-Y and m-d-Y failed', ['raw' => $dateRaw, 'error' => $e2->getMessage()]);
                            // Tiếp tục thử các format khác
                        }
                    }
                }

                // 3. Thử parse với Carbon (tự động detect format)
                try {
                    $date = Carbon::parse($dateRaw);
                    if ($date->isValid()) {
                        $result = $date->format('Y-m-d H:i:s');
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('Carbon parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                }
                
                // 4. Thử các format phổ biến của Việt Nam - Ưu tiên d-m-y
                $commonFormats = [
                    'd-m-Y',           // 25-12-2024 (Việt Nam - ưu tiên cao nhất)
                    'd/m/Y',           // 25/12/2024 (Việt Nam)
                    'd.m.Y',           // 25.12.2024 (Việt Nam)
                    'd-m-y',           // 25-12-24 (Việt Nam - năm 2 chữ số)
                    'd/m/y',           // 25/12/24 (Việt Nam - năm 2 chữ số)
                    'Y-m-d',           // 2024-12-25 (ISO)
                    'Y/m/d',           // 2024/12/25 (ISO)
                    'd/m/Y H:i:s',     // 25/12/2024 10:30:00
                    'd-m-Y H:i:s',     // 25-12-2024 10:30:00
                    'Y-m-d H:i:s',     // 2024-12-25 10:30:00
                    'd/m/Y H:i',       // 25/12/2024 10:30
                    'd-m-Y H:i',       // 25-12-2024 10:30
                    'Y-m-d H:i',       // 2024-12-25 10:30
                ];
                
                foreach ($commonFormats as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, $dateRaw);
                        if ($date->isValid()) {
                            $result = $date->format('Y-m-d H:i:s');
                            return $result;
                        }
                    } catch (\Exception $e) {
                        // Tiếp tục thử format tiếp theo
                    }
                }
                
                // 5. Thử parse với strtotime (fallback)
                try {
                    $timestamp = strtotime($dateRaw);
                    if ($timestamp !== false) {
                        $result = date('Y-m-d H:i:s', $timestamp);
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('strtotime parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                }
                
                Log::error('All date parsing methods failed', ['raw' => $dateRaw]);
                return null;
            };

            // Hàm đổi giá trị trống thành 'N/A' để hiển thị nhất quán
            $na = function ($value) {
                if (is_null($value)) return 'N/A';
                if (is_string($value)) {
                    return trim($value) === '' ? 'N/A' : $value;
                }
                return $value === '' ? 'N/A' : $value;
            };

            // Hàm kiểm tra ô có bị merge hay không
            $isMergedCell = function ($sheet, $cellCoordinate) {
                try {
                    $mergedRanges = $sheet->getMergeCells();
                    foreach ($mergedRanges as $range) {
                        if ($sheet->getCell($cellCoordinate)->isInRange($range)) {
                            return true;
                        }
                    }
                    return false;
                } catch (\Exception $e) {
                    return false;
                }
            };

            // Hàm lấy giá trị từ ô, xử lý merged cells và ngày tháng - tối ưu cho việc đọc ít dòng
            $getCellValue = function ($sheet, $cellCoordinate) use ($isMergedCell) {
                try {
                    // Chỉ đọc cell khi cần thiết, không load toàn bộ sheet
                    $cell = $sheet->getCell($cellCoordinate, false); // false = không tính toán lại
                    $value = $cell->getCalculatedValue();
                    
                    // Nếu ô bị merge và giá trị trống, thử lấy từ ô đầu tiên của range
                    if (empty($value) && $isMergedCell($sheet, $cellCoordinate)) {
                        $mergedRanges = $sheet->getMergeCells();
                        foreach ($mergedRanges as $range) {
                            if ($sheet->getCell($cellCoordinate)->isInRange($range)) {
                                $rangeArray = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($range);
                                $startRow = $rangeArray[0][1];
                                $startCol = $rangeArray[0][0];
                                $startCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol) . $startRow;
                                $value = $sheet->getCell($startCell, false)->getCalculatedValue();
                                break;
                            }
                        }
                    }
                    
                    // Xử lý đặc biệt cho ngày tháng
                    if ($value instanceof \DateTime) {
                        // Nếu là DateTime object, chuyển về string
                        $value = $value->format('Y-m-d H:i:s');
                    } elseif (is_numeric($value) && $cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                        // Kiểm tra xem có phải là Excel date serial number không
                        try {
                            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                            $value = $excelDate->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            // Nếu không phải ngày, giữ nguyên giá trị số
                        }
                    }
                    
                    // Đảm bảo encoding UTF-8 cho tất cả giá trị string
                    if (is_string($value)) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                    
                    return $value;
                } catch (\Exception $e) {
                    Log::warning('Error getting cell value', [
                        'cell' => $cellCoordinate,
                        'error' => $e->getMessage()
                    ]);
                    return '';
                }
            };

            // Hàm chuẩn hóa trạng thái - tối ưu hóa và xử lý lộn xộn
            $parseStatus = function ($statusRaw) {
                // Nếu cột L (trạng thái) rỗng → trạng thái chưa điều phối
                if (empty($statusRaw)) return 0;
                
                // Loại bỏ khoảng trắng thừa và chuyển về chữ thường
                $statusLower = mb_strtolower(trim($statusRaw));
                
                // Loại bỏ các ký tự đặc biệt và số thừa
                $statusClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $statusLower);
                $statusClean = preg_replace('/\s+/', ' ', $statusClean);
                $statusClean = trim($statusClean);
                
                // Xử lý đặc biệt cho nhóm "Đại lý tự lắp/Đại lý lắp đặt" → đã điều phối (1)
                $agencySelfInstallStatuses = [
                    'đại lý tự lắp', 'đl đl tự lắp', 'đại lý lắp đặt', 'đl lắp đặt',
                    'agency self install', 'dealer install', 'đại lý tự làm',
                    'đl tự lắp', 'đại lý tt', 'đại lý lắp đặt tt', 'đại lý lắp đặt, đã tt',
                    'đại lý thanh toán', 'đlttoán', 'đltt', 'đl tt'
                ];
                
                foreach ($agencySelfInstallStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 1;
                }
                
                // Trạng thái đã thanh toán (3)
                $paidStatuses = [
                    'đã thanh toán', 'thanh toán', 'đã trả', 'trả tiền', 'paid', 'payment',
                    'đã chi', 'chi trả', 'hoàn tất thanh toán', 'thanh toán xong',
                    'khách tt', 'khách thanh toán', 'khách tt phí', 'khách tt tiền', 'khách tt công',
                    'đã tt', 'đã tt rồi', 'đã tt ctv', 'đã tt cho ctv', 'đã tt tiền cho ctv',
                    'đã tt tiền công', 'đã tt phí', 'đã tt phí cho ctv', 'đã tt c t v',
                    'đl thanh toán', 'đại lý thanh toán', 'đại lý tt', 'đã tt tháng',
                    'đã tt tiền công cho ctv', 'đã tt tiền cho ctv', 'đã tt cho c t v',
                    'đã tt rồi', 'đã tt công', 'đã tt phí rồi', 'đã tt phí cho c t v',
                    'đã thanh toán cho ctv', 'đã thanh toán ctv', 'đã tt c t v', 'đã tt ctv phí',
                    'đã thanh toán, khách', 'đã tt cho ctv rồi'
                ];
                
                // Trạng thái hoàn thành (2) 
                $completedStatuses = [
                    'đã hoàn thành', 'hoàn thành', 'done', 'x', '1', 'yes', 'ok', 'okay',
                    'xong', 'hoàn tất', 'kết thúc', 'finish', 'completed', 'success',
                    'đã xong', 'đã làm xong', 'đã lắp xong', 'lắp xong',
                    'đã xử lý', 'đã xử lý xong', 'k phải đến nữa', 'không phải đến nữa',
                    'máy khách đã ổn định', 'đã ổn định', 'bếp đã ổn định', 'k tính phí',
                    'đã hỗ trợ', 'đã hỗ trợ đại lý', 'đã mở dc k phải sang', 'xong 1 lần',
                    'khách tự xử lý', 'khách tự thay', 'khách tự lắp', 'thu phí bên khách',
                    'đổi bếp mới cho khách', 'k phải điều nữa', 'k phải đi nữa',
                    'kho hn đã xử lý xong', 'kho hn xử lý', 'kho hcm', 'kỹ thuật hn xử lý',
                    'kỹ thuật hd', 'kỹ thuật kho sg đi', 'kt kuchen đi', 'hd xử lý',
                    'đã gửi số thợ', 'đang đã hoàn thành', 'khách đang đã hoàn thành'
                ];
                
                // Trạng thái đang xử lý (Đã điều phối) (1)
                $processingStatuses = [
                    'đang xử lý', 'đang làm', 'đang theo dõi', 'đang thực hiện', 'đang lắp',
                    'processing', 'in progress', 'đang tiến hành',
                    'đang lắp đặt', 'lắp đặt', 'đang thi công', 'thi công',
                    'ctv bận', 'gọi thuê bao', 'báo bận', 'chưa có mạch',
                    'sẽ báo', 'báo sau', 'có gì báo sau', 'có gì sẽ báo sau', 'có gì báo lại', 'báo lại sau',
                    'lúc nào lắp sẽ báo', 'lúc nào lắp sẽ gọi', 'lúc nào lắp báo', 'lúc nào lắp sẽ báo lại',
                    'ra tết lắp', 'mai lắp', 'tuần sau', 'chờ khách trả lời', 'chờ báo', 'chờ báo lại',
                    'chờ khách', 'chờ xuất kho mới gọi', 'chờ đại lý báo lại', 'chờ đại lý cho số khác',
                    'khi nào lắp sẽ báo', 'khi nào lắp sẽ gọi', 'co gi bao sau', 'có gì báo lại sau',
                    'đang chờ', 'theo dõi', 'đang theo dõi', 'nước chảy khắp nhà  đang xl'
                ];
                
                // Trạng thái chưa điều phối (0)
                $notAssignedStatuses = [
                    'chưa điều phối', 'chưa giao', 'chưa làm', 'pending', 'waiting', 'chưa lắp',
                    'chưa xong', 'chưa đến', 'chưa liên hệ', 'chưa gọi dc', 'chưa gọi được',
                    'chưa lấy', 'chưa lấy hàng', 'chưa có phiếu', 'chưa xuất kho', 'chưa gửi pk',
                    'chưa phải xử lý', 'chưa phải đến', 'hủy', 'huỷ', 'hủy đơn', 'bỏ', 'hoãn lại chưa điều',
                    'chưa tt do chưa cung cấp tk'
                ];
                
                // Kiểm tra từng nhóm trạng thái
                foreach ($paidStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 3;
                }
                
                foreach ($completedStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 2;
                }
                
                foreach ($processingStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 1;
                }
                
                foreach ($notAssignedStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 0;
                }
                
                // Nếu không khớp với bất kỳ pattern nào, mặc định là đang xử lý (1)
                return 1;
            };

            // Xử lý tất cả sheet
            $startSheet = 0; // Bắt đầu từ sheet đầu tiên
            $endSheet = $sheetCount; // Đến sheet cuối cùng

            for ($s = $startSheet; $s < $endSheet; $s++) {
                try {
                    $currentSheet = $spreadsheet->getSheet($s);
                    $sheetName = $currentSheet->getTitle();
                    
                    // Chỉ lấy dữ liệu cần thiết, không load toàn bộ sheet
                    $highestRow = $currentSheet->getHighestDataRow();
                    
                    // Bỏ qua sheet trống
                    if ($highestRow <= 1) {
                        continue;
                    }

                    $stats['sheets_processed']++;


                    // Xử lý từng dòng - đọc đến dòng cuối cùng có dữ liệu
                    for ($row = 3; $row <= $highestRow; $row++) { // Bắt đầu từ dòng 3 (bỏ header dòng 1 và 2)
                        try {
                            // Lấy dữ liệu từ các cột cụ thể - sử dụng getCellValue để xử lý merged cells
                            $orderCode = trim($getCellValue($currentSheet, 'Q' . $row) ?? '');
                            
                            // Nếu không có mã đơn hàng, đặt giá trị null
                            if (empty($orderCode)) {
                                $orderCode = null;
                            }

                            // Xử lý ngày - cải thiện để xử lý nhiều trường hợp
                            $dateRaw = trim($getCellValue($currentSheet, 'B' . $row) ?? '');
                            
                            $parsedDate = $parseDate($dateRaw);
                            
                            if ($parsedDate) {
                                $createdAt = $parsedDate;
                            } else {
                                // Nếu ô ngày trống hoặc không parse được, để null
                                $createdAt = null;
                            }

                            $agencyName = trim($getCellValue($currentSheet, 'C' . $row) ?? '');
                            $agencyPhoneRaw = trim($getCellValue($currentSheet, 'D' . $row) ?? '');
                            $customerName = trim($getCellValue($currentSheet, 'F' . $row) ?? '');
                            $customerPhoneRaw = trim($getCellValue($currentSheet, 'G' . $row) ?? '');
                            $customerAddress = trim($getCellValue($currentSheet, 'H' . $row) ?? '');
                            $product = $this->normalizeProduct($getCellValue($currentSheet, 'I' . $row) ?? '');
                            $collabName = trim($getCellValue($currentSheet, 'J' . $row) ?? '');
                            $collabPhoneRaw = trim($getCellValue($currentSheet, 'K' . $row) ?? '');
                            $statusRaw = trim($getCellValue($currentSheet, 'L' . $row) ?? '');
                            $collabAccount = trim($getCellValue($currentSheet, 'M' . $row) ?? '');
                            $bank = trim($getCellValue($currentSheet, 'N' . $row) ?? '');
                            $note = trim($getCellValue($currentSheet, 'O' . $row) ?? '');
                            $accessories = trim($getCellValue($currentSheet, 'P' . $row) ?? '');

                            // Chuẩn hóa dữ liệu
                            $agencyPhone = $sanitizePhone($agencyPhoneRaw);
                            $customerPhone = $sanitizePhone($customerPhoneRaw);
                            $collabPhone = $sanitizePhone($collabPhoneRaw);

                            // Giá trị hiển thị với 'N/A' cho trường trống
                            $agencyNameDisplay = $na($agencyName);
                            $customerNameDisplay = $na($customerName);
                            $customerAddressDisplay = $na($customerAddress);
                            $productDisplay = $na($product);
                            $statusRawDisplay = $na($statusRaw);
                            $collabNameDisplay = $na($collabName);
                            $bankDisplay = $na($bank);
                            $noteDisplay = $na($note);
                            $accessoriesDisplay = $na($accessories);
                            $agencyPhoneDisplay = $agencyPhone ?: 'N/A';
                            $customerPhoneDisplay = $customerPhone ?: 'N/A';
                            $collabPhoneDisplay = $collabPhone ?: 'N/A';

                            // 1. Xử lý Collaborator (CTV) & Đại lý tự lắp
                            $collaboratorId = null; // Default null

                            // Kiểm tra xem có phải "Đại lý tự lắp/Đại lý lắp đặt" không
                            // Kiểm tra từ statusRaw, collabName và collabPhone - ƯU TIÊN KIỂM TRA TRƯỚC
                            $isAgencySelfInstall = false;
                            
                            // BƯỚC 1: Kiểm tra từ statusRaw (cột L trong Excel)
                            if (!empty($statusRaw)) {
                                $statusLower = mb_strtolower(trim($statusRaw));
                                $statusClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $statusLower);
                                $statusClean = preg_replace('/\s+/', ' ', $statusClean);
                                $statusClean = trim($statusClean);

                                $agencySelfInstallStatuses = [
                                    'đại lý tự lắp', 'đại lý lắp đặt', 'đl đl tự lắp', 'đl lắp đặt',
                                    'Đại Lý tự lắp', 'đại lý lắp đặt', 'ĐL tự lắp', 'đại lý tự làm'
                                ];

                                foreach ($agencySelfInstallStatuses as $status) {
                                    if (strpos($statusClean, $status) !== false) {
                                        $isAgencySelfInstall = true;
                                        break;
                                    }
                                }
                            }
                            
                            // BƯỚC 2: Kiểm tra từ collabName - nếu tên CTV = "Đại lý lắp đặt" thì cũng coi là đại lý tự lắp
                            if (!$isAgencySelfInstall && !empty($collabName)) {
                                $collabNameLower = mb_strtolower(trim($collabName));
                                $collabNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $collabNameLower);
                                $collabNameClean = preg_replace('/\s+/', ' ', $collabNameClean);
                                $collabNameClean = trim($collabNameClean);
                                
                                $agencyInstallLabel = mb_strtolower(trim(Enum::AGENCY_INSTALL_CHECKBOX_LABEL));
                                $agencyInstallLabelClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $agencyInstallLabel);
                                $agencyInstallLabelClean = preg_replace('/\s+/', ' ', $agencyInstallLabelClean);
                                $agencyInstallLabelClean = trim($agencyInstallLabelClean);
                                
                                // Kiểm tra khớp chính xác hoặc chứa từ khóa
                                if ($collabNameClean === $agencyInstallLabelClean || 
                                    strpos($collabNameClean, 'đại lý lắp đặt') !== false ||
                                    strpos($collabNameClean, 'đại lý tự lắp') !== false) {
                                    $isAgencySelfInstall = true;
                                }
                            }
                            
                            // BƯỚC 3: Kiểm tra xem collabPhone có trùng với phone của CTV flag không
                            if (!$isAgencySelfInstall && !empty($collabPhone) && $flagPhone) {
                                if ($flagPhone === $collabPhone) {
                                    $isAgencySelfInstall = true;
                                }
                            }

                            // Nếu phát hiện là "Đại lý lắp đặt" → CHỈ sử dụng flag ID, KHÔNG tạo CTV mới
                            if ($isAgencySelfInstall) {
                                $statusInstall = $parseStatus($statusRaw); // Vẫn parse status để lấy đúng trạng thái
                                $collaboratorId = Enum::AGENCY_INSTALL_FLAG_ID;
                                // KHÔNG tạo CTV mới, chỉ dùng flag ID
                            } else {
                                // Không ép trạng thái về 0 khi thiếu CTV; dùng trạng thái từ file Excel
                                $parsedStatus = $parseStatus($statusRaw);
                                $statusInstall = $parsedStatus; // 0/1/2/3 theo mô tả

                                // Nếu có CTV thật, tạo/tìm CTV và gán id; nếu trống thì để NULL
                                $isCtvEmpty = empty($collabName) || empty($collabPhone);
                                if (!$isCtvEmpty) {
                                    // KIỂM TRA LẠI: Xem có phải là CTV flag không (tránh tạo trùng)
                                    // Kiểm tra theo tên TRƯỚC - QUAN TRỌNG: không tạo CTV mới nếu tên = "Đại lý lắp đặt"
                                    $collabNameLower = mb_strtolower(trim($collabName));
                                    $collabNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $collabNameLower);
                                    $collabNameClean = preg_replace('/\s+/', ' ', $collabNameClean);
                                    $collabNameClean = trim($collabNameClean);
                                    
                                    $flagNameLower = mb_strtolower(trim($flagName));
                                    $flagNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $flagNameLower);
                                    $flagNameClean = preg_replace('/\s+/', ' ', $flagNameClean);
                                    $flagNameClean = trim($flagNameClean);
                                    
                                    $isFlagName = $collabNameClean === $flagNameClean || 
                                                  strpos($collabNameClean, 'đại lý lắp đặt') !== false ||
                                                  strpos($collabNameClean, 'đại lý tự lắp') !== false;
                                    
                                    // Kiểm tra theo phone
                                    $isFlagPhone = $flagPhone && $flagPhone === $collabPhone;
                                    
                                    // QUAN TRỌNG: Nếu tên hoặc phone trùng với flag → LUÔN dùng flag ID, KHÔNG tạo mới
                                    if ($isFlagPhone || $isFlagName) {
                                        // Nếu là CTV flag, sử dụng flag ID thay vì tạo mới
                                        $collaboratorId = Enum::AGENCY_INSTALL_FLAG_ID;
                                    } else {
                                        // Chỉ tạo CTV mới nếu KHÔNG phải là flag
                                        // Kiểm tra thêm: nếu tên có chứa "đại lý" và "lắp đặt" → cũng không tạo mới
                                        if (strpos($collabNameClean, 'đại lý') !== false && 
                                            (strpos($collabNameClean, 'lắp đặt') !== false || strpos($collabNameClean, 'tự lắp') !== false)) {
                                            // Tên có vẻ là "Đại lý lắp đặt" → dùng flag ID
                                            $collaboratorId = Enum::AGENCY_INSTALL_FLAG_ID;
                                        } else {
                                            // CTV thật, có thể tạo mới
                                            if (!isset($collaboratorCache[$collabPhone])) {
                                                if (isset($existingCollaborators[$collabPhone])) {
                                                    $collaboratorCache[$collabPhone] = $existingCollaborators[$collabPhone];
                                                } else {
                                                    // Kiểm tra lại trong database để đảm bảo không tạo trùng
                                                    $existingCollaborator = WarrantyCollaborator::where('phone', $collabPhone)->first();
                                                    if ($existingCollaborator) {
                                                        // Đã tồn tại trong database, sử dụng ID hiện có
                                                        $collaboratorCache[$collabPhone] = $existingCollaborator->id;
                                                        // Cập nhật vào existingCollaborators để các dòng sau không phải query lại
                                                        $existingCollaborators[$collabPhone] = $existingCollaborator->id;
                                                    } else {
                                                        // Chưa tồn tại, tạo mới CTV thật
                                                        try {
                                                            $collaborator = new WarrantyCollaborator();
                                                            $collaborator->full_name = $collabName;
                                                            $collaborator->phone = $collabPhone;
                                                            $collaborator->sotaikhoan = $collabAccount;
                                                            $collaborator->chinhanh = $bank;
                                                            $collaborator->created_at = now();
                                                            $collaborator->save();

                                                            $collaboratorCache[$collabPhone] = $collaborator->id;
                                                            // Cập nhật vào existingCollaborators để các dòng sau không phải query lại
                                                            $existingCollaborators[$collabPhone] = $collaborator->id;
                                                            $stats['collaborators_created']++;
                                                        } catch (\Exception $e) {
                                                            // Nếu lỗi do duplicate (có thể xảy ra trong trường hợp race condition)
                                                            // Thử tìm lại trong database
                                                            $existingCollaborator = WarrantyCollaborator::where('phone', $collabPhone)->first();
                                                            if ($existingCollaborator) {
                                                                $collaboratorCache[$collabPhone] = $existingCollaborator->id;
                                                                $existingCollaborators[$collabPhone] = $existingCollaborator->id;
                                                            } else {
                                                                $stats['errors'][] = "Lỗi tạo collaborator: " . $e->getMessage();
                                                                $collaboratorCache[$collabPhone] = null;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            $collaboratorId = $collaboratorCache[$collabPhone];
                                        }
                                    }
                                } else {
                                    $collaboratorId = null;
                                }
                            }
                            
                            // ĐẢM BẢO CUỐI CÙNG: Nếu collaboratorId trùng với flag ID, không được tạo CTV mới
                            if ($collaboratorId == Enum::AGENCY_INSTALL_FLAG_ID || $collaboratorId === Enum::AGENCY_INSTALL_FLAG_ID) {
                                $collaboratorId = Enum::AGENCY_INSTALL_FLAG_ID;
                            }

                            // 2. Xử lý Agency - Tối ưu hóa với pre-loaded data
                            if (!empty($agencyName) && !empty($agencyPhone)) {
                                if (!isset($agencyCache[$agencyPhone])) {
                                    // Kiểm tra trong pre-loaded data trước (theo phone đã chuẩn hóa)
                                    if (isset($existingAgencies[$agencyPhone]) || isset($existingAgenciesByNormalizedPhone[$agencyPhone])) {
                                        $agencyId = $existingAgencies[$agencyPhone] ?? $existingAgenciesByNormalizedPhone[$agencyPhone];
                                        $agencyCache[$agencyPhone] = $agencyId;
                                    } else {
                                        try {
                                            // Tránh tạo trùng lần cuối bằng cách kiểm tra DB theo phone chuẩn hóa
                                            $existing = Agency::where('phone', $agencyPhone)->first();
                                            if ($existing) {
                                                $agencyCache[$agencyPhone] = $existing->id;
                                            } else {
                                                // Tạo agency mới
                                                $agency = new Agency();
                                                $agency->name = $agencyName;
                                                $agency->phone = $agencyPhone;
                                                $agency->sotaikhoan = $collabAccount;
                                                $agency->chinhanh = $bank;
                                                $agency->created_ad = now();
                                                $agency->save();

                                                $agencyCache[$agencyPhone] = $agency->id;
                                                // Cập nhật maps để các dòng sau nhận diện
                                                $existingAgencies[$agencyPhone] = $agency->id;
                                                $existingAgenciesByNormalizedPhone[$agencyPhone] = $agency->id;
                                                $stats['agencies_created']++;
                                            }
                                        } catch (\Exception $e) {
                                            $stats['errors'][] = "Lỗi tạo agency: " . $e->getMessage();
                                        }
                                    }
                                }
                            }

                            // 3. Xử lý Product - Tối ưu hóa với pre-loaded data
                            if (!empty($product) && !isset($productCache[$product])) {
                                // Kiểm tra trong pre-loaded data
                                if (!isset($existingProducts[$product])) {
                                    $stats['products_created']++;
                                }
                                $productCache[$product] = true;
                            }

                            // 4. Tạo/Cập nhật Order - Tối ưu hóa
                            $order = null;
                            if ($orderCode) {
                                $order = Order::where('order_code2', $orderCode)->first();
                            }
                            
                            if (!$order) {
                                $order = new Order();
                                $order->order_code2 = $orderCode;
                                // Chỉ set created_at nếu có ngày từ Excel, không tự động thêm now()
                                if ($createdAt) {
                                    $order->created_at = $createdAt;
                                } else {
                                    // KHÔNG set created_at, để database tự xử lý (sẽ là NULL)
                                }
                                $stats['orders_created']++;
                            } else {
                                $stats['orders_updated']++;
                            }
                            
                            // Cập nhật Order với trạng thái đã được xử lý
                            $order->fill([
                                'customer_name' => $customerNameDisplay,
                                'customer_phone' => $customerPhoneDisplay,
                                'customer_address' => $customerAddressDisplay,
                                'agency_name' => $agencyNameDisplay,
                                'agency_phone' => $agencyPhoneDisplay,
                                'collaborator_id' => $collaboratorId,
                                'status_install' => $statusInstall,
                                'successed_at' => ($statusInstall == 2 && $createdAt) ? $createdAt : null,
                                'payment_method' => 'cash',
                                'status' => 'Đã quét QR',
                                'status_tracking' => 'Giao thành công',
                                'staff' => 'system',
                                'zone' => 'Đồng bộ từ file cũ',
                                'type' => 'online',
                                'shipping_unit' => 'default',
                                'send_camon' => 0,
                                'send_khbh' => 0,
                                'ip_rate' => '',
                                'note' => $statusRawDisplay, // Lưu trạng thái gốc để hiển thị
                                'note_admin' => '',
                                'check_return' => 0
                            ]);
                            $order->save();
                            
                            // 4.1. Tạo OrderProduct nếu có sản phẩm và order đã được tạo
                            if (!empty($product) && $order) {
                                $orderProduct = OrderProduct::where('order_id', $order->id)
                                    ->where('product_name', $product)
                                    ->first();
                                
                                if (!$orderProduct) {
                                    $orderProduct = new OrderProduct();
                                    $orderProduct->order_id = $order->id;
                                    $orderProduct->product_name = $product;
                                    $orderProduct->sub_address = $customerAddress;
                                    $orderProduct->install = 1; // Đánh dấu cần lắp đặt
                                    $orderProduct->quantity = 1;
                                    $orderProduct->excluding_VAT = 0;
                                    $orderProduct->VAT = '0%';
                                    $orderProduct->VAT_price = 0;
                                    $orderProduct->price = 0;
                                    $orderProduct->price_difference = 0;
                                    $orderProduct->is_promotion = false;
                                    $orderProduct->warranty_scan = 0;
                                    $orderProduct->save();
                                    
                                    $stats['order_products_created']++;
                                }
                            }

                            // 5. Tạo/Cập nhật InstallationOrder - Xử lý tất cả trạng thái
                            if ($statusInstall > 0) {
                                $installationOrder = null;
                                if ($orderCode) {
                                    $installationOrder = InstallationOrder::where('order_code', $orderCode)->first();
                                }
                                
                                if (!$installationOrder) {
                                    $installationOrder = new InstallationOrder();
                                    $stats['installation_orders_created']++;
                                } else {
                                    $stats['installation_orders_updated']++;
                                }
                                
                                $installationOrder->fill([
                                    'order_code' => $orderCode,
                                    'full_name' => $customerNameDisplay,
                                    'phone_number' => $customerPhoneDisplay,
                                    'address' => $customerAddressDisplay,
                                    'product' => $productDisplay,
                                    'collaborator_id' => $collaboratorId,
                                    'status_install' => $statusInstall,
                                    'reviews_install' => $noteDisplay . ($statusRaw ? ' | Trạng thái gốc: ' . $statusRawDisplay : ''),
                                    'agency_name' => $agencyNameDisplay,
                                    'agency_phone' => $agencyPhoneDisplay,
                                    'zone' => 'Đồng bộ từ file cũ',
                                    'type' => 'donhang',
                                    'successed_at' => ($statusInstall == 2 && $createdAt) ? $createdAt : null
                                ]);
                                
                                // Chỉ set created_at nếu có ngày từ Excel
                                if ($createdAt) {
                                    $installationOrder->created_at = $createdAt;
                                }
                                $installationOrder->save();
                            }

                            // 6. Tạo/Cập nhật WarrantyRequest - Xử lý tất cả trạng thái
                            if (!empty($product)) {
                                $warrantyRequest = null;
                                if ($orderCode) {
                                    $warrantyRequest = WarrantyRequest::where('serial_number', $orderCode)->first();
                                }
                                
                                if (!$warrantyRequest) {
                                    $warrantyRequest = new WarrantyRequest();
                                    $warrantyRequest->serial_number = $orderCode;
                                    $stats['warranty_requests_created']++;
                                } else {
                                    $stats['warranty_requests_updated']++;
                                }
                                
                                $warrantyEnd = $createdAt ? Carbon::parse($createdAt)->addYear() : null;
                                $warrantyRequest->fill([
                                    'product' => $productDisplay,
                                    'full_name' => $customerNameDisplay,
                                    'phone_number' => $customerPhoneDisplay,
                                    'address' => $customerAddressDisplay,
                                    'collaborator_id' => $collaboratorId,
                                    'agency_name' => $agencyNameDisplay,
                                    'agency_phone' => $agencyPhoneDisplay,
                                    'status_install' => $statusInstall,
                                    'Ngaytao' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'type' => 'agent_home',
                                    'branch' => 'Đồng bộ từ file cũ',
                                    'zone' => 'Đồng bộ từ file cũ',
                                    'return_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'shipment_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'received_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'warranty_end' => $warrantyEnd,
                                    'staff_received' => 'system'
                                ]);
                                
                                $warrantyRequest->save();
                            }

                            $stats['imported']++;
                            
                            // Giải phóng memory sau mỗi 50 dòng để tối ưu performance
                            if ($row % 50 == 0) {
                                gc_collect_cycles(); // Garbage collection
                            }

                        } catch (\Exception $e) {
                            $stats['errors'][] = "Sheet $sheetName, Dòng $row: " . $e->getMessage();
                        }
                    }

                    // Giải phóng memory sau mỗi sheet
                    $currentSheet->disconnectCells();
                    unset($currentSheet);
                    
                } catch (\Exception $e) {
                    $stats['errors'][] = "Lỗi xử lý sheet $s: " . $e->getMessage();
                }
            }

            // Giải phóng memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Tạo thông báo chi tiết
            $message = "Đồng bộ thành công! Đã xử lý {$stats['imported']} dòng từ {$stats['sheets_processed']} sheet.\n";
            $message .= "Tạo mới: {$stats['orders_created']} đơn hàng, {$stats['installation_orders_created']} lắp đặt, {$stats['warranty_requests_created']} bảo hành, {$stats['collaborators_created']} CTV, {$stats['agencies_created']} đại lý.\n";
            $message .= "Cập nhật: {$stats['orders_updated']} đơn hàng, {$stats['installation_orders_updated']} lắp đặt, {$stats['warranty_requests_updated']} bảo hành.";
            
            if (!empty($stats['errors'])) {
                $message .= "\nLỗi: " . count($stats['errors']) . " lỗi xảy ra.";
            }

            // Invalidate cache version sau khi import đồng bộ
            try { Cache::increment('collab_install_cache_version'); } catch (\Throwable $e) { /* ignore */ }

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý file: ' . $e->getMessage(),
            ], 500);
        }
    }
}