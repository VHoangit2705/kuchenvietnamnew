<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;

class ReportHelper
{
    /**
     * Làm sạch string, loại bỏ các ký tự điều khiển
     *
     * @param string|null $str
     * @return string
     */
    public static function cleanString($str)
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $str ?? '');
    }

    /**
     * Tạo khóa sắp xếp ngân hàng theo tên ngân hàng (gom các bản ghi cùng ngân hàng)
     * - Ưu tiên dùng bank_name nếu có
     * - Nếu không có, cố gắng lấy tên ngân hàng ở đầu chuỗi chi nhánh trước dấu '-' hoặc từ khóa 'CN/Chi nhánh'
     * - Chuẩn hóa lỗi chính tả/alias phổ biến
     *
     * @param string|null $bankName
     * @param string|null $chinhanh
     * @return string lowercase normalized sort key
     */
    public static function bankSortKey($bankName, $chinhanh)
    {
        $name = trim(self::cleanString($bankName ?? ''));
        $branch = trim(self::cleanString($chinhanh ?? ''));

        $base = $name;
        if ($base === '' && $branch !== '') {
            $candidate = $branch;
            // Cắt phần sau dấu '-'
            $parts = preg_split('/\s*-\s*/u', $candidate);
            if ($parts && $parts[0] !== '') {
                $candidate = $parts[0];
            }
            // Cắt phần sau 'CN' hoặc 'Chi nhánh'
            $parts = preg_split('/\s+(CN|Chi\s*nhánh)\b/iu', $candidate);
            if ($parts && $parts[0] !== '') {
                $candidate = $parts[0];
            }
            // Lấy token chữ cái ở đầu (vd: Vietcombank CN ...)
            if (preg_match('/^([A-Za-z]+)(?:\s|$)/u', $candidate, $m)) {
                $base = $m[1];
            } else {
                $base = $candidate;
            }
        }

        $base = preg_replace('/\s+/', ' ', trim($base));
        $lower = mb_strtolower($base, 'UTF-8');

        // Chuẩn hóa alias/lỗi chính tả phổ biến
        $alias = [
            'techcomnbank' => 'techcombank',
            'mb bank' => 'mbbank',
            'mb' => 'mbbank',
            'vietin bank' => 'vietinbank',
            'tp bank' => 'tpbank',
        ];
        if (isset($alias[$lower])) {
            $lower = $alias[$lower];
        }

        return $lower;
    }

    /**
     * Helper function để convert số sang từ tiếng Việt (recursive)
     *
     * @param int $num
     * @param array $ones
     * @param array $tens
     * @return string
     */
    private static function numberToWordsHelper($num, $ones, $tens)
    {
        if ($num == 0) return '';
        if ($num < 20) {
            $words = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín', 'mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
            return $words[$num];
        }
        if ($num < 100) {
            $tens_words = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
            $ten = floor($num / 10);
            $one = $num % 10;
            if ($one == 0) return $tens_words[$ten];
            if ($one == 1) return $tens_words[$ten] . ' mốt';
            if ($one == 5) return $tens_words[$ten] . ' lăm';
            return $tens_words[$ten] . ' ' . $ones[$one];
        }
        if ($num < 1000) {
            $hundred = floor($num / 100);
            $remainder = $num % 100;
            $result = $ones[$hundred] . ' trăm';
            if ($remainder > 0) {
                if ($remainder < 10) {
                    $result .= ' linh ' . $ones[$remainder];
                } else {
                    $result .= ' ' . self::numberToWordsHelper($remainder, $ones, $tens);
                }
            }
            return $result;
        }
        return '';
    }

    /**
     * Convert số tiền sang chữ tiếng Việt
     *
     * @param int|float $number
     * @return string
     */
    public static function numberToWords($number)
    {
        if ($number == 0) return 'không đồng';
        
        $ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín', 'mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
        $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        
        $num = (int)$number;
        $result = '';
        $billions = floor($num / 1000000000);
        $num %= 1000000000;
        $millions = floor($num / 1000000);
        $num %= 1000000;
        $thousands = floor($num / 1000);
        $num %= 1000;
        $hundreds = floor($num / 100);
        $num %= 100;
        
        if ($billions > 0) {
            $result .= self::numberToWordsHelper($billions, $ones, $tens) . ' tỷ ';
        }
        if ($millions > 0) {
            $result .= self::numberToWordsHelper($millions, $ones, $tens) . ' triệu ';
        }
        if ($thousands > 0) {
            $result .= self::numberToWordsHelper($thousands, $ones, $tens) . ' nghìn ';
        }
        if ($hundreds > 0) {
            $result .= self::numberToWordsHelper($hundreds, $ones, $tens) . ' trăm ';
        }
        if ($num > 0) {
            $result .= self::numberToWordsHelper($num, $ones, $tens);
        }
        
        return trim($result) . ' đồng';
    }

    /**
     * Tạo thông tin ngân hàng từ bank_name và chinhanh
     *
     * @param string|null $bankName
     * @param string|null $chinhanh
     * @return string
     */
    public static function formatBankInfo($bankName, $chinhanh)
    {
        $bankName = self::cleanString($bankName ?? '');
        $chinhanh = self::cleanString($chinhanh ?? '');
        $bankInfo = trim(($bankName ? $bankName . ' - ' : '') . $chinhanh);
        if (empty($bankInfo)) {
            $bankInfo = $chinhanh; // Fallback nếu không có bank_name
        }
        return $bankInfo;
    }

    /**
     * Apply date filter cho InstallationOrder query
     *
     * @param Builder $query
     * @param string|null $tungay
     * @param string|null $denngay
     * @return Builder
     */
    public static function applyDateFilter($query, $tungay, $denngay)
    {
        return $query->where(function($q) use ($tungay, $denngay) {
            if ($tungay && !empty($tungay) && $denngay && !empty($denngay)) {
                // Có cả từ ngày và đến ngày
                $q->where(function($sub) use ($tungay, $denngay) {
                    $sub->whereNotNull('successed_at')
                        ->whereDate('successed_at', '>=', $tungay)
                        ->whereDate('successed_at', '<=', $denngay);
                })->orWhere(function($sub) use ($tungay, $denngay) {
                    $sub->whereNull('successed_at')
                        ->whereDate('created_at', '>=', $tungay)
                        ->whereDate('created_at', '<=', $denngay);
                });
            } elseif ($tungay && !empty($tungay)) {
                // Chỉ có từ ngày
                $q->where(function($sub) use ($tungay) {
                    $sub->whereNotNull('successed_at')
                        ->whereDate('successed_at', '>=', $tungay);
                })->orWhere(function($sub) use ($tungay) {
                    $sub->whereNull('successed_at')
                        ->whereDate('created_at', '>=', $tungay);
                });
            } elseif ($denngay && !empty($denngay)) {
                // Chỉ có đến ngày
                $q->where(function($sub) use ($denngay) {
                    $sub->whereNotNull('successed_at')
                        ->whereDate('successed_at', '<=', $denngay);
                })->orWhere(function($sub) use ($denngay) {
                    $sub->whereNull('successed_at')
                        ->whereDate('created_at', '<=', $denngay);
                });
            }
        });
    }

    /**
     * Tạo header cho Excel sheet (company info, title, date)
     *
     * @param Worksheet $sheet
     * @param string $title
     * @param string $fromDate
     * @param string $toDate
     * @param array $columns
     * @return void
     */
    public static function setupSheetHeader($sheet, $title, $fromDate, $toDate, $columns)
    {
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        
        // Company information
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A1', 'Công ty TNHH Kuchen Việt Nam');
        $sheet->setCellValue('A2', 'Tòa nhà Kuchen Building đường Vinh - Cửa Lò, Xóm 13, Phường Nghi Phú, Thành phố Vinh, Nghệ An');
        $sheet->getStyle('A1')->getFont()->setName('Times New Roman')->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setName('Times New Roman')->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // Title and date
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->setCellValue('A3', $title);
        $sheet->setCellValue('A4', "Từ ngày: $fromDate - đến ngày: $toDate");
        $sheet->getStyle('A3')->getFont()->setName('Times New Roman')->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A4')->getFont()->setName('Times New Roman')->setBold(true)->setSize(13);
        
        // Column headers
        $sheet->fromArray([$columns], NULL, 'A5');
        $sheet->getStyle("A5:{$lastCol}5")->getFont()->setName('Times New Roman')->setBold(true)->setSize(14);
        $sheet->getStyle("A5:{$lastCol}5")->getAlignment()->setVertical('center');
        $sheet->getStyle("A5:{$lastCol}5")->getAlignment()->setHorizontal('center');
        
        // Auto-size columns
        for ($col = 1; $col <= count($columns); $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        // Default font for the entire workbook (body): size 13
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(13);
    }

    /**
     * Thêm total row và amount in words vào sheet
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastCol
     * @param int $totalCost
     * @param int $mergeColsBefore Total columns before amount column
     * @param string $amountCol Column letter for amount
     * @return int New row number
     */
    public static function addTotalRow($sheet, $row, $lastCol, $totalCost, $mergeColsBefore, $amountCol)
    {
        // ====== TỔNG CỘNG ======
        $mergeStart = Coordinate::stringFromColumnIndex(1);
        $mergeEnd   = Coordinate::stringFromColumnIndex($mergeColsBefore);

        $sheet->mergeCells("{$mergeStart}{$row}:{$mergeEnd}{$row}");
        $sheet->setCellValue("{$mergeStart}{$row}", 'TỔNG CỘNG');

        // Ghi số tiền
        $sheet->setCellValue("{$amountCol}{$row}", (int)$totalCost);
        $sheet->getStyle("{$amountCol}{$row}")
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $sheet->getStyle("{$mergeStart}{$row}:{$lastCol}{$row}")
            ->getFont()
            ->setName('Times New Roman')
            ->setBold(true);

        // Lưu lại vị trí cell tổng tiền
        $totalCell = "{$amountCol}{$row}";
        $row++;

        // ====== BẰNG CHỮ (ĐỌC TỪ CELL EXCEL) ======
        $excelTotalValue = (int) $sheet->getCell($totalCell)->getCalculatedValue();
        $amountInWords   = self::numberToWords($excelTotalValue);

        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", "Bằng chữ: {$amountInWords}");

        $sheet->getStyle("A{$row}")
            ->getFont()
            ->setName('Times New Roman')
            ->setBold(true)
            ->setItalic(true);

        $row++;

        return $row;
    }

    /**
     * Thêm date và location vào sheet
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastCol
     * @param string $startCol Column letter to start merge
     * @return int New row number
     */
    public static function addDateLocation($sheet, $row, $lastCol, $startCol = 'H', $endCol = null)
    {
        $currentDate = Carbon::now('Asia/Ho_Chi_Minh');
        // Gộp toàn bộ hàng giống addTotalRow: A{row}:{lastCol}{row}
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", "Nghệ An, Ngày {$currentDate->format('d')} tháng {$currentDate->format('m')} năm {$currentDate->format('Y')}");
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('right');
        $row += 5; // Cách 5 dòng trước phần ký
        return $row;
    }

    /**
     * Thêm signature section vào sheet
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastCol
     * @param array $signaturePositions Array of [startCol, endCol] for each signature
     * @return void
     */
    public static function addSignatureSection($sheet, $row, $lastCol, $signaturePositions)
    {
        $signatures = ['Giám đốc', 'Kế toán trưởng', 'Đối soát', 'Người lập biểu'];
        
        // First row - signature titles
        foreach ($signaturePositions as $index => $pos) {
            $startCol = Coordinate::stringFromColumnIndex($pos[0]);
            $endCol = Coordinate::stringFromColumnIndex($pos[1]);
            $sheet->mergeCells("{$startCol}{$row}:{$endCol}{$row}");
            $sheet->setCellValue("{$startCol}{$row}", $signatures[$index] ?? '');
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setName('Times New Roman')->setBold(true);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal('center');
        $row++;
        
        // Second row - signature labels
        foreach ($signaturePositions as $index => $pos) {
            $startCol = Coordinate::stringFromColumnIndex($pos[0]);
            $endCol = Coordinate::stringFromColumnIndex($pos[1]);
            $sheet->mergeCells("{$startCol}{$row}:{$endCol}{$row}");
            $sheet->setCellValue("{$startCol}{$row}", '(Ký, ghi rõ họ tên)');
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setName('Times New Roman')->setItalic(true)->setSize(12);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal('center');
    }

    /**
     * Áp dụng border mảnh cho toàn bộ bảng (header + dữ liệu + tổng)
     *
     * @param Worksheet $sheet
     * @param int $startRow
     * @param int $endRow
     * @param string $lastCol
     * @return void
     */
    public static function applyTableBorders($sheet, $startRow, $endRow, $lastCol)
    {
        if ($endRow < $startRow) {
            return;
        }
        $range = "A{$startRow}:{$lastCol}{$endRow}";
        $sheet->getStyle($range)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * Ép một cột trong khoảng hàng thành kiểu TEXT (không scientific notation)
     *
     * @param Worksheet $sheet
     * @param string $columnLetter
     * @param int $startRow
     * @param int $endRow
     * @return void
     */
    public static function forceTextColumn($sheet, $columnLetter, $startRow, $endRow)
    {
        for ($r = $startRow; $r <= $endRow; $r++) {
            $coord = $columnLetter . $r;
            $value = (string)$sheet->getCell($coord)->getValue();
            $sheet->setCellValueExplicit($coord, $value, DataType::TYPE_STRING);
        }
    }

    /**
     * Format date từ Carbon object
     *
     * @param mixed $date
     * @return string
     */
    public static function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('d/m/Y') : '';
    }

    /**
     * Chuẩn hóa số điện thoại thành chuỗi văn bản, luôn có '0' đầu và giữ nguyên trong Excel
     *
     * @param string|null $phone
     * @return string "'0xxxxxxxxx" để Excel coi như text
     */
    public static function normalizePhone($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
        if ($digits === '') {
            return '';
        }
        if ($digits[0] !== '0') {
            $digits = '0' . $digits;
        }
        return $digits; // Trả về chuỗi số; cột sẽ được set format TEXT trong Excel
    }

    /**
     * Trích xuất model ở cuối tên sản phẩm.
     * Ví dụ: "Bếp điện từ KU MI389" => "KU MI389"
     * - Lấy cụm token cuối cùng chỉ gồm A-Z/0-9/-/\/
     * - Cho phép nhiều token (ví dụ "KU MI389").
     * - Yêu cầu trong cụm phải có ít nhất một chữ số.
     *
     * @param string|null $productName
     * @return string
     */
    public static function extractModel($productName)
    {
        $name = trim(self::cleanString($productName ?? ''));
        if ($name === '') {
            return '';
        }

        $tokens = preg_split('/\s+/', $name) ?: [];
        $capture = [];
        $started = false;

        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $t = trim($tokens[$i], ",.;:()[]{}\-–—\u{2013}\u{2014}");
            if ($t === '') {
                if ($started) break;
                continue;
            }

            // Dùng token gốc (Unicode) nhưng chỉ chấp nhận ASCII A-Z/0-9/-/\/
            $upper = mb_strtoupper($t, 'UTF-8');
            if (preg_match('/^[A-Z0-9\-\/]+$/u', $upper)) {
                $capture[] = $t; // giữ nguyên hiển thị gốc (giữ khoảng trắng/hoa thường nếu có)
                $started = true;
                continue;
            }

            if ($started) {
                break;
            }
        }

        if (empty($capture)) {
            return '';
        }

        $capture = array_reverse($capture);
        $model = trim(implode(' ', $capture));

        // Đảm bảo có ít nhất 1 chữ số trong model
        if (!preg_match('/\d/', $model)) {
            return '';
        }

        return $model;
    }
}
