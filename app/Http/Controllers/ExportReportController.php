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

class ExportReportController extends Controller
{
    public function ReportCollaboratorInstall(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = InstallationOrder::where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)
            ->where(function($q) use ($tungay, $denngay) {
                if ($tungay && !empty($tungay) && $denngay && !empty($denngay)) {
                    // Có cả từ ngày và đến ngày
                    $q->where(function($sub) use ($tungay, $denngay) {
                        // Có successed_at: filter theo successed_at
                        $sub->whereNotNull('successed_at')
                            ->whereDate('successed_at', '>=', $tungay)
                            ->whereDate('successed_at', '<=', $denngay);
                    })->orWhere(function($sub) use ($tungay, $denngay) {
                        // Không có successed_at: filter theo created_at
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
            })
            ->get();

        $dataAgency = InstallationOrder::where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)
            ->where(function($q) use ($tungay, $denngay) {
                if ($tungay && !empty($tungay) && $denngay && !empty($denngay)) {
                    // Có cả từ ngày và đến ngày
                    $q->where(function($sub) use ($tungay, $denngay) {
                        // Có successed_at: filter theo successed_at
                        $sub->whereNotNull('successed_at')
                            ->whereDate('successed_at', '>=', $tungay)
                            ->whereDate('successed_at', '<=', $denngay);
                    })->orWhere(function($sub) use ($tungay, $denngay) {
                        // Không có successed_at: filter theo created_at
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
            })
            ->get();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        $spreadsheet = new Spreadsheet();

        $cleanString = function ($str) {
            return preg_replace('/[\x00-\x1F\x7F]/u', '', $str ?? '');
        };

        $makeHeader = function ($sheet, $title, $from, $to, $columns) {
            $lastCol = Coordinate::stringFromColumnIndex(count($columns));
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->mergeCells("A2:{$lastCol}2");

            $sheet->setCellValue('A1', $title);
            $sheet->setCellValue('A2', "Từ ngày: $from - đến ngày: $to");

            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('right');
            $sheet->getStyle('A2')->getFont()->setBold(true);

            $sheet->fromArray([$columns], NULL, 'A3');
            $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
            $sheet->getStyle("A3:{$lastCol}3")->getAlignment()->setVertical('center');

            for ($col = 1; $col <= count($columns); $col++) {
                $letter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(14);
        };

        // ================= SHEET 1: CHI TIẾT CỘNG TÁC VIÊN =================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('CTV CHI TIẾT');
        $makeHeader(
            $sheet1,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            ['STT', 'CỘNG TÁC VIÊN', 'SỐ ĐIỆN THOẠI', 'SẢN PHẨM', 'CHI PHÍ', 'NGAY DONE', 'STK CTV', 'NGÂN HÀNG', 'MĐH']
        );

        $row = 4;
        $stt = 1;
        foreach ($dataCollaborator as $item) {
            $bankName = $cleanString($item->collaborator->bank_name ?? '');
            $chinhanh = $cleanString($item->collaborator->chinhanh ?? '');
            $bankInfo = trim(($bankName ? $bankName . ' - ' : '') . $chinhanh);
            if (empty($bankInfo)) {
                $bankInfo = $chinhanh; // Fallback nếu không có bank_name
            }
            
            $sheet1->fromArray([[
                $stt,
                $cleanString($item->collaborator->full_name ?? ''),
                $cleanString($item->collaborator->phone ?? ''),
                $cleanString($item->product ?? ''),
                $item->install_cost ?? 0,
                $item->successed_at ?? '',
                $cleanString($item->collaborator->sotaikhoan ?? ''),
                $bankInfo,
                $cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet1->getStyle("E4:E" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

        // ================= SHEET 2: TỔNG HỢP CỘNG TÁC VIÊN =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('CTV TỔNG HỢP');
        $makeHeader(
            $sheet2,
            'BẢNG KÊ TIỀN THANH TOÁN CỘNG TÁC VIÊN LẮP ĐẶT BẢO HÀNH',
            $fromDateFormatted,
            $toDateFormatted,
            ['STT', 'HỌ VÀ TÊN', 'SỐ ĐIỆN THOẠI', 'ĐỊA CHỈ', 'SỐ TÀI KHOẢN CTV', 'NGÂN HÀNG', 'SỐ CA', 'SỐ TIỀN']
        );

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator->phone ?? 'N/A');

        $row = 4;
        $stt = 1;
        foreach ($dataCollaboratorgrouped as $phone => $items) {
            $collaborator = $items->first()->collaborator ?? null;
            $bankName = $cleanString($collaborator->bank_name ?? '');
            $chinhanh = $cleanString($collaborator->chinhanh ?? '');
            $bankInfo = trim(($bankName ? $bankName . ' - ' : '') . $chinhanh);
            if (empty($bankInfo)) {
                $bankInfo = $chinhanh; // Fallback nếu không có bank_name
            }
            
            $sheet2->fromArray([[
                $stt,
                $cleanString($collaborator->full_name ?? ''),
                $cleanString($phone),
                $cleanString($collaborator->address ?? ''),
                $cleanString($collaborator->sotaikhoan ?? ''),
                $bankInfo,
                $items->count(),
                $items->sum('install_cost')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet2->getStyle("H4:H" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

        // ================= SHEET 3: CHI TIẾT ĐẠI LÝ =================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('ĐẠI LÝ CHI TIẾT');
        $makeHeader(
            $sheet3,
            'BẢNG CHI TIẾT TIỀN LẮP ĐẶT ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            ['STT', 'TÊN ĐẠI LÝ', 'SỐ ĐIỆN THOẠI', 'NGÀY DONE', 'THIẾT BỊ', 'CP HOÀN LẠI', 'STK ĐẠI LÝ', 'NGÂN HÀNG', 'MÃ ĐƠN HÀNG']
        );

        $row = 4;
        $stt = 1;
        foreach ($dataAgency as $item) {
            $sheet3->fromArray([[
                $stt,
                $cleanString($item->agency->name ?? ''),
                $cleanString($item->agency_phone ?? ''),
                $cleanString(''),
                $cleanString($item->product ?? ''),
                $item->install_cost ?? 0,
                $cleanString($item->agency->sotaikhoan ?? ''),
                $cleanString($item->agency->chinhanh ?? ''),
                $cleanString($item->order_code ?? '')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet3->getStyle("F4:F" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

        // ================= SHEET 4: TỔNG HỢP ĐẠI LÝ =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('ĐẠI LÝ TỔNG HỢP');
        $makeHeader(
            $sheet2,
            'BẢNG TỔNG HỢP TRẢ TIỀN ĐẠI LÝ',
            $fromDateFormatted,
            $toDateFormatted,
            ['STT', 'HỌ VÀ TÊN', 'SĐT', 'SỐ TK CÁ NHÂN', 'NGÂN HÀNG', 'SỐ CA', 'SỐ TIỀN']
        );

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency->phone ?? 'N/A');

        $row = 4;
        $stt = 1;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $sheet2->fromArray([[
                $stt,
                $cleanString($agency->name ?? ''),
                $cleanString($phone),
                $cleanString($agency->sotaikhoan ?? ''),
                $cleanString($agency->chinhanh ?? ''),
                $items->count(),
                $items->sum('install_cost')
            ]], NULL, "A$row");
            $stt++;
            $row++;
        }
        $sheet2->getStyle("G4:G" . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

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

        $dataCollaborator = InstallationOrder::where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)
            ->where(function($q) use ($tungay, $denngay) {
                if ($tungay && !empty($tungay) && $denngay && !empty($denngay)) {
                    // Có cả từ ngày và đến ngày
                    $q->where(function($sub) use ($tungay, $denngay) {
                        // Có successed_at: filter theo successed_at
                        $sub->whereNotNull('successed_at')
                            ->whereDate('successed_at', '>=', $tungay)
                            ->whereDate('successed_at', '<=', $denngay);
                    })->orWhere(function($sub) use ($tungay, $denngay) {
                        // Không có successed_at: filter theo created_at
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
            })
            ->get();

        $dataAgency = InstallationOrder::where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)
            ->where(function($q) use ($tungay, $denngay) {
                if ($tungay && !empty($tungay) && $denngay && !empty($denngay)) {
                    // Có cả từ ngày và đến ngày
                    $q->where(function($sub) use ($tungay, $denngay) {
                        // Có successed_at: filter theo successed_at
                        $sub->whereNotNull('successed_at')
                            ->whereDate('successed_at', '>=', $tungay)
                            ->whereDate('successed_at', '<=', $denngay);
                    })->orWhere(function($sub) use ($tungay, $denngay) {
                        // Không có successed_at: filter theo created_at
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
            })
            ->get();

        $fromDateFormatted = Carbon::parse($tungay)->format('d-m-Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d-m-Y');

        $cleanString = function ($str) {
            return preg_replace('/[\x00-\x1F\x7F]/u', '', $str ?? '');
        };

        // Prepare data for preview
        $sheet1Data = [];
        $stt = 1;
        foreach ($dataCollaborator as $item) {
            $bankName = $cleanString($item->collaborator->bank_name ?? '');
            $chinhanh = $cleanString($item->collaborator->chinhanh ?? '');
            $bankInfo = trim(($bankName ? $bankName . ' - ' : '') . $chinhanh);
            if (empty($bankInfo)) {
                $bankInfo = $chinhanh; // Fallback nếu không có bank_name
            }
            
            $sheet1Data[] = [
                'stt' => $stt++,
                'name' => $cleanString($item->collaborator->full_name ?? ''),
                'phone' => $cleanString($item->collaborator->phone ?? ''),
                'product' => $cleanString($item->product ?? ''),
                'cost' => $item->install_cost ?? 0,
                'done_date' => $item->successed_at ?? '',
                'account' => $cleanString($item->collaborator->sotaikhoan ?? ''),
                'bank' => $bankInfo,
                'order_code' => $cleanString($item->order_code ?? '')
            ];
        }

        $dataCollaboratorgrouped = collect($dataCollaborator)->groupBy(fn($i) => $i->collaborator->phone ?? 'N/A');
        $sheet2Data = [];
        $stt = 1;
        foreach ($dataCollaboratorgrouped as $phone => $items) {
            $collaborator = $items->first()->collaborator ?? null;
            $bankName = $cleanString($collaborator->bank_name ?? '');
            $chinhanh = $cleanString($collaborator->chinhanh ?? '');
            $bankInfo = trim(($bankName ? $bankName . ' - ' : '') . $chinhanh);
            if (empty($bankInfo)) {
                $bankInfo = $chinhanh; // Fallback nếu không có bank_name
            }
            
            $sheet2Data[] = [
                'stt' => $stt++,
                'name' => $cleanString($collaborator->full_name ?? ''),
                'phone' => $cleanString($phone),
                'address' => $cleanString($collaborator->address ?? ''),
                'account' => $cleanString($collaborator->sotaikhoan ?? ''),
                'bank' => $bankInfo,
                'count' => $items->count(),
                'total' => $items->sum('install_cost')
            ];
        }

        $sheet3Data = [];
        $stt = 1;
        foreach ($dataAgency as $item) {
            $sheet3Data[] = [
                'stt' => $stt++,
                'name' => $cleanString($item->agency->name ?? ''),
                'phone' => $cleanString($item->agency_phone ?? ''),
                'done_date' => '',
                'product' => $cleanString($item->product ?? ''),
                'cost' => $item->install_cost ?? 0,
                'account' => $cleanString($item->agency->sotaikhoan ?? ''),
                'bank' => $cleanString($item->agency->chinhanh ?? ''),
                'order_code' => $cleanString($item->order_code ?? '')
            ];
        }

        $dataAgencyGrouped = collect($dataAgency)->groupBy(fn($i) => $i->agency->phone ?? 'N/A');
        $sheet4Data = [];
        $stt = 1;
        foreach ($dataAgencyGrouped as $phone => $items) {
            $agency = $items->first()->agency ?? null;
            $sheet4Data[] = [
                'stt' => $stt++,
                'name' => $cleanString($agency->name ?? ''),
                'phone' => $cleanString($phone),
                'account' => $cleanString($agency->sotaikhoan ?? ''),
                'bank' => $cleanString($agency->chinhanh ?? ''),
                'count' => $items->count(),
                'total' => $items->sum('install_cost')
            ];
        }

        return view('collaboratorinstall.preview', compact(
            'sheet1Data',
            'sheet2Data',
            'sheet3Data',
            'sheet4Data',
            'fromDateFormatted',
            'toDateFormatted'
        ));
    }
}