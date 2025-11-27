<?php

namespace App\Services\SendReport;

use Illuminate\Support\Carbon;

class ReportHtmlTemplate
{
    /**
     * Get CSS styles for the report
     */
    public function getCss(): string
    {
        return '
        @page {
            size: A4 landscape;
            margin: 0.3in 0.2in;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 7pt;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
        }
        .sub-header {
            text-align: right;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 7pt;
        }
        table th {
            background-color: #D3D3D3;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            border: 1px solid #000;
            font-size: 8pt;
        }
        table td {
            padding: 3px;
            border: 1px solid #000;
            text-align: left;
        }
        table td.center {
            text-align: center;
        }
        table td.right {
            text-align: right;
        }
        .summary-table {
            margin-top: 15px;
        }
        .work-process-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 20px 0 10px 0;
        }
        .work-process-date {
            text-align: right;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 10px;
        }
        .branch-summary {
            margin-top: 20px;
        }
        .branch-summary-header {
            background-color: #D3D3D3;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            border: 1px solid #000;
            font-size: 9pt;
        }
        .branch-summary-cell {
            text-align: center;
            padding: 6px;
            border: 1px solid #000;
            font-size: 8pt;
        }
        .branch-summary-cell.bold {
            font-weight: bold;
        }
        ';
    }

    /**
     * Build HTML report
     */
    public function buildHtml($data, $workProcessData, $fromDate, $toDate, $branchName): string
    {
        $fromDateFormatted = $fromDate;
        $toDateFormatted = $toDate;

        // Tính toán dữ liệu
        $stt = 1;
        $countBH = 0;
        $countLKBH = $priceLKBH = $PhiBH = 0;
        $countLKKBH = $priceLKKBH = $PhiHBH = 0;

        foreach ($data as $item) {
            $BH = 'Hết hạn BH';
            if ($item->warranty_end >= $item->received_date) {
                $BH = 'Còn hạn BH';
                $countBH++;
                if ($item->replacement) {
                    $countLKBH++;
                    $priceLKBH += $item->replacement_price * $item->quantity;
                    $PhiBH += $item->total;
                }
            } else {
                if ($item->replacement) {
                    $countLKKBH++;
                    $priceLKKBH += $item->replacement_price * $item->quantity;
                    $PhiHBH += $item->total;
                }
            }
        }

        // Tính tổng số ca của từng chi nhánh cho work process
        $branchTotals = null;
        if ($workProcessData && $workProcessData->isNotEmpty()) {
            $branchTotals = $workProcessData->groupBy('branch')->map(function ($branchStats) {
                return $branchStats->sum('tong_tiep_nhan');
            });

            // Thêm các phần trăm vào mỗi record
            $workProcessData = $workProcessData->map(function ($item) use ($branchTotals) {
                $tongTiepNhan = $item->tong_tiep_nhan ?? 0;
                $branchTotal = $branchTotals->get($item->branch, 0);

                $item->phan_tram_chi_nhanh = $branchTotal > 0
                    ? ($tongTiepNhan / $branchTotal) * 100
                    : 0;

                $item->dang_sua_chua_percent = $tongTiepNhan > 0
                    ? (($item->dang_sua_chua ?? 0) / $tongTiepNhan) * 100
                    : 0;

                $item->cho_khach_hang_phan_hoi_percent = $tongTiepNhan > 0
                    ? (($item->cho_khach_hang_phan_hoi ?? 0) / $tongTiepNhan) * 100
                    : 0;

                $item->da_hoan_tat_percent = $tongTiepNhan > 0
                    ? (($item->da_hoan_tat ?? 0) / $tongTiepNhan) * 100
                    : 0;

                $item->qua_han_percent = $tongTiepNhan > 0
                    ? (($item->qua_han ?? 0) / $tongTiepNhan) * 100
                    : 0;

                return $item;
            });
        } else {
            $branchTotals = $data->groupBy('branch')->map(function ($branchStats) {
                return $branchStats->count();
            });
        }

        // Build HTML
        $css = $this->getCss();
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>' . $css . '</style>
</head>
<body>
    <div class="header">BÁO CÁO THỐNG KÊ TRƯỜNG HỢP BẢO HÀNH</div>
    <div class="sub-header">Chi nhánh: ' . htmlspecialchars($branchName) . '     Từ ngày: ' . htmlspecialchars($fromDateFormatted) . ' đến ngày: ' . htmlspecialchars($toDateFormatted) . '</div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">STT</th>
                <th style="width: 7%;">Mã serial</th>
                <th style="width: 7%;">Mã serial thân máy</th>
                <th style="width: 10%;">Tên sản phẩm</th>
                <th style="width: 7%;">Chi nhánh</th>
                <th style="width: 8%;">Khách hàng</th>
                <th style="width: 7%;">Số điện thoại</th>
                <th style="width: 8%;">Kỹ thuật viên</th>
                <th style="width: 6%;">Ngày tiếp nhận</th>
                <th style="width: 10%;">Lỗi ban đầu</th>
                <th style="width: 6%;">Ngày xuất kho</th>
                <th style="width: 6%;">Tình trạng BH</th>
                <th style="width: 8%;">Linh kiện</th>
                <th style="width: 5%;">Đơn giá</th>
                <th style="width: 4%;">Số lượng</th>
                <th style="width: 6%;">Thành tiền</th>
                <th style="width: 6%;">Khách hàng chi trả</th>
            </tr>
        </thead>
        <tbody>';

        $stt = 1;
        foreach ($data as $item) {
            $BH = 'Hết hạn BH';
            if ($item->warranty_end >= $item->received_date) {
                $BH = 'Còn hạn BH';
            }

            $html .= '<tr>
                <td class="center">' . $stt++ . '</td>
                <td>' . htmlspecialchars($item->serial_number ?? '') . '</td>
                <td>' . htmlspecialchars($item->serial_thanmay ?? '') . '</td>
                <td>' . htmlspecialchars($item->product ?? '') . '</td>
                <td>' . htmlspecialchars($item->branch ?? '') . '</td>
                <td>' . htmlspecialchars($item->full_name ?? '') . '</td>
                <td>' . htmlspecialchars($item->phone_number ?? '') . '</td>
                <td>' . htmlspecialchars($item->staff_received ?? '') . '</td>
                <td class="center">' . Carbon::parse($item->received_date)->format('d/m/Y') . '</td>
                <td>' . htmlspecialchars($item->initial_fault_condition ?? '') . '</td>
                <td class="center">' . Carbon::parse($item->shipment_date)->format('d/m/Y') . '</td>
                <td class="center">' . htmlspecialchars($BH) . '</td>
                <td>' . htmlspecialchars($item->replacement ?? '') . '</td>
                <td class="right">' . number_format((float)($item->replacement_price ?? 0), 0, ',', '.') . '</td>
                <td class="center">' . ($item->quantity ?? 0) . '</td>
                <td class="right">' . number_format((float)(($item->quantity ?? 0) * ($item->replacement_price ?? 0)), 0, ',', '.') . '</td>
                <td class="right">' . number_format((float)($item->total ?? 0), 0, ',', '.') . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>

    <table class="summary-table">
        <thead>
            <tr>
                <th>Phân loại</th>
                <th>Số lượng ca bảo hành</th>
                <th>Số lượng linh kiện đã thay</th>
                <th>Chi phí linh kiện</th>
                <th>Chi phí bảo hành</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Còn bảo hành</td>
                <td class="center">' . $countBH . '</td>
                <td class="center">' . $countLKBH . '</td>
                <td class="right">' . number_format((float)$priceLKBH, 0, ',', '.') . '</td>
                <td class="right">' . number_format((float)$PhiBH, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td>Hết bảo hành</td>
                <td class="center">' . ($data->count() - $countBH) . '</td>
                <td class="center">' . $countLKKBH . '</td>
                <td class="right">' . number_format((float)$priceLKKBH, 0, ',', '.') . '</td>
                <td class="right">' . number_format((float)$PhiHBH, 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td><strong>Tổng</strong></td>
                <td class="center"><strong>' . $data->count() . '</strong></td>
                <td class="center"><strong>' . ($countLKBH + $countLKKBH) . '</strong></td>
                <td class="right"><strong>' . number_format((float)($priceLKBH + $priceLKKBH), 0, ',', '.') . '</strong></td>
                <td class="right"><strong>' . number_format((float)($PhiBH + $PhiHBH), 0, ',', '.') . '</strong></td>
            </tr>
        </tbody>
    </table>';

        // Work process report
        if ($workProcessData && $workProcessData->isNotEmpty()) {
            $html .= '<div class="work-process-title">BÁO CÁO TỔNG HỢP QUÁ TRÌNH LÀM VIỆC CỦA NHÂN VIÊN</div>
    <div class="work-process-date">Từ ngày: ' . htmlspecialchars($fromDate) . ' đến ngày: ' . htmlspecialchars($toDate) . '</div>
    
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Chi nhánh</th>
                <th>Tên kỹ thuật viên</th>
                <th>Tổng tiếp nhận</th>
                <th>% so với CN</th>
                <th>Đang sửa chữa</th>
                <th>Đang sửa chữa %</th>
                <th>Chờ KH phản hồi</th>
                <th>Chờ KH phản hồi %</th>
                <th>Đã hoàn tất</th>
                <th>Đã hoàn tất %</th>
                <th>Tỉ lệ trễ ca bảo hành (%)</th>
            </tr>
        </thead>
        <tbody>';

            $workStt = 1;
            foreach ($workProcessData as $item) {
                $html .= '<tr>
                    <td class="center">' . $workStt++ . '</td>
                    <td>' . htmlspecialchars($item->branch ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($item->staff_received ?? 'N/A') . '</td>
                    <td class="center">' . ($item->tong_tiep_nhan ?? 0) . '</td>
                    <td class="center">' . number_format($item->phan_tram_chi_nhanh ?? 0, 2) . '%</td>
                    <td class="center">' . ($item->dang_sua_chua ?? 0) . '</td>
                    <td class="center">' . number_format($item->dang_sua_chua_percent ?? 0, 2) . '%</td>
                    <td class="center">' . ($item->cho_khach_hang_phan_hoi ?? 0) . '</td>
                    <td class="center">' . number_format($item->cho_khach_hang_phan_hoi_percent ?? 0, 2) . '%</td>
                    <td class="center">' . ($item->da_hoan_tat ?? 0) . '</td>
                    <td class="center">' . number_format($item->da_hoan_tat_percent ?? 0, 2) . '%</td>
                    <td class="center">' . number_format($item->ti_le_qua_han ?? 0, 2) . '%</td>
                </tr>';
            }

            $html .= '</tbody>
    </table>';
        }

        // Branch summary table
        if ($branchTotals && $branchTotals->isNotEmpty()) {
            $branches = $branchTotals->keys()->sort()->values()->toArray();
            $numBranches = count($branches);

            $html .= '<div class="branch-summary">
    <table>
        <tr>
            <td colspan="' . $numBranches . '" class="branch-summary-header">TỔNG CA BẢO HÀNH TIẾP NHẬN</td>
        </tr>
        <tr>';

            foreach ($branches as $branchName) {
                $html .= '<td class="branch-summary-cell bold">' . htmlspecialchars($branchName) . '</td>';
            }

            $html .= '</tr>
        <tr>';

            foreach ($branches as $branchName) {
                $html .= '<td class="branch-summary-cell">' . $branchTotals->get($branchName, 0) . '</td>';
            }

            $html .= '</tr>
    </table>
</div>';
        }

        $html .= '</body>
</html>';

        return $html;
    }
}
