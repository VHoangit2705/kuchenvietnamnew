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
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('id');
            
        // Đã hoàn thành status_install = 2
        $lstInstallOrder = InstallationOrder::where('status_install', 2)
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
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('id');
            
        // Đã thanh toán status_install = 3
        $lstPaidOrder = InstallationOrder::where('status_install', 3)
            ->when($madon = request('madon'), fn($q) => $q->where('order_code', 'like', "%$madon%"))
            ->when($sanpham = request('sanpham'), fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('created_at', '>=', $tungay))
            ->when($denngay = request('denngay'), function($q) use ($denngay) {
                if (!empty($denngay)) {
                    $q->whereDate('created_at', '<=', $denngay);
                }
            })
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
                } elseif ($trangthai === '3') {
                    $q->where('status_install', 3);
                }
            })
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', 1);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', 1);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('id');
            
        $counts = [
            'dieuphoidonhang' => (clone $lstOrder)->count(),
            'dieuphoidonhangle' => (clone $lstOrderLe)->count(),
            'dieuphoibaohanh' => (clone $lstWarranty)->count(),
            'dadieuphoi'      => (clone $lstDaDieuPhoi)->count(),
            'dahoanthanh'     => (clone $lstInstallOrder)->count(),
            'dathanhtoan'     => (clone $lstPaidOrder)->count(),
        ];

        $tab = $request->get('tab', 'dieuphoidonhang');
        $tabQuery = match ($tab) {
            'dieuphoidonhangle' => $lstOrderLe,
            'dieuphoibaohanh' => $lstWarranty,
            'dadieuphoi'      => $lstDaDieuPhoi,
            'dahoanthanh'     => $lstInstallOrder,
            'dathanhtoan'     => $lstPaidOrder,
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

            // Kiểm tra trạng thái đơn hàng
            $isPaidOrder = ($model->status_install === 3);
            $action = $request->input('action');
            
            // Chỉ chặn nếu cố gắng thay đổi trạng thái từ "Đã thanh toán" (3)
            // Cho phép 'update' để cập nhật thông tin, chỉ chặn 'complete' và 'payment'
            if ($isPaidOrder && in_array($action, ['complete', 'payment'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng đã thanh toán, không thể thay đổi trạng thái! Bạn chỉ có thể cập nhật thông tin khác.',
                    'error_code' => 'PAID_ORDER_STATUS_LOCKED'
                ], 403);
            }

            if (in_array($action, ['complete', 'payment']) && !$request->successed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu nhập ngày hoàn thành!'
                ]);
            }
            // Xử lý thay đổi trạng thái
            if (!$isPaidOrder) {
                // Chỉ thay đổi trạng thái nếu không phải đơn hàng đã thanh toán
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
            } else {
                // Nếu là đơn hàng đã thanh toán, chỉ cho phép cập nhật thông tin
                // KHÔNG thay đổi trạng thái (giữ nguyên status_install = 3)
                // vẫn cho phép cập nhật các thông tin khác
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

            try {
                // Chỉ thực hiện updateOrCreate nếu có order_code
                $orderCode = $model->order_code2 ?? $model->serial_number ?? $model->order_code;
                if (!empty($orderCode)) {
                    InstallationOrder::updateOrCreate(
                        [
                            'order_code' => $orderCode,
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
                }
            } catch (\Exception $e) {
                Log::error("InstallationOrder updateOrCreate failed", [
                    'error' => $e->getMessage(),
                    'model_id' => $model->id,
                    'order_code' => $orderCode ?? 'empty'
                ]);
            }

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
}