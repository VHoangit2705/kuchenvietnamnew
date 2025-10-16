<?php

namespace App\Http\Controllers;

use App\Models\Kho\Order;
use App\Models\Kho\OrderProduct;
use App\Models\Kho\InstallationOrder;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\District;
use App\Models\KyThuat\Wards;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\WarrantyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Kho\Agency;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollaboratorInstallController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:Xem danh sách CTV')->only(['Index']);
    //     $this->middleware('permission:Cập nhật CTV')->only(['CreateCollaborator', 'DeleteCollaborator']);
    // }
    static $pageSize = 50;
    public function Index(Request $request)
    {
        $tungay = request('tungay') ?? now()->startOfMonth()->toDateString();
        
        $startDate = Carbon::create(2025, 9, 1)->startOfDay();
        $lstOrder = OrderProduct::with('order')
            ->where('install', 1)
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                  ->where('order_code2', 'not like', 'KU%')
                  ->where(function ($sub) {
                    $sub->whereNull('status_install')
                        ->orWhere('status_install', 0);
                  });
            })
            ->when($madon = request('madon'), function ($q) use ($madon) {
                $q->whereHas('order', function ($sub) use ($madon) {
                    $sub->where('order_code2', 'like', "%$madon%");
                });
            })
            ->when($sanpham = request('sanpham'), function ($q) use ($sanpham) {
                $q->where('product_name', 'like', "%$sanpham%");
            })
            ->when($tungay, function ($q) use ($tungay) {
                $q->whereHas('order', function ($sub) use ($tungay) {
                    $sub->whereDate('created_at', '>=', $tungay);
                });
            })
            ->when($denngay = request('denngay'), function ($q) use ($denngay) {
                $q->whereHas('order', function ($sub) use ($denngay) {
                    $sub->whereDate('created_at', '<=', $denngay);
                });
            })
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
                $q->whereHas('order', function ($sub) use ($trangthai) {
                    if ($trangthai === '0') {
                        $sub->whereNull('status_install')->orWhere('status_install', 0);
                    } elseif ($trangthai === '1') {
                        $sub->where('status_install', 1);
                    } elseif ($trangthai === '2') {
                        $sub->where('status_install', 2);
                    }
                });
            })
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                $q->whereHas('order', function ($sub) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $sub->where('collaborator_id', '!=', 1);
                    } elseif ($phanloai === 'agency') {
                        $sub->where('collaborator_id', 1);
                    }
                });
            });
        
        $lstOrderLe = OrderProduct::with('order')
            ->where('install', 1)
            ->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                ->where('order_code2', 'like', 'KU%')
                  ->where(function ($sub) {
                    $sub->whereNull('status_install')
                        ->orWhere('status_install', 0);
                  });
            })
            ->when($madon = request('madon'), function ($q) use ($madon) {
                $q->whereHas('order', function ($sub) use ($madon) {
                    $sub->where('order_code2', 'like', "%$madon%");
                });
            })
            ->when($sanpham = request('sanpham'), function ($q) use ($sanpham) {
                $q->where('product_name', 'like', "%$sanpham%");
            })
            ->when($tungay, function ($q) use ($tungay) {
                $q->whereHas('order', function ($sub) use ($tungay) {
                    $sub->whereDate('created_at', '>=', $tungay);
                });
            })
            ->when($denngay = request('denngay'), function ($q) use ($denngay) {
                $q->whereHas('order', function ($sub) use ($denngay) {
                    $sub->whereDate('created_at', '<=', $denngay);
                });
            })
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
                $q->whereHas('order', function ($sub) use ($trangthai) {
                    if ($trangthai === '0') {
                        $sub->whereNull('status_install')->orWhere('status_install', 0);
                    } elseif ($trangthai === '1') {
                        $sub->where('status_install', 1);
                    } elseif ($trangthai === '2') {
                        $sub->where('status_install', 2);
                    }
                });
            })
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                $q->whereHas('order', function ($sub) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $sub->where('collaborator_id', '!=', 1);
                    } elseif ($phanloai === 'agency') {
                        $sub->where('collaborator_id', 1);
                    }
                });
            });


        $lstWarranty = WarrantyRequest::where('type', 'agent_home')
            ->where('Ngaytao', '>=', $startDate)
            ->where(function ($q) {
                $q->whereNull('status_install')
                    ->orWhere('status_install', 0);
            })
            ->when($madon = request('madon'), fn($q) => $q->where('serial_number', 'like', "%$madon%"))
            ->when($sanpham = request('sanpham'), fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay, fn($q) => $q->whereDate('Ngaytao', '>=', $tungay))
            ->when($denngay = request('denngay'), fn($q) => $q->whereDate('Ngaytao', '<=', $denngay))
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
                if ($trangthai === '0') {
                    $q->where(function ($sub) {
                        $sub->whereNull('status_install')
                            ->orWhere('status_install', 0);
                    });
                } elseif ($trangthai === '1') {
                    $q->where('status_install', 1);
                } elseif ($trangthai === '2') {
                    $q->where('status_install', 2);
                }
            })
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', 1);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', 1);
                }
            });

        $lstDaDieuPhoi = InstallationOrder::where('status_install', 1)->where('created_at', '>=', $startDate)
            ->when($madon = request('madon'), fn($q) => $q->where('order_code', 'like', "%$madon%"))
            ->when($sanpham = request('sanpham'), fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay, fn($q) => $q->whereDate('created_at', '>=', $tungay))
            ->when($denngay = request('denngay'), fn($q) => $q->whereDate('created_at', '<=', $denngay))
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', 1);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', 1);
                }
            });
        $lstInstallOrder = InstallationOrder::where('status_install', '!=', 1)->where('created_at', '>=', $startDate)
            ->when($madon = request('madon'), fn($q) => $q->where('order_code', 'like', "%$madon%"))
            ->when($sanpham = request('sanpham'), fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay, fn($q) => $q->whereDate('created_at', '>=', $tungay))
            ->when($denngay = request('denngay'), fn($q) => $q->whereDate('created_at', '<=', $denngay))
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', 1);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', 1);
                }
            });
            
        $lstInstallOld = InstallationOrder::whereDate('created_at', '<=', '2025-01-01')
            ->when($madon = request('madon'), fn($q) => $q->where('order_code', 'like', "%$madon%"))
            ->when($ngaytao = request('ngaytao'), fn($q) => $q->whereDate('created_at', '=', $ngaytao))
            ->when($kho = request('kho'), fn($q) => $q->where('zone', 'like', "%$kho%"))
            ->when($agency_home = request('agency_home'), fn($q) => $q->where('type', 'agent_home'))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($product_name = request('product_name'), fn($q) => $q->where('product', 'like', "%$product_name%"))
            ->when($install_collaborator = request('install_collaborator'), fn($q) => $q->where('collaborator_id', $install_collaborator))
            ->when($install_cost = request('install_cost'), fn($q) => $q->where('install_cost', $install_cost))
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
                if ($trangthai === '1') {
                    $q->where(function ($sub) {
                        $sub->whereNull('status_install')
                            ->orWhere('status_install', 1);
                    });
                } elseif ($trangthai === '2') {
                    $q->where('status_install', 2);
                } elseif ($trangthai === '3') {
                    $q->where('status_install', 3);
                }
            });

        $counts = [
            'dieuphoidonhang' => (clone $lstOrder)->count(),
            'dieuphoidonhangle' => (clone $lstOrderLe)->count(),
            'dieuphoibaohanh' => (clone $lstWarranty)->count(),
            'dadieuphoi'      => (clone $lstDaDieuPhoi)->count(),
            'dahoanthanh'     => (clone $lstInstallOrder)->count(),
            'installold'      => (clone $lstInstallOld)->count(),
        ];

        $tab = $request->get('tab', 'dieuphoidonhang');
        $tabQuery = match ($tab) {
            'dieuphoidonhangle' => $lstOrderLe,
            'dieuphoibaohanh' => $lstWarranty,
            'dadieuphoi'      => $lstDaDieuPhoi,
            'dahoanthanh'     => $lstInstallOrder,
            'installold'      => $lstInstallOld,
            default           => $lstOrder,
        };

        $data = $tabQuery->orderByDesc('id')->paginate(50)->withQueryString();
        if ($request->ajax()) {
            return response()->json([
                'tab'   => view('collaboratorinstall.tableheader', [
                    'counts'    => $counts,
                    'activeTab' => $tab
                ])->render(),
                'table' => view('collaboratorinstall.tablecontent', compact('data'))->render(),
            ]);
        }
        return view('collaboratorinstall.index', compact('data', 'counts', 'tab'));
    }

    public function Details(Request $request)
    {
        if ($request->type == 'donhang') {
            $data = OrderProduct::with('order')->findOrFail($request->id);
            $provinceId = $data->order->province ?? null;
            $districtId = $data->order->district ?? null;
            $wardId     = $data->order->wards ?? null;
            $agency_phone = $data->order->agency_phone;
        } else if ($request->type == 'baohanh') {
            $data = WarrantyRequest::findOrFail($request->id);
            $provinceId = $data->province_id ?? null;
            $districtId = $data->district_id ?? null;
            $wardId     = $data->ward_id ?? null;
            $agency_phone = $data->agency_phone;
        } else {
            $data = InstallationOrder::findOrFail($request->id);
            $provinceId = $data->province_id ?? null;
            $districtId = $data->district_id ?? null;
            $wardId     = $data->ward_id ?? null;
            $agency_phone = $data->agency_phone;
        }
        $agency = Agency::where('phone', $agency_phone)->first();
        $provinces = Province::orderBy('name')->get();
        
        $provinceName = $provinceId ? Province::find($provinceId)?->name : null;
        $districtName = $districtId ? District::find($districtId)?->name : null;
        $wardName     = $wardId ? Wards::find($wardId)?->name : null;
        $fullAddress = implode(', ', array_filter([$wardName, $districtName, $provinceName]));
        
        $lstCollaborator = WarrantyCollaborator::query()
            ->when($provinceId, function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            })
            ->select('*')
            ->selectRaw('
                (CASE WHEN province_id = ? THEN 1 ELSE 0 END +
                 CASE WHEN district_id = ? THEN 1 ELSE 0 END +
                 CASE WHEN ward_id = ? THEN 1 ELSE 0 END) as match_score
            ', [$provinceId, $districtId, $wardId])
            ->orderByDesc('match_score')
            ->limit(10)
            ->get();

        return view('collaboratorinstall.details', compact('data', 'lstCollaborator', 'provinces', 'agency', 'fullAddress'));
    }

    public function Update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'installcost' => 'nullable|numeric|min:0',
            'ctv_id' => 'nullable',
            'successed_at' => 'nullable',
            'installreview' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $model = match ($request->type) {
                'donhang'  => Order::findOrFail($request->id),
                'baohanh'  => WarrantyRequest::findOrFail($request->id),
                default    => InstallationOrder::findOrFail($request->id),
            };

            $action = $request->input('action');
            if (in_array($action, ['complete', 'payment']) && !$request->successed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu nhập ngày hoàn thành!'
                ]);
            }
            switch ($action) {
                case 'update':
                    $model->status_install = 1;
                    break;

                case 'complete':
                    $model->status_install = 2;
                    break;

                case 'payment':
                    $model->status_install = 3;
                    break;
            }
            
            $model->collaborator_id = $request->ctv_id;
            $model->install_cost    = $request->installcost;
            $model->successed_at    = $request->successed_at;

            if ($request->hasFile('installreview')) {
                $file = $request->file('installreview');
                $extension = strtolower($file->getClientOriginalExtension());

                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.pdf';
                $savePath = storage_path('app/public/install_reviews/' . $filename);

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    // Convert ảnh -> PDF
                    $imgData = base64_encode(file_get_contents($file->getRealPath()));
                    $html = '<img src="data:image/' . $extension . ';base64,' . $imgData . '" style="width:100%;">';
                    $pdf = Pdf::loadHTML($html);
                    $pdf->save($savePath);
                } elseif ($extension === 'pdf') {
                    // Giữ nguyên PDF
                    $file->storeAs('install_reviews', $filename, 'public');
                }

                $model->reviews_install = $filename;
            }
            $model->save();

            InstallationOrder::updateOrCreate(
                [
                    'order_code' => $model->order_code2 ?? $model->serial_number ?? $model->order_code,
                ],
                [
                    'full_name'        => $model->customer_name ?? $model->full_name,
                    'phone_number'     => $model->customer_phone ?? $model->phone_number,
                    'address'          => $model->customer_address ?? $model->address,
                    'product'          => $request->product,
                    'province_id'      => $model->province ?? $model->province_id,
                    'district_id'      => $model->district ?? $model->district_id,
                    'ward_id'          => $model->wards ?? $model->ward_id,
                    'collaborator_id'  => $model->collaborator_id,
                    'install_cost'     => $model->install_cost,
                    'status_install'   => $model->status_install,
                    'reviews_install'  => $model->reviews_install,
                    'agency_name'      => $model->agency_name ?? '',
                    'agency_phone'     => $model->agency_phone ?? '',
                    'type'             => $request->type,
                    'zone'             => $model->zone,
                    'created_at'       => $model->created_at ?? $model->Ngaytao,
                    'successed_at'     => $model->successed_at
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Filter cộng tác viên
    public function Filter(Request $request)
    {
        $lstCollaborator = WarrantyCollaborator::when($request->province, function ($q) use ($request) {
            $q->where('province_id', $request->province);
        })
            ->when($request->district, function ($q) use ($request) {
                $q->where('district_id', $request->district);
            })
            ->when($request->ward, function ($q) use ($request) {
                $q->where('ward_id', $request->ward);
            })
            ->get();

        $html = view('collaboratorinstall.tablecollaborator', compact('lstCollaborator'))->render();
        return response()->json(['html' => $html]);
    }
    
    public function ReportCollaboratorInstall(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = InstallationOrder::where('collaborator_id', '!=', 1)
            ->where('status_install', 2)->get();

        $dataAgency = InstallationOrder::where('collaborator_id', 1)
            ->where('status_install', 2)->get();

        $fromDateFormatted = Carbon::parse($tungay)->format('d/m/Y');
        $toDateFormatted   = Carbon::parse($denngay)->format('d/m/Y');

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
            $sheet1->fromArray([[
                $stt,
                $cleanString($item->collaborator->full_name ?? ''),
                $cleanString($item->collaborator->phone ?? ''),
                $cleanString($item->product ?? ''),
                $item->install_cost ?? 0,
                $item->successed_at ?? '',
                $cleanString($item->collaborator->sotaikhoan ?? ''),
                $cleanString($item->collaborator->chinhanh ?? ''),
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
            $sheet2->fromArray([[
                $stt,
                $cleanString($collaborator->full_name ?? ''),
                $cleanString($phone),
                $cleanString($collaborator->address ?? ''),
                $cleanString($collaborator->sotaikhoan ?? ''),
                $cleanString($collaborator->chinhanh ?? ''),
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
    
    public function ImportExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excelFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('excelFile');

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheetCount = $spreadsheet->getSheetCount();

            $imported = 0;
            $processedSheets = 0;

            for ($s = 0; $s < $sheetCount; $s++) {
                $currentSheet = $spreadsheet->getSheet($s);
                $rows = $currentSheet->toArray(null, true, true, true);
                $processedSheets++;

                foreach ($rows as $index => $row) {
                    if ($index == 1) {
                        // Bỏ qua hàng tiêu đề
                        continue;
                    }

                    // MÃ ĐƠN HÀNG ở cột N
                    $orderCode = trim($row['O'] ?? '');
                    if ($orderCode === '') {
                        continue;
                    }

                  // Ngày ở cột B
                    $dateRaw = trim($row['B'] ?? '');
                    $createdAt = null;

                    if ($dateRaw !== '') {
                        if (is_numeric($dateRaw)) {
                            $createdAt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw)
                                ->format('Y-m-d H:i:s');
                        } else {
                            try {
                                // ép parse đúng định dạng d/m/Y
                                $createdAt = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d H:i:s');
                            } catch (\Exception $e) {
                                $createdAt = null;
                            }
                        }
                    } else {
                        $createdAt = null;
                    }

                    // Map cột theo file: C đại lý, D sđt đại lý, E sđt KH, F địa chỉ, G thiết bị, H tên CTV, I SĐT CTV, J trạng thái, K STK, L ngân hàng, M ghi chú
                    $agencyName       = trim($row['C'] ?? '');
                    $agencyPhoneRaw   = trim($row['D'] ?? '');
                    $customerPhoneRaw = trim($row['E'] ?? '');
                    $customerAddress  = trim($row['F'] ?? '');
                    $product          = trim($row['G'] ?? '');
                    $collabName       = trim($row['H'] ?? '');
                    $collabPhoneRaw   = trim($row['I'] ?? '');
                    $doneRaw          = trim($row['J'] ?? '');
                    $note             = trim($row['M'] ?? '');

                    // Chuẩn hóa số điện thoại: chỉ giữ chữ số, cắt tối đa 15 ký tự
                    $sanitizePhone = function ($value) {
                        $digits = preg_replace('/\D+/', '', $value ?? '');
                        return $digits !== null ? mb_substr($digits, 0, 15) : '';
                    };
                    $agencyPhone    = $sanitizePhone($agencyPhoneRaw);
                    $customerPhone  = $sanitizePhone($customerPhoneRaw);
                    $collabPhone    = $sanitizePhone($collabPhoneRaw);

                    $statusInstall = 1;
                    if ($doneRaw !== '') {
                        $doneLower = mb_strtolower($doneRaw);
                        if (in_array($doneLower, ['1', 'đã hoàn thành', 'x', 'done', 'yes'])) {
                            $statusInstall = 2;
                        }
                    }

                    $collaborator = null;
                    if ($collabPhone !== '') {
                        $collaborator = WarrantyCollaborator::where('phone', $collabPhone)->first();
                    }

                    // Không tự tạo agency mới để tránh lỗi bảng agency không tự tăng id

                    $payload = [
                        'order_code'      => $orderCode,
                        // Không có trường họ tên khách trong file cũ -> để trống
                        'full_name'       => '',
                        'phone_number'    => $customerPhone,
                        'address'         => $customerAddress,
                        'product'         => $product,
                        'province_id'     => null,
                        'district_id'     => null,
                        'ward_id'         => null,
                        'order_id'        => null,
                        'collaborator_id' => $collaborator->id ?? 1,
                        'install_cost'    => 0,
                        'status_install'  => $statusInstall,
                        'reviews_install' => $note,
                        'agency_name'     => $agencyName,
                        'agency_phone'    => $agencyPhone,
                        'agency_payment'  => null,
                        'type'            => 'donhang',
                        'zone'            => null,
                        'created_at'      => $createdAt,
                        'successed_at'    => $statusInstall == 2 ? $createdAt : null,
                    ];

                    $existing = InstallationOrder::where('order_code', $orderCode)->first();
                    if ($existing) {
                        $existing->update($payload);
                    } else {
                        // Bảng mysql3 có thể không auto-increment id -> tự sinh id tiếp theo
                        $nextId = (InstallationOrder::max('id') ?? 0) + 1;
                        $payloadWithId = array_merge(['id' => $nextId], $payload);
                        InstallationOrder::insert($payloadWithId);
                    }

                    $imported++;
                }
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'sheets_processed' => $processedSheets,
                'message' => "Đã import $imported dòng vào bảng installation_orders (từ $processedSheets sheet).",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý file: ' . $e->getMessage(),
            ], 500);
        }
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

        // Tăng thời gian thực thi
        ini_set('memory_limit', '2048M');      // hoặc 2048M
        ini_set('max_execution_time', '1200'); // 20 phút
        set_time_limit(1200);

        try {
            $file = $request->file('excelFile');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheetCount = $spreadsheet->getSheetCount();

            $stats = [
                'imported' => 0,
                'collaborators_created' => 0,
                'agencies_created' => 0,
                'products_created' => 0,
                'orders_created' => 0,
                'orders_updated' => 0,
                'installation_orders_created' => 0,
                'installation_orders_updated' => 0,
                'warranty_requests_created' => 0,
                'warranty_requests_updated' => 0,
                'sheets_processed' => 0,
                'errors' => []
            ];

            // Cache để tối ưu performance
            $collaboratorCache = [];
            $agencyCache = [];
            $productCache = [];

            // Hàm chuẩn hóa số điện thoại
            $sanitizePhone = function ($value) {
                // Giữ lại chữ số, cắt tối đa 11 số (phổ biến tại VN)
                $digits = preg_replace('/\D+/', '', $value ?? '');
                if ($digits === null) return '';
                // Nếu dài hơn 11, lấy 11 số cuối (trường hợp có mã vùng/chuỗi dính)
                if (mb_strlen($digits) > 11) {
                    return mb_substr($digits, -11);
                }
                return $digits;
            };

            // Hàm chuẩn hóa ngày tháng
            $parseDate = function ($dateRaw) {
                if (empty($dateRaw)) return null;
                
                if (is_numeric($dateRaw)) {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw)
                        ->format('Y-m-d H:i:s');
                } else {
                    try {
                        // Thử định dạng d/m/Y trước
                        return Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        try {
                            // Thử định dạng d/m/y
                            return Carbon::createFromFormat('d/m/y', $dateRaw)->format('Y-m-d H:i:s');
                        } catch (\Exception $e2) {
                            try {
                                // Thử định dạng khác
                                return Carbon::parse($dateRaw)->format('Y-m-d H:i:s');
                            } catch (\Exception $e3) {
                                Log::warning("Cannot parse date: $dateRaw");
                                return null;
                            }
                        }
                    }
                }
            };

            // Hàm chuẩn hóa trạng thái
            $parseStatus = function ($statusRaw) {
                if (empty($statusRaw)) return 0;
                
                $statusLower = mb_strtolower(trim($statusRaw));
                if (in_array($statusLower, ['đã hoàn thành', 'hoàn thành', 'done', 'x', '1', 'yes'])) {
                    return 2; // Hoàn thành
                } elseif (in_array($statusLower, ['đang xử lý', 'đang làm', 'đang theo dõi', 'đang thực hiện'])) {
                    return 1; // Đang xử lý
                } else {
                    return 0; // Chưa xử lý
                }
            };

            // Xử lý tất cả sheet để debug
            $startSheet = 0; // Bắt đầu từ sheet đầu tiên
            $endSheet = $sheetCount; // Đến sheet cuối cùng
            
            Log::info("Processing sheets from $startSheet to $endSheet (total: $sheetCount)");

            for ($s = $startSheet; $s < $endSheet; $s++) {
                try {
                    $currentSheet = $spreadsheet->getSheet($s);
                    $sheetName = $currentSheet->getTitle();
                    Log::info("Processing sheet: $sheetName (index: $s)");
                    
                    // Đặc biệt kiểm tra sheet T7
                    if ($sheetName === 'T7') {
                        Log::info("Found T7 sheet at index $s - Processing...");
                    }
                    
                    // Chỉ lấy dữ liệu cần thiết, không load toàn bộ sheet
                    $highestRow = $currentSheet->getHighestDataRow();
                    $highestColumn = $currentSheet->getHighestDataColumn();
                    
                    Log::info("Sheet $sheetName - HighestRow: $highestRow, HighestColumn: $highestColumn");
                    
                    // Bỏ qua sheet trống
                    if ($highestRow <= 1) {
                        Log::info("Skipping empty sheet: $sheetName");
                        continue;
                    }

                    $stats['sheets_processed']++;

                    // Ghi nhớ ngày của hàng gần nhất (để xử lý các ô ngày bị merge/để trống)
                    $lastParsedDate = null;

                    // Xử lý từng dòng
                    for ($row = 2; $row <= $highestRow; $row++) { // Bắt đầu từ dòng 2 (bỏ header)
                        try {
                            // Lấy dữ liệu từ các cột cụ thể
                            $orderCode = trim($currentSheet->getCell('Q' . $row)->getCalculatedValue() ?? '');
                            
                            // Debug log để kiểm tra dữ liệu
                            Log::info("Row $row - OrderCode: '$orderCode'");
                            
                            // Đặc biệt debug cho sheet T7
                            if ($sheetName === 'T7') {
                                Log::info("T7 Row $row - OrderCode: '$orderCode'");
                            }
                            
                            if (empty($orderCode)) {
                                Log::info("Skipping row $row - Empty orderCode");
                                if ($sheetName === 'T7') {
                                    Log::info("T7 Row $row - Skipping due to empty orderCode");
                                }
                                continue;
                            }

                            $dateRaw = trim($currentSheet->getCell('B' . $row)->getCalculatedValue() ?? '');
                            $parsedDate = $parseDate($dateRaw);
                            if ($parsedDate) {
                                $createdAt = $parsedDate;
                                $lastParsedDate = $parsedDate; // lưu lại để dùng cho các dòng cùng ngày
                            } else {
                                // Nếu ô ngày trống (do merge), dùng lại ngày trước đó
                                $createdAt = $lastParsedDate;
                            }
                            
                            // Debug log để kiểm tra định dạng ngày
                            Log::info("Row $row - DateRaw: '$dateRaw', Parsed: '" . ($createdAt ?? 'NULL') . "'");
                            if ($sheetName === 'T7') {
                                Log::info("T7 Row $row - DateRaw: '$dateRaw', Parsed: '" . ($createdAt ?? 'NULL') . "'");
                            }

                            $agencyName = trim($currentSheet->getCell('C' . $row)->getCalculatedValue() ?? '');
                            $agencyPhoneRaw = trim($currentSheet->getCell('D' . $row)->getCalculatedValue() ?? '');
                            $customerName = trim($currentSheet->getCell('F' . $row)->getCalculatedValue() ?? '');
                            $customerPhoneRaw = trim($currentSheet->getCell('G' . $row)->getCalculatedValue() ?? '');
                            $customerAddress = trim($currentSheet->getCell('H' . $row)->getCalculatedValue() ?? '');
                            $product = trim($currentSheet->getCell('I' . $row)->getCalculatedValue() ?? '');
                            $collabName = trim($currentSheet->getCell('J' . $row)->getCalculatedValue() ?? '');
                            $collabPhoneRaw = trim($currentSheet->getCell('K' . $row)->getCalculatedValue() ?? '');
                            $statusRaw = trim($currentSheet->getCell('L' . $row)->getCalculatedValue() ?? '');
                            $collabAccount = trim($currentSheet->getCell('M' . $row)->getCalculatedValue() ?? '');
                            $bank = trim($currentSheet->getCell('N' . $row)->getCalculatedValue() ?? '');
                            $note = trim($currentSheet->getCell('O' . $row)->getCalculatedValue() ?? '');
                            $accessories = trim($currentSheet->getCell('P' . $row)->getCalculatedValue() ?? '');
                            
                            // Debug log để kiểm tra dữ liệu
                            Log::info("Row $row - Data: OrderCode='$orderCode', AgencyName='$agencyName', CustomerName='$customerName', Product='$product'");
                            
                            // Đặc biệt debug cho sheet T7
                            if ($sheetName === 'T7') {
                                Log::info("T7 Row $row - Data: OrderCode='$orderCode', AgencyName='$agencyName', CustomerName='$customerName', Product='$product'");
                            }

                            // Chuẩn hóa dữ liệu
                            $agencyPhone = $sanitizePhone($agencyPhoneRaw);
                            $customerPhone = $sanitizePhone($customerPhoneRaw);
                            $collabPhone = $sanitizePhone($collabPhoneRaw);
                            $statusInstall = $parseStatus($statusRaw);

                            // 1. Xử lý Collaborator (CTV) - Upsert với cache
                            $collaboratorId = 1; // Default agency
                            if (!empty($collabName) && !empty($collabPhone)) {
                                if (!isset($collaboratorCache[$collabPhone])) {
                                    $collaborator = WarrantyCollaborator::where('phone', $collabPhone)->first();
                                    
                                    if (!$collaborator) {
                                        // Tạo collaborator mới
                                        $collaborator = new WarrantyCollaborator();
                                        $collaborator->full_name = $collabName;
                                        $collaborator->phone = $collabPhone;
                                        $collaborator->sotaikhoan = $collabAccount;
                                        $collaborator->chinhanh = $bank;
                                        $collaborator->created_at = now(); // Sử dụng đúng tên cột created_at
                                        $collaborator->save();
                                        
                                        $stats['collaborators_created']++;
                                    }
                                    $collaboratorCache[$collabPhone] = $collaborator->id;
                                }
                                $collaboratorId = $collaboratorCache[$collabPhone];
                            }

                            // 2. Xử lý Agency - Upsert với cache
                            $agencyId = null;
                            if (!empty($agencyName) && !empty($agencyPhone)) {
                                if (!isset($agencyCache[$agencyPhone])) {
                                    $agency = Agency::where('phone', $agencyPhone)->first();
                                    
                                    if (!$agency) {
                                        // Tạo agency mới
                                        $agency = new Agency();
                                        $agency->name = $agencyName;
                                        $agency->phone = $agencyPhone;
                                        $agency->sotaikhoan = $collabAccount;
                                        $agency->chinhanh = $bank;
                                        $agency->created_ad = now();
                                        $agency->save();
                                        
                                        $stats['agencies_created']++;
                                    }
                                    $agencyCache[$agencyPhone] = $agency->id;
                                }
                                $agencyId = $agencyCache[$agencyPhone];
                            }

                            // 3. Xử lý Product - Cache
                            if (!empty($product) && !isset($productCache[$product])) {
                                $existingProduct = OrderProduct::where('product_name', $product)->first();
                                if (!$existingProduct) {
                                    $stats['products_created']++;
                                }
                                $productCache[$product] = true;
                            }

                            // 4. Tạo/Cập nhật Order - Kiểm tra trùng lặp trước khi tạo
                            $order = Order::where('order_code2', $orderCode)->first();
                            if (!$order) {
                                $order = new Order();
                                $order->order_code2 = $orderCode;
                                $order->created_at = $createdAt ?: now();
                                $stats['orders_created']++;
                            } else {
                                $stats['orders_updated']++;
                            }
                            
                            $order->customer_name = $customerName;
                            $order->customer_phone = $customerPhone;
                            $order->customer_address = $customerAddress;
                            $order->agency_name = $agencyName;
                            $order->agency_phone = $agencyPhone;
                            $order->collaborator_id = $collaboratorId;
                            $order->status_install = $statusInstall;
                            $order->successed_at = $statusInstall == 2 ? $createdAt : null;
                            $order->payment_method = 'cash'; // Thêm giá trị mặc định
                            $order->status = 'pending'; // Thêm giá trị mặc định
                            $order->status_tracking = 'pending'; // Thêm giá trị mặc định
                            $order->staff = 'system'; // Thêm giá trị mặc định
                            $order->zone = 'default'; // Thêm giá trị mặc định
                            $order->type = 'online'; // Thêm giá trị mặc định
                            $order->shipping_unit = 'default'; // Thêm giá trị mặc định
                            $order->send_camon = 0; // Thêm giá trị mặc định
                            $order->send_khbh = 0; // Thêm giá trị mặc định
                            $order->ip_rate = ''; // Thêm giá trị mặc định
                            $order->note = ''; // Thêm giá trị mặc định cho trường note
                            $order->note_admin = ''; // Thêm giá trị mặc định cho trường note_admin
                            $order->check_return = 0; // Thêm giá trị mặc định cho trường check_return
                            $order->save();

                            // 5. Tạo/Cập nhật InstallationOrder - Kiểm tra trùng lặp
                            $installationOrder = InstallationOrder::where('order_code', $orderCode)->first();
                            if (!$installationOrder) {
                                $installationOrder = new InstallationOrder();
                                $stats['installation_orders_created']++;
                            } else {
                                $stats['installation_orders_updated']++;
                            }
                            
                            $installationOrder->order_code = $orderCode;
                            $installationOrder->full_name = $customerName;
                            $installationOrder->phone_number = $customerPhone;
                            $installationOrder->address = $customerAddress;
                            $installationOrder->product = $product;
                            $installationOrder->collaborator_id = $collaboratorId;
                            $installationOrder->status_install = $statusInstall;
                            $installationOrder->reviews_install = $note;
                            $installationOrder->agency_name = $agencyName;
                            $installationOrder->agency_phone = $agencyPhone;
                            $installationOrder->type = 'donhang';
                            $installationOrder->created_at = $createdAt ?: now();
                            $installationOrder->successed_at = $statusInstall == 2 ? $createdAt : null;
                            $installationOrder->save();

                            // 6. Tạo/Cập nhật WarrantyRequest nếu cần - Kiểm tra trùng lặp
                            if (!empty($product) && $statusInstall > 0) {
                                $warrantyRequest = WarrantyRequest::where('serial_number', $orderCode)->first();
                                if (!$warrantyRequest) {
                                    $warrantyRequest = new WarrantyRequest();
                                    $warrantyRequest->serial_number = $orderCode;
                                    $stats['warranty_requests_created']++;
                                } else {
                                    $stats['warranty_requests_updated']++;
                                }
                                
                                $warrantyRequest->product = $product;
                                $warrantyRequest->full_name = $customerName;
                                $warrantyRequest->phone_number = $customerPhone;
                                $warrantyRequest->address = $customerAddress;
                                $warrantyRequest->collaborator_id = $collaboratorId;
                                $warrantyRequest->agency_name = $agencyName;
                                $warrantyRequest->agency_phone = $agencyPhone;
                                $warrantyRequest->status_install = $statusInstall;
                                $warrantyRequest->Ngaytao = $createdAt ?: now();
                                $warrantyRequest->type = 'agent_home';
                                $warrantyRequest->branch = 'default'; // Thêm giá trị mặc định cho trường branch
                                $warrantyRequest->return_date = $createdAt ?: now(); // Thêm giá trị mặc định cho trường return_date
                                $warrantyRequest->shipment_date = $createdAt ?: now(); // Thêm giá trị mặc định cho trường shipment_date
                                $warrantyRequest->received_date = $createdAt ?: now(); // Thêm giá trị mặc định cho trường received_date
                                $warrantyRequest->warranty_end = $createdAt ? Carbon::parse($createdAt)->addYear() : now()->addYear(); // Thêm giá trị mặc định cho trường warranty_end
                                $warrantyRequest->staff_received = 'system'; // Thêm giá trị mặc định cho trường staff_received
                                $warrantyRequest->save();
                            }

                            $stats['imported']++;
                            Log::info("Successfully processed row $row in sheet $sheetName");

                        } catch (\Exception $e) {
                            $stats['errors'][] = "Sheet $sheetName, Dòng $row: " . $e->getMessage();
                            Log::error("Import Excel error at sheet $sheetName, row $row: " . $e->getMessage());
                        }
                    }

                    // Giải phóng memory sau mỗi sheet
                    $currentSheet->disconnectCells();
                    unset($currentSheet);
                    
                } catch (\Exception $e) {
                    $stats['errors'][] = "Lỗi xử lý sheet $s: " . $e->getMessage();
                    Log::error("Error processing sheet $s: " . $e->getMessage());
                }
            }

            // Giải phóng memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => "Đồng bộ thành công! Đã xử lý {$stats['imported']} dòng từ {$stats['sheets_processed']} sheet. Tạo mới: {$stats['orders_created']} đơn hàng, {$stats['installation_orders_created']} lắp đặt, {$stats['warranty_requests_created']} bảo hành. Cập nhật: {$stats['orders_updated']} đơn hàng, {$stats['installation_orders_updated']} lắp đặt, {$stats['warranty_requests_updated']} bảo hành.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý file: ' . $e->getMessage(),
            ], 500);
        }
    }
}