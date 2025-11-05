<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // Title and date
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->setCellValue('A3', $title);
        $sheet->setCellValue('A4', "Từ ngày: $fromDate - đến ngày: $toDate");
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        // Column headers
        $sheet->fromArray([$columns], NULL, 'A5');
        $sheet->getStyle("A5:{$lastCol}5")->getFont()->setBold(true);
        $sheet->getStyle("A5:{$lastCol}5")->getAlignment()->setVertical('center');
        
        // Auto-size columns
        for ($col = 1; $col <= count($columns); $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(14);
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
        // Total row
        $mergeStart = Coordinate::stringFromColumnIndex(1);
        $mergeEnd = Coordinate::stringFromColumnIndex($mergeColsBefore);
        $sheet->mergeCells("{$mergeStart}{$row}:{$mergeEnd}{$row}");
        $sheet->setCellValue("{$mergeStart}{$row}", 'TỔNG CỘNG');
        $sheet->setCellValue("{$amountCol}{$row}", $totalCost);
        $sheet->getStyle("{$mergeStart}{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$amountCol}{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;
        
        // Amount in words
        $amountInWords = self::numberToWords($totalCost);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", "Bằng chữ: $amountInWords");
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
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
    public static function addDateLocation($sheet, $row, $lastCol, $startCol = 'H')
    {
        $currentDate = Carbon::now('Asia/Ho_Chi_Minh');
        $sheet->mergeCells("{$startCol}{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("{$startCol}{$row}", "Nghệ An, Ngày {$currentDate->format('d')} tháng {$currentDate->format('m')} năm {$currentDate->format('Y')}");
        $sheet->getStyle("{$startCol}{$row}")->getAlignment()->setHorizontal('right');
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
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal('center');
        $row++;
        
        // Second row - signature labels
        foreach ($signaturePositions as $index => $pos) {
            $startCol = Coordinate::stringFromColumnIndex($pos[0]);
            $endCol = Coordinate::stringFromColumnIndex($pos[1]);
            $sheet->mergeCells("{$startCol}{$row}:{$endCol}{$row}");
            $sheet->setCellValue("{$startCol}{$row}", '(Ký, ghi rõ họ tên)');
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setItalic(true)->setSize(12);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal('center');
    }

    /**
     * Format date từ Carbon object
     *
     * @param mixed $date
     * @return string
     */
    public static function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '';
    }
}

