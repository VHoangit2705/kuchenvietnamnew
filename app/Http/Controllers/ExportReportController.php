<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kho\InstallationOrder;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Helpers\ReportHelper;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExportReportController extends Controller
{
    public function ReportCollaboratorInstall(Request $request)
    {
        // Server-side throttle: block exports if last export < 120 seconds ago
        $lastExportAt = session('export_time');
        if ($lastExportAt) {
            $diffSeconds = Carbon::parse($lastExportAt)->diffInSeconds(now('Asia/Ho_Chi_Minh'));
            if ($diffSeconds < 10) {
                return response()->json([
                    'message' => 'Bạn vừa tải báo cáo. Vui lòng thử lại sau 10 giây.',
                ], 429);
            }
        }
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = ReportHelper::applyDateFilter(
            InstallationOrder::query()
                ->whereNotNull('collaborator_id')
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        // Sắp xếp theo tên ngân hàng (gom liên tục), sau đó theo tên CTV
        $dataCollaborator = $dataCollaborator->sortBy(function ($i) {
            $c = $i->collaborator;
            $bankKey = ReportHelper::bankSortKey($c?->bank_name ?? null, $c?->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($c?->full_name ?? '');
            return $bankKey . '|' . $nameKey;
        })->values();

        $dataAgency = ReportHelper::applyDateFilter(
            InstallationOrder::query()
                ->whereNull('collaborator_id')
                ->where(function ($q) {
                    $q->whereNotNull('agency_id')
                        ->orWhereNotNull('agency_at')
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('agency_phone')->where('agency_phone', '!=', '');
                        })
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('agency_name')->where('agency_name', '!=', '');
                        });
                })
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        // Sắp xếp theo tên ngân hàng (gom liên tục), sau đó theo tên đại lý
        $dataAgency = $dataAgency->sortBy(function ($i) {
            $a = $i->agency;
            $bankKey = ReportHelper::bankSortKey($a?->bank_name_agency ?? null, $a?->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($a?->name ?? '');
            return $bankKey . '|' . $nameKey;
        })->values();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        $spreadsheet = new Spreadsheet();


        // ================= SHEET 1: CHI TIẾT CỘNG TÁC VIÊN =================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('CTV CHI TIẾT');
        
        $columns = ['STT', 'CỘNG TÁC VIÊN', 'SỐ ĐIỆN THOẠI', 'SẢN PHẨM', 'CHI PHÍ', 'NGÀY HOÀN THÀNH', 'STK CTV', 'NGÂN HÀNG - CHI NHÁNH', 'MĐH'];
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        
        ReportHelper::setupSheetHeader(
            $sheet1,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            $columns
        );

        // Format columns as text to preserve leading zeros and prevent auto-format
        $sheet1->getStyle('C:C')->getNumberFormat()->setFormatCode('@'); // Phone
        $sheet1->getStyle('G:G')->getNumberFormat()->setFormatCode('@'); // Account

        // Set column widths and disable auto width
        $sheet1->getColumnDimension('A')->setWidth(5.00)->setAutoSize(false); // 34 pixels
        $sheet1->getColumnDimension('B')->setWidth(17.00)->setAutoSize(false); // 166 pixels
        $sheet1->getColumnDimension('C')->setWidth(11.00)->setAutoSize(false); // 106 pixels
        $sheet1->getColumnDimension('D')->setWidth(12.00)->setAutoSize(false); // Sản phẩm
        $sheet1->getColumnDimension('E')->setWidth(12.00)->setAutoSize(false); // Chi phí
        $sheet1->getColumnDimension('F')->setWidth(10.00)->setAutoSize(false); // Ngày hoàn thành
        $sheet1->getColumnDimension('G')->setWidth(15.00)->setAutoSize(false); // STK CTV
        $sheet1->getColumnDimension('H')->setWidth(27.00)->setAutoSize(false); // 367 pixels - Ngân hàng chi nhánh
        $sheet1->getColumnDimension('I')->setWidth(16.00)->setAutoSize(false); // MĐH

        // Enable text wrapping for header row (row 5)
        $sheet1->getStyle("A5:{$lastCol}5")->getAlignment()->setWrapText(true);

        $row = 6;
        $stt = 1;
        $totalCost1 = 0;
        foreach ($dataCollaborator as $item) {
            $bankInfo = ReportHelper::formatBankInfo($item->collaborator->bank_name ?? null, $item->collaborator->chinhanh ?? null);
            
            $cost = $item->install_cost ?? 0;
            $totalCost1 += $cost;
            $sheet1->fromArray([[
                $stt,
                ReportHelper::cleanString($item->collaborator->full_name ?? ''),
                ReportHelper::normalizePhone($item->collaborator->phone ?? ''),
                ReportHelper::extractModel($item->product ?? ''),
                $cost,
                ReportHelper::formatDate($item->successed_at),
                ReportHelper::cleanString($item->collaborator->sotaikhoan ?? ''),
                $bankInfo,
                ReportHelper::cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            // Force TEXT for account and uppercase order code
            $sheet1->setCellValueExplicit('G' . $row, ReportHelper::cleanString($item->collaborator->sotaikhoan ?? ''), DataType::TYPE_STRING);
            $sheet1->setCellValue('I' . $row, strtoupper(ReportHelper::cleanString($item->order_code ?? '')));
            $stt++;
            $row++;
        }
        $sheet1->getStyle("E6:E" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        // Enable text wrapping for data rows (Excel will auto-adjust row height when opened)
        $sheet1->getStyle("A6:{$lastCol}" . ($row - 1))->getAlignment()->setWrapText(true);
        
        // Align left for column A (STT) from row 6
        $sheet1->getStyle("A6:A" . ($row - 1))->getAlignment()->setHorizontal('left');
        
        $row = ReportHelper::addTotalRow($sheet1, $row, $lastCol, $totalCost1, 4, 'E');
        // Ensure phone (C) and account (G) are TEXT explicitly
        ReportHelper::forceTextColumn($sheet1, 'C', 6, $row - 2);
        ReportHelper::forceTextColumn($sheet1, 'G', 6, $row - 2);
        // Apply borders for header (row 5) through total row ($row - 2)
        ReportHelper::applyTableBorders($sheet1, 5, $row - 2, $lastCol);
        $row = ReportHelper::addDateLocation($sheet1, $row, $lastCol, 'E', 'G');
        ReportHelper::addSignatureSection($sheet1, $row, $lastCol, [[1, 2], [4, 5], [7, 8], [9, 9]]);

        // ================= SHEET 2: TỔNG HỢP CỘNG TÁC VIÊN =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('CTV TỔNG HỢP');
        
        $columns2 = ['STT', 'HỌ VÀ TÊN', 'SỐ ĐIỆN THOẠI', 'ĐỊA CHỈ', 'SỐ TÀI KHOẢN CTV', 'NGÂN HÀNG - CHI NHÁNH', 'SỐ CA', 'SỐ TIỀN'];
        $lastCol2 = Coordinate::stringFromColumnIndex(count($columns2));
        
        ReportHelper::setupSheetHeader(
            $sheet2,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            $columns2
        );

        $sheet2->getStyle('C:C')->getNumberFormat()->setFormatCode('@'); // Phone
        $sheet2->getStyle('E:E')->getNumberFormat()->setFormatCode('@'); // Account

        // Set column widths and disable auto width for sheet 2 (8 columns: A-H)
        $sheet2->getColumnDimension('A')->setWidth(5.00)->setAutoSize(false); // STT
        $sheet2->getColumnDimension('B')->setWidth(17.00)->setAutoSize(false); // HỌ VÀ TÊN
        $sheet2->getColumnDimension('C')->setWidth(11.00)->setAutoSize(false); // SỐ ĐIỆN THOẠI
        $sheet2->getColumnDimension('D')->setWidth(25.00)->setAutoSize(false); // ĐỊA CHỈ
        $sheet2->getColumnDimension('E')->setWidth(18.00)->setAutoSize(false); // SỐ TÀI KHOẢN CTV
        $sheet2->getColumnDimension('F')->setWidth(27.00)->setAutoSize(false); // NGÂN HÀNG - CHI NHÁNH
        $sheet2->getColumnDimension('G')->setWidth(8.00)->setAutoSize(false); // SỐ CA
        $sheet2->getColumnDimension('H')->setWidth(15.00)->setAutoSize(false); // SỐ TIỀN

        // Enable text wrapping for header row (row 5)
        $sheet2->getStyle("A5:{$lastCol2}5")->getAlignment()->setWrapText(true);

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator->phone ?? 'N/A');
        // Sắp xếp nhóm theo tên ngân hàng
        $dataCollaboratorgrouped = $dataCollaboratorgrouped->sortBy(function ($items) {
            $c = $items->first()->collaborator ?? null;
            $bankKey = ReportHelper::bankSortKey($c->bank_name ?? null, $c->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($c->full_name ?? '');
            return $bankKey . '|' . $nameKey;
        });

        $row = 6;
        $stt = 1;
        $totalCost2 = 0;
        foreach ($dataCollaboratorgrouped as $phone => $items) {
            $collaborator = $items->first()->collaborator ?? null;
            $bankInfo = ReportHelper::formatBankInfo($collaborator->bank_name ?? null, $collaborator->chinhanh ?? null);
            
            $total = $items->sum('install_cost');
            $totalCost2 += $total;
            $sheet2->fromArray([[
                $stt,
                ReportHelper::cleanString($collaborator->full_name ?? ''),
                ReportHelper::normalizePhone($phone),
                ReportHelper::cleanString($collaborator->address ?? ''),
                ReportHelper::cleanString($collaborator->sotaikhoan ?? ''),
                $bankInfo,
                $items->count(),
                $total
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet2->getStyle("H6:H" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        // Enable text wrapping for data rows (Excel will auto-adjust row height when opened)
        $sheet2->getStyle("A6:{$lastCol2}" . ($row - 1))->getAlignment()->setWrapText(true);
        
        // Align left for column A (STT) from row 6
        $sheet2->getStyle("A6:A" . ($row - 1))->getAlignment()->setHorizontal('left');
        
        $row = ReportHelper::addTotalRow($sheet2, $row, $lastCol2, $totalCost2, 7, 'H');
        ReportHelper::forceTextColumn($sheet2, 'C', 6, $row - 2);
        ReportHelper::forceTextColumn($sheet2, 'E', 6, $row - 2);
        ReportHelper::applyTableBorders($sheet2, 5, $row - 2, $lastCol2);
        $row = ReportHelper::addDateLocation($sheet2, $row, $lastCol2, 'E', 'G');
        ReportHelper::addSignatureSection($sheet2, $row, $lastCol2, [[1, 2], [3, 4], [5, 6], [7, 8]]);

        // ================= SHEET 3: CHI TIẾT ĐẠI LÝ =================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('ĐẠI LÝ CHI TIẾT');
        
        $columns = ['STT', 'TÊN ĐẠI LÝ', 'SĐT', 'NGÀY HOÀN THÀNH', 'THIẾT BỊ', 'CP HOÀN LẠI', 'STK ĐẠI LÝ', 'NGÂN HÀNG - CHI NHÁNH', 'MÃ ĐƠN HÀNG'];
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        
        ReportHelper::setupSheetHeader(
            $sheet3,
            'BẢNG CHI TIẾT TIỀN LẮP ĐẶT ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            $columns
        );

        $sheet3->getStyle('C:C')->getNumberFormat()->setFormatCode('@'); // Phone
        $sheet3->getStyle('G:G')->getNumberFormat()->setFormatCode('@'); // Account

        // Set column widths and disable auto width for sheet 3 (9 columns: A-I)
        $sheet3->getColumnDimension('A')->setWidth(5.00)->setAutoSize(false); // STT
        $sheet3->getColumnDimension('B')->setWidth(17.00)->setAutoSize(false); // TÊN ĐẠI LÝ
        $sheet3->getColumnDimension('C')->setWidth(11.00)->setAutoSize(false); // SĐT
        $sheet3->getColumnDimension('D')->setWidth(12.00)->setAutoSize(false); // NGÀY HOÀN THÀNH
        $sheet3->getColumnDimension('E')->setWidth(12.00)->setAutoSize(false); // THIẾT BỊ
        $sheet3->getColumnDimension('F')->setWidth(12.00)->setAutoSize(false); // CP HOÀN LẠI
        $sheet3->getColumnDimension('G')->setWidth(18.00)->setAutoSize(false); // STK ĐẠI LÝ
        $sheet3->getColumnDimension('H')->setWidth(27.00)->setAutoSize(false); // NGÂN HÀNG - CHI NHÁNH
        $sheet3->getColumnDimension('I')->setWidth(16.00)->setAutoSize(false); // MÃ ĐƠN HÀNG

        // Enable text wrapping for header row (row 5)
        $sheet3->getStyle("A5:{$lastCol}5")->getAlignment()->setWrapText(true);

        $row = 6;
        $stt = 1;
        $totalCost = 0;
        foreach ($dataAgency as $item) {
            $cost = $item->install_cost ?? 0;
            $totalCost += $cost;
            $bankInfoAgency = ReportHelper::formatBankInfo($item->agency->bank_name_agency ?? null, $item->agency->chinhanh ?? null);
            $sheet3->fromArray([[
                $stt,
                ReportHelper::cleanString($item->agency->name ?? ''),
                ReportHelper::normalizePhone($item->agency_phone ?? ''),
                ReportHelper::formatDate($item->successed_at),
                ReportHelper::extractModel($item->product ?? ''),
                $cost,
                ReportHelper::cleanString($item->agency->sotaikhoan ?? ''),
                $bankInfoAgency,
                ReportHelper::cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            // Force TEXT for account and uppercase order code
            $sheet3->setCellValueExplicit('G' . $row, ReportHelper::cleanString($item->agency->sotaikhoan ?? ''), DataType::TYPE_STRING);
            $sheet3->setCellValue('I' . $row, strtoupper(ReportHelper::cleanString($item->order_code ?? '')));
            $stt++;
            $row++;
        }
        $sheet3->getStyle("F6:F" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        // Enable text wrapping for data rows (Excel will auto-adjust row height when opened)
        $sheet3->getStyle("A6:{$lastCol}" . ($row - 1))->getAlignment()->setWrapText(true);
        
        // Align left for column A (STT) from row 6
        $sheet3->getStyle("A6:A" . ($row - 1))->getAlignment()->setHorizontal('left');
        
        $row = ReportHelper::addTotalRow($sheet3, $row, $lastCol, $totalCost, 5, 'F');
        ReportHelper::forceTextColumn($sheet3, 'C', 6, $row - 2);
        ReportHelper::forceTextColumn($sheet3, 'G', 6, $row - 2);
        ReportHelper::applyTableBorders($sheet3, 5, $row - 2, $lastCol);
        $row = ReportHelper::addDateLocation($sheet3, $row, $lastCol, 'E', 'G');
        ReportHelper::addSignatureSection($sheet3, $row, $lastCol, [[1, 2], [4, 5], [7, 8], [9, 9]]);

        // ================= SHEET 4: TỔNG HỢP ĐẠI LÝ =================
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('ĐẠI LÝ TỔNG HỢP');
        
        $columns4 = ['STT', 'HỌ VÀ TÊN', 'SĐT', 'SỐ TK CÁ NHÂN', 'NGÂN HÀNG - CHI NHÁNH', 'SỐ CA', 'SỐ TIỀN'];
        $lastCol4 = Coordinate::stringFromColumnIndex(count($columns4));
        
        ReportHelper::setupSheetHeader(
            $sheet4,
            'BẢNG TỔNG HỢP TRẢ TIỀN ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            $columns4
        );

        $sheet4->getStyle('C:C')->getNumberFormat()->setFormatCode('@'); // Phone
        $sheet4->getStyle('D:D')->getNumberFormat()->setFormatCode('@'); // Account

        // Set column widths and disable auto width for sheet 4 (7 columns: A-G)
        $sheet4->getColumnDimension('A')->setWidth(5.00)->setAutoSize(false); // STT
        $sheet4->getColumnDimension('B')->setWidth(17.00)->setAutoSize(false); // HỌ VÀ TÊN
        $sheet4->getColumnDimension('C')->setWidth(11.00)->setAutoSize(false); // SĐT
        $sheet4->getColumnDimension('D')->setWidth(18.00)->setAutoSize(false); // SỐ TK CÁ NHÂN
        $sheet4->getColumnDimension('E')->setWidth(27.00)->setAutoSize(false); // NGÂN HÀNG - CHI NHÁNH
        $sheet4->getColumnDimension('F')->setWidth(8.00)->setAutoSize(false); // SỐ CA
        $sheet4->getColumnDimension('G')->setWidth(15.00)->setAutoSize(false); // SỐ TIỀN

        // Enable text wrapping for header row (row 5)
        $sheet4->getStyle("A5:{$lastCol4}5")->getAlignment()->setWrapText(true);

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency->phone ?? 'N/A');
        // Sắp xếp nhóm theo tên ngân hàng
        $dataAgencyGrouped = $dataAgencyGrouped->sortBy(function ($items) {
            $a = $items->first()->agency ?? null;
            $bankKey = ReportHelper::bankSortKey($a->bank_name_agency ?? null, $a->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($a->name ?? '');
            return $bankKey . '|' . $nameKey;
        });

        $row = 6;
        $stt = 1;
        $totalCost4 = 0;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $total = $items->sum('install_cost');
            $totalCost4 += $total;
            $bankInfoAgency = ReportHelper::formatBankInfo($agency->bank_name_agency ?? null, $agency->chinhanh ?? null);
            $sheet4->fromArray([[
                $stt,
                ReportHelper::cleanString($agency->name ?? ''),
                ReportHelper::normalizePhone($phone),
                ReportHelper::cleanString($agency->sotaikhoan ?? ''),
                $bankInfoAgency,
                $items->count(),
                $total
            ]], NULL, "A$row");
            // Force TEXT for account
            $sheet4->setCellValueExplicit('D' . $row, ReportHelper::cleanString($agency->sotaikhoan ?? ''), DataType::TYPE_STRING);
            $stt++;
            $row++;
        }
        $sheet4->getStyle("G6:G" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        // Enable text wrapping for data rows (Excel will auto-adjust row height when opened)
        $sheet4->getStyle("A6:{$lastCol4}" . ($row - 1))->getAlignment()->setWrapText(true);
        
        // Align left for column A (STT) from row 6
        $sheet4->getStyle("A6:A" . ($row - 1))->getAlignment()->setHorizontal('left');
        
        $row = ReportHelper::addTotalRow($sheet4, $row, $lastCol4, $totalCost4, 6, 'G');
        ReportHelper::forceTextColumn($sheet4, 'C', 6, $row - 2);
        ReportHelper::forceTextColumn($sheet4, 'D', 6, $row - 2);
        ReportHelper::applyTableBorders($sheet4, 5, $row - 2, $lastCol4);
        $row = ReportHelper::addDateLocation($sheet4, $row, $lastCol4, 'E', 'G');
        // Center header titles for sheet 4
        $sheet4->getStyle("A5:{$lastCol4}5")->getAlignment()->setHorizontal('center');
        ReportHelper::addSignatureSection($sheet4, $row, $lastCol4, [[1, 2], [3, 4], [5, 6], [7, 7]]);

        // ================= EXPORT FILE =================
        $writer = new Xlsx($spreadsheet);
        session(['export_time' => now('Asia/Ho_Chi_Minh')]);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="THỐNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function ReportCollaboratorInstallPreview(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = ReportHelper::applyDateFilter(
            InstallationOrder::query()
                ->whereNotNull('collaborator_id')
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        // Preview: đảm bảo sắp xếp tương tự export
        $dataCollaborator = $dataCollaborator->sortBy(function ($i) {
            $c = $i->collaborator;
            $bankKey = ReportHelper::bankSortKey($c?->bank_name ?? null, $c?->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($c?->full_name ?? '');
            return $bankKey . '|' . $nameKey;
        })->values();

        $dataAgency = ReportHelper::applyDateFilter(
            InstallationOrder::query()
                ->whereNull('collaborator_id')
                ->where(function ($q) {
                    $q->whereNotNull('agency_id')
                        ->orWhereNotNull('agency_at')
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('agency_phone')->where('agency_phone', '!=', '');
                        })
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('agency_name')->where('agency_name', '!=', '');
                        });
                })
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        // Preview: sắp xếp theo ngân hàng
        $dataAgency = $dataAgency->sortBy(function ($i) {
            $a = $i->agency;
            $bankKey = ReportHelper::bankSortKey($a?->bank_name_agency ?? null, $a?->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($a?->name ?? ($i->agency_name ?? ''));
            return $bankKey . '|' . $nameKey;
        })->values();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        // Prepare data for preview
        $sheet1Data = [];
        $stt = 1;
        $sheet1Total = 0;
        foreach ($dataCollaborator as $item) {
            $c = $item->collaborator;
            $bankInfo = ReportHelper::formatBankInfo($c?->bank_name ?? null, $c?->chinhanh ?? null);
            
            $cost = $item->install_cost ?? 0;
            $sheet1Total += $cost;
            $sheet1Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($c?->full_name ?? ''),
                'phone' => ReportHelper::cleanString($c?->phone ?? ''),
                'product' => ReportHelper::cleanString($item->product ?? ''),
                'model' => ReportHelper::extractModel($item->product ?? ''),
                'cost' => $cost,
                'done_date' => ReportHelper::formatDate($item->successed_at),
                'account' => ReportHelper::cleanString($c?->sotaikhoan ?? ''),
                'bank' => $bankInfo,
                'order_code' => ReportHelper::cleanString($item->order_code ?? '')
            ];
        }
        $sheet1AmountInWords = ReportHelper::numberToWords($sheet1Total);

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator?->phone ?? 'N/A');
        $dataCollaboratorgrouped = $dataCollaboratorgrouped->sortBy(function ($items) {
            $c = $items->first()->collaborator ?? null;
            $bankKey = ReportHelper::bankSortKey($c->bank_name ?? null, $c->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($c->full_name ?? '');
            return $bankKey . '|' . $nameKey;
        });
        $sheet2Data = [];
        $stt = 1;
        $sheet2Total = 0;
        foreach ($dataCollaboratorgrouped as $phone => $items) {
            $collaborator = $items->first()->collaborator ?? null;
            $bankInfo = ReportHelper::formatBankInfo($collaborator->bank_name ?? null, $collaborator->chinhanh ?? null);
            
            $total = $items->sum('install_cost');
            $sheet2Total += $total;
            $sheet2Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($collaborator->full_name ?? ''),
                'phone' => ReportHelper::cleanString($phone),
                'address' => ReportHelper::cleanString($collaborator->address ?? ''),
                'account' => ReportHelper::cleanString($collaborator->sotaikhoan ?? ''),
                'bank' => $bankInfo,
                'count' => $items->count(),
                'total' => $total
            ];
        }
        $sheet2AmountInWords = ReportHelper::numberToWords($sheet2Total);

        $sheet3Data = [];
        $stt = 1;
        $sheet3Total = 0;
        foreach ($dataAgency as $item) {
            $cost = $item->install_cost ?? 0;
            $sheet3Total += $cost;
            $a = $item->agency;
            $bankInfoAgency = ReportHelper::formatBankInfo($a?->bank_name_agency ?? null, $a?->chinhanh ?? null);
            $sheet3Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($a?->name ?? ($item->agency_name ?? '')),
                'phone' => ReportHelper::cleanString($item->agency_phone ?? ''),
                'done_date' => ReportHelper::formatDate($item->successed_at),
                'product' => ReportHelper::cleanString($item->product ?? ''),
                'model' => ReportHelper::extractModel($item->product ?? ''),
                'cost' => $cost,
                'account' => ReportHelper::cleanString($a?->sotaikhoan ?? ($item->agency_payment ?? '')),
                'bank' => $bankInfoAgency,
                'order_code' => ReportHelper::cleanString($item->order_code ?? '')
            ];
        }
        $sheet3AmountInWords = ReportHelper::numberToWords($sheet3Total);

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency_phone ?? ($i->agency?->phone ?? 'N/A'));
        $dataAgencyGrouped = $dataAgencyGrouped->sortBy(function ($items) {
            $a = $items->first()->agency ?? null;
            $bankKey = ReportHelper::bankSortKey($a->bank_name_agency ?? null, $a->chinhanh ?? null);
            $nameKey = ReportHelper::cleanString($a->name ?? '');
            return $bankKey . '|' . $nameKey;
        });
        $sheet4Data = [];
        $stt = 1;
        $sheet4Total = 0;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $total = $items->sum('install_cost');
            $sheet4Total += $total;
            $bankInfoAgency = ReportHelper::formatBankInfo($agency->bank_name_agency ?? null, $agency->chinhanh ?? null);
            $sheet4Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($agency->name ?? ''),
                'phone' => ReportHelper::cleanString($phone),
                'account' => ReportHelper::cleanString($agency->sotaikhoan ?? ''),
                'bank' => $bankInfoAgency,
                'count' => $items->count(),
                'total' => $total
            ];
        }
        $sheet4AmountInWords = ReportHelper::numberToWords($sheet4Total);

        return view('collaboratorinstall.preview', compact(
            'sheet1Data',
            'sheet2Data',
            'sheet3Data',
            'sheet4Data',
            'fromDateFormatted',
            'toDateFormatted',
            'sheet1Total',
            'sheet1AmountInWords',
            'sheet2Total',
            'sheet2AmountInWords',
            'sheet3Total',
            'sheet3AmountInWords',
            'sheet4Total',
            'sheet4AmountInWords'
        ));
    }
}