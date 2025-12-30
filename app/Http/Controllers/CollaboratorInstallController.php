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
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kho\Agency;
use App\Http\Controllers\SaveLogController;
use App\Models\KyThuat\EditCtvHistory;
use App\Models\KyThuat\RequestAgency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class CollaboratorInstallController extends Controller
{
    static $pageSize = 50;
    protected $saveLogController;

    public function __construct()
    {
        $this->saveLogController = new SaveLogController();
    }

    public function Index(Request $request)
    {
        $tab = 'donhang';
        $counts = [
            'donhang' => 0,
            'dieuphoidonhangle' => 0,
            'dieuphoibaohanh' => 0,
            'dadieuphoi' => 0,
            'dailylapdat' => 0,
            'dahoanthanh' => 0,
            'dathanhtoan' => 0,
        ];
        return view('collaboratorinstall.index', compact('counts', 'tab'));
    }

    /**
     * Lấy dữ liệu cho tab cụ thể qua AJAX
     */
    public function getTabData(Request $request)
    {
        try {
            $tab = $request->get('tab', 'donhang');
            $view = session('brand') === 'hurom' ? 3 : 1;

            $mainQuery = $this->buildTabQuery($tab, $view);
            $mainQuery = $this->applyTabFilters($mainQuery, $tab, $request);
            if ($tab === 'dieuphoibaohanh') {
                $data = $mainQuery->orderByDesc('received_date')->orderByDesc('id')->paginate(50)->withQueryString();
            } elseif (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
                $data = $mainQuery->orderByDesc('orders.created_at')->orderByDesc('order_products.id')->paginate(50)->withQueryString();               
            } elseif ($tab === 'dadieuphoi') {
                $data = $mainQuery->orderByDesc('installation_orders.dispatched_at')
                    ->orderByDesc('installation_orders.created_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dailylapdat') {
                $data = $mainQuery->orderByDesc('installation_orders.agency_at')
                    ->orderByDesc('installation_orders.dispatched_at')
                    ->orderByDesc('installation_orders.created_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dahoanthanh') {
                $data = $mainQuery->orderByDesc('installation_orders.successed_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } elseif ($tab === 'dathanhtoan') {
                $data = $mainQuery->orderByDesc('installation_orders.paid_at')
                    ->orderByDesc('installation_orders.successed_at')
                    ->orderByDesc('installation_orders.id')
                    ->paginate(50)->withQueryString();
            } else {
                $data = $mainQuery->orderByDesc('installation_orders.created_at')->orderByDesc('installation_orders.id')->paginate(50)->withQueryString();
            }

            $startRender = microtime(true);
            $html = view('collaboratorinstall.tablecontent', compact('data', 'tab'))->render();
            $renderTime = round((microtime(true) - $startRender) * 1000, 2);
            
            return response()->json([
                'table' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu: ' . $e->getMessage(),
                'table' => '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>'
            ], 500);
        }
    }

    /**
     * Build query cho từng tab
     */
    private function buildTabQuery($tab, $view)
    {
        try {
            $queryBuilders = [
            'donhang' => function () use ($view) {
                return OrderProduct::join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->join('orders', 'order_products.order_id', '=', 'orders.id')
                    ->leftJoin('installation_orders as io', function($join) {
                        $join->on(function($q) {
                            $q->whereColumn('io.order_code', 'orders.order_code2')
                              ->orWhereColumn('io.order_code', 'orders.order_code1');
                        })
                        ->whereColumn('io.product', 'order_products.product_name')
                        ->where('io.status_install', '>=', 1);
                    })
                    ->where('p.view', $view)
                    ->select(
                        'order_products.id',
                        'order_products.order_id',
                        'order_products.product_name',
                        'order_products.VAT',
                        'orders.order_code2',
                        'orders.zone',
                        'orders.created_at',
                        'orders.status_install',
                        'orders.agency_name',
                        'orders.agency_phone',
                        'orders.customer_name',
                        'orders.customer_phone',
                    )
                    ->where('order_products.install', 1)
                    ->where('orders.order_code2', 'not like', 'KU%')
                    ->where(function ($sub) {
                        $sub->whereNull('orders.status_install')
                            ->orWhere('orders.status_install', 0);
                    })
                    ->whereNull('orders.collaborator_id')
                    ->whereNull('io.id')
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoidonhangle' => function () use ($view) {
                return OrderProduct::join('products as p', function($join) {
                        $join->on('order_products.product_name', '=', 'p.product_name');
                    })
                    ->join('orders', 'order_products.order_id', '=', 'orders.id')
                    ->leftJoin('installation_orders as io', function($join) {
                        $join->on(function($q) {
                            $q->whereColumn('io.order_code', 'orders.order_code2')
                              ->orWhereColumn('io.order_code', 'orders.order_code1');
                        })
                        ->whereColumn('io.product', 'order_products.product_name')
                        ->where('io.status_install', '>=', 1);
                    })
                    ->where('p.view', $view)
                    ->select(
                        'order_products.id',
                        'order_products.order_id',
                        'order_products.product_name',
                        'order_products.VAT',
                        'orders.order_code2',
                        'orders.zone',
                        'orders.created_at',
                        'orders.status_install',
                        'orders.agency_name',
                        'orders.agency_phone',
                        'orders.customer_name',
                        'orders.customer_phone',
                    )
                    ->where('order_products.install', 1)
                    ->where(function ($sub) {
                        $sub->whereNull('orders.status_install')
                            ->orWhere('orders.status_install', 0);
                    })
                    ->whereNull('orders.collaborator_id')
                    ->whereIn('orders.type', [
                        'warehouse_branch',
                        'warehouse_ghtk',
                        'warehouse_viettel'
                    ])
                    ->whereNull('io.id')
                    ->orderByDesc('orders.created_at');
            },
            'dieuphoibaohanh' => function () use ($view) {
                return WarrantyRequest::where('type', 'agent_component')
                    ->where('view', $view);
            },
            'dadieuphoi' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 1)
                    ->whereNotNull('installation_orders.collaborator_id');
            },
            'dailylapdat' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 1)
                    ->where(function ($q) {
                        $q->whereNotNull('installation_orders.agency_name')
                          ->where('installation_orders.agency_name', '!=', '');
                    })
                    ->whereNull('installation_orders.collaborator_id');
            },
            'dahoanthanh' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 2);
            },
            'dathanhtoan' => function () use ($view) {
                return InstallationOrder::leftJoin('products as p', function($join){
                        $join->on('installation_orders.product', '=', 'p.product_name');
                    })
                    ->where(function($q) use ($view) {
                        $q->where('p.view', $view)->orWhereNull('p.view');
                    })
                    ->select('installation_orders.*')
                    ->where('installation_orders.status_install', 3);
            },
        ];
        
        $query = ($queryBuilders[$tab] ?? $queryBuilders['donhang'])();
        return $query;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Áp dụng filter động cho query
     */
    private function applyTabFilters($query, $tab, Request $request)
    {
        try {
            $tungay = $request->filled('tungay') ? (trim($request->input('tungay')) ?: null) : null;
            $denngay = $request->filled('denngay') ? (trim($request->input('denngay')) ?: null) : null;
            $madon = $request->filled('madon') ? (trim($request->input('madon')) ?: null) : null;
            $sanpham = $request->filled('sanpham') ? (trim($request->input('sanpham')) ?: null) : null;
            $trangthai = $request->filled('trangthai') && $request->input('trangthai') !== '' ? $request->input('trangthai') : null;
            $phanloai = $request->filled('phanloai') && $request->input('phanloai') !== '' ? $request->input('phanloai') : null;
            $customer_name = $request->filled('customer_name') ? (trim($request->input('customer_name')) ?: null) : null;
            $customer_phone = $request->filled('customer_phone') ? (trim($request->input('customer_phone')) ?: null) : null;
            $agency_phone = $request->filled('agency_phone') ? (trim($request->input('agency_phone')) ?: null) : null;
            $agency_name = $request->filled('agency_name') ? (trim($request->input('agency_name')) ?: null) : null;

        if (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
            $query->when(!empty($madon), function ($q) use ($madon) {
                $q->where('orders.order_code2', 'like', "%$madon%");
            })
            ->when(!empty($sanpham), function ($q) use ($sanpham) {
                $q->where('order_products.product_name', 'like', "%$sanpham%");
            })
            ->when(!empty($tungay), function ($q) use ($tungay) {
                $q->whereDate('orders.created_at', '>=', $tungay);
            })
            ->when(!empty($denngay), function ($q) use ($denngay) {
                $q->whereDate('orders.created_at', '<=', $denngay);
            })
            ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
                if ($trangthai === '0') {
                    $q->where(function ($sub) {
                        $sub->whereNull('orders.status_install')->orWhere('orders.status_install', 0);
                    });
                } elseif ($trangthai === '1') {
                    $q->where('orders.status_install', 1);
                } elseif ($trangthai === '2') {
                    $q->where('orders.status_install', 2);
                } elseif ($trangthai === '3') {
                    $q->where('orders.status_install', 3);
                }
            })
            ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where(function ($sub) {
                        $sub->whereNull('orders.agency_name')
                            ->orWhere('orders.agency_name', '');
                    });
                } elseif ($phanloai === 'agency') {
                    $q->whereNotNull('orders.agency_name')
                      ->where('orders.agency_name', '!=', '');
                }
            })
            ->when(!empty($customer_name), function ($q) use ($customer_name) {
                $q->where('orders.customer_name', 'like', "%$customer_name%");
            })
            ->when(!empty($customer_phone), function ($q) use ($customer_phone) {
                $q->where('orders.customer_phone', 'like', "%$customer_phone%");
            })
            ->when(!empty($agency_phone), function ($q) use ($agency_phone) {
                $q->where('orders.agency_phone', 'like', "%$agency_phone%");
            })
            ->when(!empty($agency_name), function ($q) use ($agency_name) {
                $q->where('orders.agency_name', 'like', "%$agency_name%");
            });
        }
        elseif ($tab === 'dieuphoibaohanh') {
            $query->when(!empty($madon), fn($q) => $q->where('serial_number', 'like', "%$madon%"))
                ->when(!empty($sanpham), fn($q) => $q->where('product', 'like', "%$sanpham%"))
                ->when(!empty($tungay), fn($q) => $q->whereDate('received_date', '>=', $tungay))
                ->when(!empty($denngay), function($q) use ($denngay) {
                    $q->whereDate('received_date', '<=', $denngay);
                })
                ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
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
                ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $q->where(function ($sub) {
                            $sub->whereNull('agency_name')
                                ->orWhere('agency_name', '');
                        });
                    } elseif ($phanloai === 'agency') {
                        $q->whereNotNull('agency_name')
                          ->where('agency_name', '!=', '');
                    }
                })
                ->when(!empty($customer_name), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
                ->when(!empty($customer_phone), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
                ->when(!empty($agency_name), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
                ->when(!empty($agency_phone), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
        }
        else {
            $dateField = 'installation_orders.created_at';
            if ($tab === 'dadieuphoi') {
                $dateField = 'installation_orders.dispatched_at';
            } elseif ($tab === 'dailylapdat') {
                $dateField = 'installation_orders.agency_at';
            } elseif ($tab === 'dahoanthanh') {
                $dateField = 'installation_orders.successed_at';
            } elseif ($tab === 'dathanhtoan') {
                $dateField = 'installation_orders.paid_at';
            }
            
            $query->when(!empty($madon), fn($q) => $q->where('installation_orders.order_code', 'like', "%$madon%"))
                ->when(!empty($sanpham), fn($q) => $q->where('installation_orders.product', 'like', "%$sanpham%"))
                ->when(!empty($tungay), function($q) use ($dateField, $tungay) {
                    $q->whereDate($dateField, '>=', $tungay);
                })
                ->when(!empty($denngay), function($q) use ($dateField, $denngay) {
                    $q->whereDate($dateField, '<=', $denngay);
                })
                ->when(!is_null($trangthai) && $trangthai !== '', function ($q) use ($trangthai) {
                    if ($trangthai === '0') {
                        $q->where(function ($sub) {
                            $sub->whereNull('installation_orders.status_install')
                                ->orWhere('installation_orders.status_install', 0);
                        });
                    } elseif ($trangthai === '1') {
                        $q->where('installation_orders.status_install', 1);
                    } elseif ($trangthai === '2') {
                        $q->where('installation_orders.status_install', 2);
                    } elseif ($trangthai === '3') {
                        $q->where('installation_orders.status_install', 3);
                    }
                })
                ->when(!is_null($phanloai) && $phanloai !== '', function ($q) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $q->where(function ($sub) {
                            $sub->whereNull('installation_orders.agency_name')
                                ->orWhere('installation_orders.agency_name', '');
                        });
                    } elseif ($phanloai === 'agency') {
                        $q->whereNotNull('installation_orders.agency_name')
                          ->where('installation_orders.agency_name', '!=', '');
                    }
                })
                ->when(!empty($customer_name), fn($q) => $q->where('installation_orders.full_name', 'like', "%$customer_name%"))
                ->when(!empty($customer_phone), fn($q) => $q->where('installation_orders.phone_number', 'like', "%$customer_phone%"))
                ->when(!empty($agency_name), fn($q) => $q->where('installation_orders.agency_name', 'like', "%$agency_name%"))
                ->when(!empty($agency_phone), fn($q) => $q->where('installation_orders.agency_phone', 'like', "%$agency_phone%"));
        }
            return $query;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function Details(Request $request)
    {
        try {
            $installationOrder = null;
            $order = null;
            $orderCode = null;
            $statusInstall = null;
            $productName = null;
            
            if ($request->type == 'donhang') {
                $data = OrderProduct::with('order')->findOrFail($request->id);
                $order = $data->order;
                $productName = $data->product_name ?? null;
                $orderCode = $order->order_code2 ?? $order->order_code1 ?? null;
                $installationOrder = null;
            
            if ($orderCode) {
                $installationOrder = InstallationOrder::where('order_code', $orderCode)
                    ->when($productName, fn($q) => $q->where('product', $productName))
                    ->first();
                if ($installationOrder) {
                    $data = $installationOrder;
                    $statusInstall = $installationOrder->status_install ?? null;
                    $productName = $installationOrder->product ?? $productName;
                } else {
                    $statusInstall = $order->status_install ?? null;
                }
            } else {
                $statusInstall = $order->status_install ?? null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? $order->province ?? null) : ($order->province ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? $order->district ?? null) : ($order->district ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? $order->wards ?? null) : ($order->wards ?? null);
            $agency_phone = $installationOrder ? ($installationOrder->agency_phone ?? $order->agency_phone) : $order->agency_phone;
            
        } else if ($request->type == 'baohanh') {
            $data = WarrantyRequest::findOrFail($request->id);
            $orderCode = $data->serial_number ?? null;
            $installationOrder = null;
            
            if ($orderCode) {
                $installationOrder = InstallationOrder::where('order_code', $orderCode)->first();
                if ($installationOrder) {
                    $data = $installationOrder;
                    $statusInstall = $installationOrder->status_install ?? null;
                } else {
                    $statusInstall = $data->status_install ?? null;
                }
            } else {
                $statusInstall = $data->status_install ?? null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? $data->province_id ?? null) : ($data->province_id ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? $data->district_id ?? null) : ($data->district_id ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? $data->ward_id ?? null) : ($data->ward_id ?? null);
            $agency_phone = $installationOrder ? ($installationOrder->agency_phone ?? $data->agency_phone) : $data->agency_phone;
            
        } else {
            $data = InstallationOrder::find($request->id);
            
            if ($data) {
                $installationOrder = $data;
                $orderCode = $data->order_code ?? null;
                $statusInstall = $data->status_install ?? null;
                $order = null;
            } else {
                $orderProduct = OrderProduct::with('order')->find($request->id);
                if ($orderProduct) {
                    $data = $orderProduct;
                    $order = $orderProduct->order;
                    $productName = $orderProduct->product_name ?? null;
                    $orderCode = $order->order_code2 ?? $order->order_code1 ?? null;
                    $statusInstall = $order->status_install ?? null;
                    $installationOrder = null;
                    
                    if ($orderCode) {
                        $installationOrder = InstallationOrder::where('order_code', $orderCode)
                            ->when($productName, fn($q) => $q->where('product', $productName))
                            ->first();
                        if ($installationOrder) {
                            $data = $installationOrder;
                            $statusInstall = $installationOrder->status_install ?? null;
                        }
                    }
                } else {
                    throw new ModelNotFoundException("No query results for model [App\Models\Kho\InstallationOrder] {$request->id}");
                }
            }
            
            if ($orderCode && !isset($order)) {
                $order = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
            }
            
            if (($statusInstall === null || $statusInstall == 0) && isset($order) && $order) {
                $data = $order;
                $installationOrder = null;
            }
            
            $provinceId = $installationOrder ? ($installationOrder->province_id ?? null) : ($order->province ?? null);
            $districtId = $installationOrder ? ($installationOrder->district_id ?? null) : ($order->district ?? null);
            $wardId     = $installationOrder ? ($installationOrder->ward_id ?? null) : ($order->wards ?? null);
            $agency_phone = $installationOrder ? $installationOrder->agency_phone : ($order->agency_phone ?? null);
        }
        
        $requestAgency = null;
        $requestAgencyAgency = null;
        if ($orderCode) {
            $requestAgency = RequestAgency::where('order_code', $orderCode)
                ->when($productName, function($q) use ($productName) {
                    $q->where(function($sub) use ($productName) {
                        $sub->whereNull('product_name')
                            ->orWhere('product_name', $productName);
                    });
                })
                ->whereIn('status', [
                    RequestAgency::STATUS_CHUA_XAC_NHAN_AGENCY,
                    RequestAgency::STATUS_DA_XAC_NHAN_AGENCY
                ])
                ->orderByDesc('created_at')
                ->first();
            
            if ($requestAgency) {
                if (!empty($requestAgency->agency_id)) {
                    $requestAgencyAgency = Agency::find($requestAgency->agency_id);
                }

                if (empty($agency_phone) && $requestAgencyAgency?->phone) {
                    $agency_phone = $requestAgencyAgency->phone;
                }
                
                if ($installationOrder && empty($installationOrder->address)) {
                    $installationOrder->address = $requestAgency->installation_address;
                }
            }
        }
        
        $agency = null;
        
        if ($installationOrder && $installationOrder->agency_id) {
            $agency = Agency::find($installationOrder->agency_id);
        }
        
        if (!$agency && $agency_phone) {
            $normalizedPhone = preg_replace('/[^0-9]/', '', $agency_phone);
            
            $agency = Agency::where('phone', $normalizedPhone)->first();
            
            if (!$agency && !empty($normalizedPhone)) {
                $agency = Agency::where('phone', 'like', '%' . $normalizedPhone . '%')->first();
            }
            
            if (!$agency) {
                $agency = Agency::where('phone', $agency_phone)->first();
            }
        }
        
        if (!$agency && isset($requestAgencyAgency) && $requestAgencyAgency) {
            $agency = $requestAgencyAgency;
        }
        
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

        return view('collaboratorinstall.details', compact(
            'data',
            'lstCollaborator',
            'provinces',
            'agency',
            'fullAddress',
            'provinceName',
            'districtName',
            'wardName',
            'provinceId',
            'districtId',
            'wardId',
            'installationOrder',
            'order',
            'orderCode',
            'statusInstall',
            'requestAgency',
            'requestAgencyAgency'
        ));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function Update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'installcost' => 'nullable|numeric|min:0',
            'ctv_id' => 'nullable',
            'successed_at' => 'nullable',
            'installreview' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'agency_name' => 'nullable|string|max:255',
            'agency_phone' => 'nullable|string|max:50',
            'agency_address' => 'nullable|string|max:500',
            'agency_bank' => 'nullable|string|max:255',
            'agency_paynumber' => 'nullable|string|max:100',
            'agency_branch' => 'nullable|string|max:255',
            'agency_cccd' => 'nullable|string|max:20',
            'agency_release_date' => 'nullable|date',
            'bank_account' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installationOrder = InstallationOrder::find($request->id);
            $sourceOrder = null;
            $sourceWarrantyRequest = null;
            $sourceOrderProduct = null;
            $orderCode = null;
            $productName = null;
            
            if (!$installationOrder) {
                $sourceModel = match ($request->type) {
                    'donhang'  => Order::findOrFail($request->id),
                    'baohanh'  => WarrantyRequest::findOrFail($request->id),
                    default    => InstallationOrder::findOrFail($request->id),
                };
                
                if ($sourceModel instanceof Order) {
                    $sourceOrder = $sourceModel;
                    $orderCode = $sourceOrder->order_code2 ?? $sourceOrder->order_code1;
                    if ($request->has('product_id')) {
                        $sourceOrderProduct = OrderProduct::find($request->product_id);
                        $productName = $sourceOrderProduct?->product_name;
                    } else {
                        $productName = $request->product 
                            ?? OrderProduct::where('order_id', $sourceOrder->id)
                                ->where('install', 1)
                                ->value('product_name');
                    }
                } elseif ($sourceModel instanceof WarrantyRequest) {
                    $sourceWarrantyRequest = $sourceModel;
                    $orderCode = $sourceWarrantyRequest->serial_number;
                    $productName = $sourceWarrantyRequest->product ?? null;
                } else {
                    $installationOrder = $sourceModel;
                    $orderCode = $installationOrder->order_code ?? null;
                    $productName = $installationOrder->product ?? null;
                }
            } else {
                $orderCode = $installationOrder->order_code ?? null;
                $productName = $installationOrder->product ?? null;
            }
            
            if (!$installationOrder && $orderCode) {
                $sourceOrder = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
                if ($sourceOrder && !$productName) {
                    $productName = $request->product 
                        ?? OrderProduct::where('order_id', $sourceOrder->id)
                            ->where('install', 1)
                            ->value('product_name');
                }
            }

            $currentStatusInstall = $installationOrder?->status_install 
                ?? $sourceOrder?->status_install 
                ?? $sourceWarrantyRequest?->status_install 
                ?? null;
            
            $isPaidOrder = ($currentStatusInstall === 3);
            $action = $request->input('action');
            
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
            
            $oldCollaboratorId = $installationOrder?->collaborator_id ?? null;
            $oldStatus = $currentStatusInstall;
            
            $ctvId = $request->ctv_id ?: null;

            $agencyName = $request->input('agency_name');
            $agencyPhone = $request->input('agency_phone');
            $agencyAddress = $request->input('agency_address');
            $agencyBank = $request->input('agency_bank');
            $agencyPaynumber = $request->input('agency_paynumber');
            $agencyBranch = $request->input('agency_branch');
            $agencyCccd = $request->input('agency_cccd');
            $agencyReleaseDate = $request->input('agency_release_date');
            $bankAccount = $request->input('bank_account');

            $isCollaboratorInstall = !empty($ctvId);
            $isAgencyInstall = !$isCollaboratorInstall && (!empty($agencyName) || !empty($agencyPhone));
            
            $newStatusInstall = $currentStatusInstall;
            if (!$isPaidOrder) {
                if ($action) {
                    switch ($action) {
                        case 'update':
                            $newStatusInstall = 1;
                            break;

                        case 'complete':
                            $newStatusInstall = 2;
                            break;

                        case 'payment':
                            $newStatusInstall = 3;
                            break;
                    }
                } else {
                    if (($isCollaboratorInstall || $isAgencyInstall) && 
                        ($currentStatusInstall === null || $currentStatusInstall == 0)) {
                        $newStatusInstall = 1;
                    }
                }
            }

            $reviewsInstallFilename = $installationOrder?->reviews_install ?? null;
            if ($request->hasFile('installreview')) {
                $file = $request->file('installreview');
                $extension = strtolower($file->getClientOriginalExtension());

                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.pdf';
                $savePath = storage_path('app/public/install_reviews/' . $filename);

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $imgData = base64_encode(file_get_contents($file->getRealPath()));
                    $html = '<img src="data:image/' . $extension . ';base64,' . $imgData . '" style="width:100%;">';
                    $pdf = Pdf::loadHTML($html);
                    $pdf->save($savePath);
                } elseif ($extension === 'pdf') {
                    $file->storeAs('install_reviews', $filename, 'public');
                }

                $reviewsInstallFilename = $filename;
            }

            if (empty($orderCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy mã đơn hàng!'
                ], 400);
            }

            if (empty($productName)) {
                $productName = $request->product
                    ?? $requestAgency?->product_name
                    ?? $sourceOrder?->product_name
                    ?? $sourceWarrantyRequest?->product
                    ?? $installationOrder?->product
                    ?? null;
            }

            $requestAgency = RequestAgency::where('order_code', $orderCode)
                ->when($productName, function($q) use ($productName) {
                    $q->where(function($sub) use ($productName) {
                        $sub->whereNull('product_name')
                            ->orWhere('product_name', $productName);
                    });
                })
                ->first();
            $requestAgencyAgency = null;
            if ($requestAgency && $requestAgency->agency_id) {
                $requestAgencyAgency = Agency::find($requestAgency->agency_id);
            }
            
            if ($isAgencyInstall) {
                $resolvedAgencyName = $agencyName 
                    ?: ($requestAgencyAgency->name ?? null)
                    ?: ($sourceOrder?->agency_name ?? null)
                    ?: '';
                $resolvedAgencyPhone = $agencyPhone 
                    ?: ($requestAgencyAgency->phone ?? null)
                    ?: ($sourceOrder?->agency_phone ?? null)
                    ?: '';

                $resolvedAgencyId = null;
                $agency = null;
                
                $normalizedPhone = !empty($resolvedAgencyPhone) ? preg_replace('/[^0-9]/', '', $resolvedAgencyPhone) : '';
                
                if ($requestAgency && $requestAgency->agency_id) {
                    $agency = Agency::find($requestAgency->agency_id);
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                    }
                }
                
                if (!$agency && !empty($normalizedPhone)) {
                    $agency = Agency::where('phone', $normalizedPhone)->first();
                    
                    if (!$agency) {
                        $agency = Agency::where('phone', 'like', '%' . $normalizedPhone . '%')->first();
                    }
                    
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                        if ($agency->phone !== $normalizedPhone) {
                            $agency->phone = $normalizedPhone;
                            $agency->save();
                        }
                    }
                }
                
                if (!$agency && !empty($agencyCccd)) {
                    $agency = Agency::where('cccd', $agencyCccd)->first();
                    if ($agency) {
                        $resolvedAgencyId = $agency->id;
                    }
                }
                
                if (!empty($normalizedPhone)) {
                    try {
                        $resolvedAgencyPhone = $normalizedPhone;
                        
                        $agencyData = [
                            'name' => $resolvedAgencyName ?: ($agency?->name ?? ''),
                            'phone' => $normalizedPhone,
                            'address' => $agencyAddress ?: ($agency?->address ?? ''),
                            'bank_name_agency' => $agencyBank ?: ($agency?->bank_name_agency ?? ''),
                            'bank_account' => $bankAccount ?: ($agency?->bank_account ?? ''),
                            'sotaikhoan' => $agencyPaynumber ?: ($agency?->sotaikhoan ?? ''),
                            'chinhanh' => $agencyBranch ?: ($agency?->chinhanh ?? ''),
                            'cccd' => $agencyCccd ?: ($agency?->cccd ?? ''),
                            'ngaycap' => $agencyReleaseDate ?: ($agency?->ngaycap ?? null),
                            'create_by' => session('user', 'system'),
                        ];
                        
                        if ($agency) {
                            $updateData = [];
                            if (!empty($resolvedAgencyName)) {
                                $updateData['name'] = $resolvedAgencyName;
                            }
                            if (!empty($agencyAddress)) {
                                $updateData['address'] = $agencyAddress;
                            }
                            if (!empty($agencyBank)) {
                                $updateData['bank_name_agency'] = $agencyBank;
                            }
                            if (!empty($bankAccount)) {
                                $updateData['bank_account'] = $bankAccount;
                            }
                            if (!empty($agencyPaynumber)) {
                                $updateData['sotaikhoan'] = $agencyPaynumber;
                            }
                            if (!empty($agencyBranch)) {
                                $updateData['chinhanh'] = $agencyBranch;
                            }
                            if (!empty($agencyCccd)) {
                                $updateData['cccd'] = $agencyCccd;
                            }
                            if (!empty($agencyReleaseDate)) {
                                $updateData['ngaycap'] = $agencyReleaseDate;
                            }
                            
                            if (!empty($updateData)) {
                                $agency->update($updateData);
                            }
                        } else {
                            $agencyData['created_ad'] = now();
                            
                            if (empty($agencyData['bank_account'])) {
                                $agencyData['bank_account'] = '';
                            }
                            
                            foreach ($agencyData as $key => $value) {
                                if ($value === '' && $key !== 'bank_account') {
                                    $agencyData[$key] = null;
                                }
                            }
                            
                            $agency = Agency::updateOrCreate(
                                ['phone' => $normalizedPhone],
                                $agencyData
                            );
                        }
                        
                        $resolvedAgencyId = $agency->id;
                        
                    } catch (\Exception $e) {
                        Log::error('Lỗi khi tạo/cập nhật agency: ' . $e->getMessage(), [
                            'phone' => $normalizedPhone ?? $resolvedAgencyPhone,
                            'name' => $resolvedAgencyName,
                            'agency_data' => $agencyData ?? [],
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        
                        if (!$resolvedAgencyId && $agency) {
                            $resolvedAgencyId = $agency->id;
                        }
                        
                        if (!$resolvedAgencyId && !empty($normalizedPhone)) {
                            $existingAgency = Agency::where('phone', $normalizedPhone)->first();
                            if ($existingAgency) {
                                $resolvedAgencyId = $existingAgency->id;
                                $agency = $existingAgency;
                            }
                        }
                    }
                } else {
                    if ($installationOrder && $installationOrder->agency_id) {
                        $resolvedAgencyId = $installationOrder->agency_id;
                    }
                }
            } else {
                $resolvedAgencyName = '';
                $resolvedAgencyPhone = '';
                $resolvedAgencyId = null;
            }

            $now = now();
            $dispatchedAt = $installationOrder?->dispatched_at;
            $successedAt = $installationOrder?->successed_at;
            $paidAt = $installationOrder?->paid_at;
            $agencyAt = $installationOrder?->agency_at;
            
            $wasAgencyInstall = !empty($installationOrder?->agency_name) && empty($installationOrder?->collaborator_id);
            $isNowAgencyInstall = $isAgencyInstall && !empty($resolvedAgencyName);
            
            if ($newStatusInstall == 1 && $oldStatus != 1) {
                $dispatchedAt = $now;
            } elseif ($newStatusInstall == 1 && $oldStatus == 1 && !$dispatchedAt) {
                $dispatchedAt = $now;
            }
            
            if ($newStatusInstall == 2 && $oldStatus != 2) {
                $successedAt = $request->successed_at ?? $now;
            } elseif ($newStatusInstall == 2 && $oldStatus == 2 && $request->filled('successed_at')) {
                $successedAt = $request->successed_at;
            }
            
            if ($newStatusInstall == 3 && $oldStatus != 3) {
                $paidAt = $now;
            }
            
            if ($isNowAgencyInstall && !$wasAgencyInstall) {
                $agencyAt = $now;
            } elseif ($isNowAgencyInstall && $wasAgencyInstall && !$agencyAt) {
                $agencyAt = $now;
            }

            $installationOrderData = [
                'full_name'        => $requestAgency?->customer_name 
                    ?? $sourceOrder?->customer_name 
                    ?? $sourceWarrantyRequest?->full_name 
                    ?? $installationOrder?->full_name 
                    ?? '',
                'phone_number'     => $requestAgency?->customer_phone 
                    ?? $sourceOrder?->customer_phone 
                    ?? $sourceWarrantyRequest?->phone_number 
                    ?? $installationOrder?->phone_number 
                    ?? '',
                'address'          => $requestAgency?->installation_address 
                    ?? $sourceOrder?->customer_address 
                    ?? $sourceWarrantyRequest?->address 
                    ?? $installationOrder?->address 
                    ?? '',
                'product'          => $productName 
                    ?? $request->product 
                    ?? $requestAgency?->product_name 
                    ?? $sourceOrder?->product_name 
                    ?? $sourceWarrantyRequest?->product 
                    ?? $installationOrder?->product 
                    ?? '',
                'province_id'      => $sourceOrder?->province 
                    ?? $sourceWarrantyRequest?->province_id 
                    ?? $installationOrder?->province_id 
                    ?? null,
                'district_id'      => $sourceOrder?->district 
                    ?? $sourceWarrantyRequest?->district_id 
                    ?? $installationOrder?->district_id 
                    ?? null,
                'ward_id'          => $sourceOrder?->wards 
                    ?? $sourceWarrantyRequest?->ward_id 
                    ?? $installationOrder?->ward_id 
                    ?? null,
                'collaborator_id'  => $isCollaboratorInstall ? $ctvId : null,
                'install_cost'     => $request->installcost ?? $installationOrder?->install_cost ?? null,
                'status_install'   => $newStatusInstall,
                'reviews_install'  => $reviewsInstallFilename ?? $installationOrder?->reviews_install,
                'agency_name'      => $resolvedAgencyName,
                'agency_phone'     => $resolvedAgencyPhone,
                'agency_id'        => $resolvedAgencyId,
                'type'             => $request->type ?? $sourceOrder?->type ?? 'donhang',
                'zone'             => $sourceOrder?->zone ?? $installationOrder?->zone ?? '',
                'created_at'       => $sourceOrder?->created_at 
                    ?? $sourceWarrantyRequest?->Ngaytao 
                    ?? $installationOrder?->created_at 
                    ?? now(),
                'successed_at'     => $successedAt,
                'dispatched_at'    => $dispatchedAt,
                'paid_at'          => $paidAt,
                'agency_at'        => $agencyAt
            ];
            
            try {
                $before = $installationOrder ? $installationOrder->fresh()->toArray() : [];

                $installationOrder = InstallationOrder::updateOrCreate(
                    [
                        'order_code' => $orderCode,
                        'product'    => $productName ?? ($request->product ?? ''),
                    ],
                    $installationOrderData
                );

                try {
                    $after = $installationOrder->fresh()->toArray();
                    $keys = [
                        'full_name', 'phone_number', 'address', 'province_id', 'district_id', 'ward_id',
                        'order_code', 'product', 'status_install', 'install_cost', 'collaborator_id',
                        'agency_name', 'agency_phone', 'agency_id',
                        'successed_at', 'dispatched_at', 'paid_at', 'agency_at',
                        'type', 'zone', 'reviews_install',
                    ];

                    $old = [];
                    $new = [];
                    foreach ($keys as $k) {
                        $old[$k] = $before[$k] ?? null;
                        $new[$k] = $after[$k] ?? null;
                    }

                    $oldCollaboratorIdForLog = $old['collaborator_id'] ?? null;
                    $newCollaboratorIdForLog = $new['collaborator_id'] ?? null;
                    $oldCollaboratorIdForLog = is_numeric($oldCollaboratorIdForLog) ? (int) $oldCollaboratorIdForLog : null;
                    $newCollaboratorIdForLog = is_numeric($newCollaboratorIdForLog) ? (int) $newCollaboratorIdForLog : null;

                    $hasCollaboratorChange = ($oldCollaboratorIdForLog !== $newCollaboratorIdForLog);

                    if ($hasCollaboratorChange) {
                        $ctvOld = [];
                        $ctvNew = [];
                        $ctvOld['collaborator_id'] = $oldCollaboratorIdForLog;
                        $ctvNew['collaborator_id'] = $newCollaboratorIdForLog;

                        $this->saveLogController->auditLog(
                            (int) $installationOrder->id,
                            (string) $orderCode,
                            'ctv_dispatched',
                            $ctvOld,
                            $ctvNew,
                            'source: CollaboratorInstallController@Update - Điều phối CTV'
                        );
                    }

                    $this->saveLogController->auditLog(
                        (int) $installationOrder->id,
                        (string) $orderCode,
                        'installation_order_updated',
                        $old,
                        $new,
                        'source: CollaboratorInstallController@Update'
                    );
                } catch (\Throwable $auditException) {
                    Log::warning('Audit installation_order_updated failed', [
                        'order_code' => $orderCode,
                        'error' => $auditException->getMessage(),
                    ]);
                }

                if ($requestAgency && $newStatusInstall >= 1) {
                    $oldRequestAgencyStatus = $requestAgency->status;
                    $newRequestAgencyStatus = match($newStatusInstall) {
                        1 => RequestAgency::STATUS_DA_DIEU_PHOI,
                        2 => RequestAgency::STATUS_HOAN_THANH,
                        3 => RequestAgency::STATUS_DA_THANH_TOAN,
                        default => $requestAgency->status
                    };
                    
                    if ($newRequestAgencyStatus != $oldRequestAgencyStatus) {
                        $requestAgency->status = $newRequestAgencyStatus;
                        $requestAgency->assigned_to = session('user', 'system');
                        $requestAgency->save();
                        
                    }
                }
                
            } catch (\Exception $e) {
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật installation order: ' . $e->getMessage(), [
                'request_data' => $request->except(['_token', 'installreview']),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

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
            ->orderBy('full_name')
            ->get();

        $html = view('collaboratorinstall.tablecollaborator', compact('lstCollaborator'))->render();
        return response()->json(['html' => $html]);
    }

    public function UpdateDetailCustomerAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_code'   => 'required|string',
            'full_name'    => 'nullable|string|max:80',
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:150',
            'product'      => 'nullable|string|max:255',
            'province_id'  => 'nullable|integer',
            'district_id'  => 'nullable|integer',
            'ward_id'      => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderCode   = $request->input('order_code');
            $newAddress  = $request->input('address', '');
            $newName     = $request->input('full_name');
            $newPhone    = $request->input('phone_number');
            $product     = $request->input('product');
            $provinceId  = $request->input('province_id');
            $districtId  = $request->input('district_id');
            $wardId      = $request->input('ward_id');

            $installationOrder = InstallationOrder::where('order_code', $orderCode)
                ->when($product, function ($q) use ($product) {
                    $q->where('product', $product);
                })
                ->first();

            if ($installationOrder) {
                if (!is_null($newAddress)) {
                    $installationOrder->address = $newAddress;
                }
                if (!is_null($newName)) {
                    $installationOrder->full_name = $newName;
                }
                if (!is_null($newPhone)) {
                    $installationOrder->phone_number = $newPhone;
                }
                if (!is_null($provinceId)) {
                    $installationOrder->province_id = $provinceId;
                }
                if (!is_null($districtId)) {
                    $installationOrder->district_id = $districtId;
                }
                if (!is_null($wardId)) {
                    $installationOrder->ward_id = $wardId;
                }
                $installationOrder->save();
            } else {
                $order = Order::where('order_code2', $orderCode)
                    ->orWhere('order_code1', $orderCode)
                    ->first();
                $warrantyRequest = null;
                
                if (!$order) {
                    $warrantyRequest = WarrantyRequest::where('serial_number', $orderCode)->first();
                }

                if (!$order && !$warrantyRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy đơn hàng trong hệ thống'
                    ], 404);
                }

                $data = [
                    'order_code' => $orderCode,
                    'address'    => $newAddress,
                ];

                if ($order) {
                    $data['full_name'] = $order->customer_name;
                    $data['phone_number'] = $order->customer_phone;
                    $data['province_id'] = $order->province;
                    $data['district_id'] = $order->district;
                    $data['ward_id'] = $order->wards;
                    $data['agency_name'] = $order->agency_name ?? '';
                    $data['agency_phone'] = $order->agency_phone ?? '';
                    $data['zone'] = $order->zone;
                    $data['type'] = $order->type ?? 'donhang';
                    $data['created_at'] = $order->created_at;
                    if ($product) {
                        $data['product'] = $product;
                    }
                } elseif ($warrantyRequest) {
                    $data['full_name'] = $warrantyRequest->full_name;
                    $data['phone_number'] = $warrantyRequest->phone_number;
                    $data['province_id'] = $warrantyRequest->province_id;
                    $data['district_id'] = $warrantyRequest->district_id;
                    $data['ward_id'] = $warrantyRequest->ward_id;
                    $data['agency_name'] = $warrantyRequest->agency_name ?? '';
                    $data['agency_phone'] = $warrantyRequest->agency_phone ?? '';
                    $data['product'] = $warrantyRequest->product;
                    $data['type'] = 'baohanh';
                    $data['created_at'] = $warrantyRequest->Ngaytao;
                }

                if (!is_null($newName)) {
                    $data['full_name'] = $newName;
                }
                if (!is_null($newPhone)) {
                    $data['phone_number'] = $newPhone;
                }

                $installationOrder = InstallationOrder::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin khách hàng thành công!',
                'address' => $newAddress,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật địa chỉ khách hàng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật địa chỉ: ' . $e->getMessage()
            ], 500);
        }
    }
}
