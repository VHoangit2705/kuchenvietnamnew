<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kho\InstallationOrder;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Enum;
use App\Helpers\ReportHelper;

class ExportReportController extends Controller
{
    public function ReportCollaboratorInstall(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = ReportHelper::applyDateFilter(
            InstallationOrder::where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        $dataAgency = ReportHelper::applyDateFilter(
            InstallationOrder::where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        $spreadsheet = new Spreadsheet();


        // ================= SHEET 1: CHI TIẾT CỘNG TÁC VIÊN =================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('CTV CHI TIẾT');
        
        $columns = ['STT', 'CỘNG TÁC VIÊN', 'SỐ ĐIỆN THOẠI', 'SẢN PHẨM', 'CHI PHÍ', 'NGAY DONE', 'STK CTV', 'NGÂN HÀNG', 'MĐH'];
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        
        ReportHelper::setupSheetHeader(
            $sheet1,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            $columns
        );

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
                ReportHelper::cleanString($item->collaborator->phone ?? ''),
                ReportHelper::cleanString($item->product ?? ''),
                $cost,
                ReportHelper::formatDate($item->successed_at),
                ReportHelper::cleanString($item->collaborator->sotaikhoan ?? ''),
                $bankInfo,
                ReportHelper::cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet1->getStyle("E6:E" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        $row = ReportHelper::addTotalRow($sheet1, $row, $lastCol, $totalCost1, 4, 'E');
        $row = ReportHelper::addDateLocation($sheet1, $row, $lastCol, 'H');
        ReportHelper::addSignatureSection($sheet1, $row, $lastCol, [[1, 2], [4, 5], [7, 8], [9, 9]]);

        // ================= SHEET 2: TỔNG HỢP CỘNG TÁC VIÊN =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('CTV TỔNG HỢP');
        
        $columns2 = ['STT', 'HỌ VÀ TÊN', 'SỐ ĐIỆN THOẠI', 'ĐỊA CHỈ', 'SỐ TÀI KHOẢN CTV', 'NGÂN HÀNG', 'SỐ CA', 'SỐ TIỀN'];
        $lastCol2 = Coordinate::stringFromColumnIndex(count($columns2));
        
        ReportHelper::setupSheetHeader(
            $sheet2,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            $columns2
        );

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator->phone ?? 'N/A');

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
                ReportHelper::cleanString($phone),
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
        
        $row = ReportHelper::addTotalRow($sheet2, $row, $lastCol2, $totalCost2, 7, 'H');
        $row = ReportHelper::addDateLocation($sheet2, $row, $lastCol2, 'G');
        ReportHelper::addSignatureSection($sheet2, $row, $lastCol2, [[1, 2], [3, 4], [5, 6], [7, 8]]);

        // ================= SHEET 3: CHI TIẾT ĐẠI LÝ =================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('ĐẠI LÝ CHI TIẾT');
        
        $columns = ['STT', 'TÊN ĐẠI LÝ', 'SĐT', 'NGÀY DONE', 'THIẾT BỊ', 'CP HOÀN LẠI', 'STK ĐẠI LÝ', 'NGÂN HÀNG', 'MÃ ĐƠN HÀNG'];
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        
        ReportHelper::setupSheetHeader(
            $sheet3,
            'BẢNG CHI TIẾT TIỀN LẮP ĐẶT ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            $columns
        );

        $row = 6;
        $stt = 1;
        $totalCost = 0;
        foreach ($dataAgency as $item) {
            $cost = $item->install_cost ?? 0;
            $totalCost += $cost;
            $sheet3->fromArray([[
                $stt,
                ReportHelper::cleanString($item->agency->name ?? ''),
                ReportHelper::cleanString($item->agency_phone ?? ''),
                ReportHelper::formatDate($item->successed_at),
                ReportHelper::cleanString($item->product ?? ''),
                $cost,
                ReportHelper::cleanString($item->agency->sotaikhoan ?? ''),
                ReportHelper::cleanString($item->agency->chinhanh ?? ''),
                ReportHelper::cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet3->getStyle("F6:F" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        $row = ReportHelper::addTotalRow($sheet3, $row, $lastCol, $totalCost, 5, 'F');
        $row = ReportHelper::addDateLocation($sheet3, $row, $lastCol, 'H');
        ReportHelper::addSignatureSection($sheet3, $row, $lastCol, [[1, 2], [4, 5], [7, 8], [9, 9]]);

        // ================= SHEET 4: TỔNG HỢP ĐẠI LÝ =================
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('ĐẠI LÝ TỔNG HỢP');
        
        $columns4 = ['STT', 'HỌ VÀ TÊN', 'SĐT', 'SỐ TK CÁ NHÂN', 'NGÂN HÀNG', 'SỐ CA', 'SỐ TIỀN'];
        $lastCol4 = Coordinate::stringFromColumnIndex(count($columns4));
        
        ReportHelper::setupSheetHeader(
            $sheet4,
            'BẢNG TỔNG HỢP TRẢ TIỀN ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            $columns4
        );

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency->phone ?? 'N/A');

        $row = 6;
        $stt = 1;
        $totalCost4 = 0;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $total = $items->sum('install_cost');
            $totalCost4 += $total;
            $sheet4->fromArray([[
                $stt,
                ReportHelper::cleanString($agency->name ?? ''),
                ReportHelper::cleanString($phone),
                ReportHelper::cleanString($agency->sotaikhoan ?? ''),
                ReportHelper::cleanString($agency->chinhanh ?? ''),
                $items->count(),
                $total
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet4->getStyle("G6:G" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
        
        $row = ReportHelper::addTotalRow($sheet4, $row, $lastCol4, $totalCost4, 6, 'G');
        $row = ReportHelper::addDateLocation($sheet4, $row, $lastCol4, 'F');
        ReportHelper::addSignatureSection($sheet4, $row, $lastCol4, [[1, 2], [3, 4], [5, 6], [7, 7]]);

        // ================= EXPORT FILE =================
        $writer = new Xlsx($spreadsheet);
        session(['export_time' => now('Asia/Ho_Chi_Minh')]);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="report_ctv.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function ReportCollaboratorInstallPreview(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = ReportHelper::applyDateFilter(
            InstallationOrder::where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        $dataAgency = ReportHelper::applyDateFilter(
            InstallationOrder::where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
                ->where('status_install', 2),
            $tungay,
            $denngay
        )->get();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        // Prepare data for preview
        $sheet1Data = [];
        $stt = 1;
        $sheet1Total = 0;
        foreach ($dataCollaborator as $item) {
            $bankInfo = ReportHelper::formatBankInfo($item->collaborator->bank_name ?? null, $item->collaborator->chinhanh ?? null);
            
            $cost = $item->install_cost ?? 0;
            $sheet1Total += $cost;
            $sheet1Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($item->collaborator->full_name ?? ''),
                'phone' => ReportHelper::cleanString($item->collaborator->phone ?? ''),
                'product' => ReportHelper::cleanString($item->product ?? ''),
                'cost' => $cost,
                'done_date' => ReportHelper::formatDate($item->successed_at),
                'account' => ReportHelper::cleanString($item->collaborator->sotaikhoan ?? ''),
                'bank' => $bankInfo,
                'order_code' => ReportHelper::cleanString($item->order_code ?? '')
            ];
        }
        $sheet1AmountInWords = ReportHelper::numberToWords($sheet1Total);

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator->phone ?? 'N/A');
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
            $sheet3Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($item->agency->name ?? ''),
                'phone' => ReportHelper::cleanString($item->agency_phone ?? ''),
                'done_date' => ReportHelper::formatDate($item->successed_at),
                'product' => ReportHelper::cleanString($item->product ?? ''),
                'cost' => $cost,
                'account' => ReportHelper::cleanString($item->agency->sotaikhoan ?? ''),
                'bank' => ReportHelper::cleanString($item->agency->chinhanh ?? ''),
                'order_code' => ReportHelper::cleanString($item->order_code ?? '')
            ];
        }
        $sheet3AmountInWords = ReportHelper::numberToWords($sheet3Total);

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency->phone ?? 'N/A');
        $sheet4Data = [];
        $stt = 1;
        $sheet4Total = 0;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $total = $items->sum('install_cost');
            $sheet4Total += $total;
            $sheet4Data[] = [
                'stt' => $stt++,
                'name' => ReportHelper::cleanString($agency->name ?? ''),
                'phone' => ReportHelper::cleanString($phone),
                'account' => ReportHelper::cleanString($agency->sotaikhoan ?? ''),
                'bank' => ReportHelper::cleanString($agency->chinhanh ?? ''),
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