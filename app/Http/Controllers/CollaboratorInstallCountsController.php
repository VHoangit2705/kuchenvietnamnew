<?php 

namespace App\Http\Controllers;

use App\Models\Kho\OrderProduct;
use App\Models\Kho\InstallationOrder;
use App\Models\KyThuat\WarrantyRequest;
use App\Enum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollaboratorInstallCountsController extends Controller 
{
    public function Counts(Request $request)
    {
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        $counts = [
            'donhang' => $this->getDonHangCount($view, $request),
            'dieuphoidonhangle' => $this->getDonHangLeCount($view, $request),
            'dieuphoibaohanh' => $this->getBaoHanhCount($view, $request),
            'dadieuphoi' => $this->getDaDieuPhoiCount($view, $request),
            'dailylapdat' => $this->getAgencyInstallCount($view, $request),
            'dahoanthanh' => $this->getDaHoanThanhCount($view, $request),
            'dathanhtoan' => $this->getDaThanhToanCount($view, $request),
        ];

        return response()->json($counts);
    }

    private function getDonHangCount($view, $request)
    {
        $query = OrderProduct::join('products as p', function($join){
                $join->on('order_products.product_name', '=', 'p.product_name');
            })
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('p.view', $view)
            ->where('order_products.install', 1)
            ->whereHas('order', function ($q) {
                $q->where('order_code2', 'not like', 'KU%')
                    ->where(function ($sub) {
                        $sub->whereNull('status_install')
                            ->orWhere('status_install', 0);
                    });
                $q->whereNull('collaborator_id');
            });

        return $this->applyCommonFiltersToOrderProduct($query, $request)
            ->orderByDesc('orders.created_at')
            ->count();
    }

    private function getDonHangLeCount($view, $request)
    {
        $query = OrderProduct::join('products as p', function($join){
                $join->on('order_products.product_name', '=', 'p.product_name');
            })
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('p.view', $view)
            ->where('order_products.install', 1)
            ->whereHas('order', function ($q) {
                $q->where(function ($sub) {
                        $sub->whereNull('status_install')
                            ->orWhere('status_install', 0);
                    })
                    ->whereNull('collaborator_id')
                    ->whereIn('type', [
                        'warehouse_branch',
                        'warehouse_ghtk',
                        'warehouse_viettel'
                    ]);
            });

        return $this->applyCommonFiltersToOrderProduct($query, $request)
            ->orderByDesc('orders.created_at')
            ->count();
    }

    private function getBaoHanhCount($view, $request)
    {
        $query = WarrantyRequest::where('type', 'agent_home')
            ->where('view', $view);

        return $this->applyCommonFiltersToWarranty($query, $request)
            ->orderByDesc('Ngaytao')
            ->count();
    }

    private function getDaDieuPhoiCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->where('status_install', 1)
            ->whereNotNull('collaborator_id')
            ->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);

        return $this->applyCommonFiltersToInstallation($query, $request)
            ->orderByDesc('installation_orders.created_at')
            ->count();
    }

    private function getAgencyInstallCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->where('status_install', 1)
            ->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);

        return $this->applyCommonFiltersToInstallation($query, $request)
            ->orderByDesc('installation_orders.created_at')
            ->count();
    }

    private function getDaHoanThanhCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->where('status_install', 2);

        return $this->applyCommonFiltersToInstallation($query, $request)
            ->orderByDesc('installation_orders.created_at')
            ->count();
    }

    private function getDaThanhToanCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->where('status_install', 3);

        return $this->applyCommonFiltersToInstallation($query, $request)->count();
    }

    private function applyCommonFiltersToOrderProduct($query, $request)
    {
        $tungay = $request->input('tungay');
        $denngay = $request->input('denngay');
        $madon = $request->input('madon');
        $sanpham = $request->input('sanpham');
        $trangthai = $request->input('trangthai');
        $phanloai = $request->input('phanloai');
        $customer_name = $request->input('customer_name');
        $customer_phone = $request->input('customer_phone');
        $agency_phone = $request->input('agency_phone');
        $agency_name = $request->input('agency_name');

        return $query->when($madon, function ($q) use ($madon) {
                $q->whereHas('order', function ($sub) use ($madon) {
                    $sub->where('order_code2', 'like', "%$madon%");
                });
            })
            ->when($sanpham, function ($q) use ($sanpham) {
                $q->where('product_name', 'like', "%$sanpham%");
            })
            ->when($tungay && !empty($tungay), function ($q) use ($tungay) {
                $q->whereHas('order', function ($sub) use ($tungay) {
                    $sub->whereDate('created_at', '>=', $tungay);
                });
            })
            ->when($denngay && !empty($denngay), function ($q) use ($denngay) {
                $q->whereHas('order', function ($sub) use ($denngay) {
                    $sub->whereDate('created_at', '<=', $denngay);
                });
            })
            ->when($trangthai, function ($q) use ($trangthai) {
                $q->whereHas('order', function ($sub) use ($trangthai) {
                    if ($trangthai === '0') {
                        $sub->whereNull('status_install')->orWhere('status_install', 0);
                    } elseif ($trangthai === '1') {
                        $sub->where('status_install', 1);
                    } elseif ($trangthai === '2') {
                        $sub->where('status_install', 2);
                    } elseif ($trangthai === '3') {
                        $sub->where('status_install', 3);
                    }
                });
            })
            ->when($phanloai, function ($q) use ($phanloai) {
                $q->whereHas('order', function ($sub) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $sub->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                    } elseif ($phanloai === 'agency') {
                        $sub->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                    }
                });
            })
            ->when($customer_name, function ($q) use ($customer_name) {
                $q->whereHas('order', function ($sub) use ($customer_name) {
                    $sub->where('customer_name', 'like', "%$customer_name%");
                });
            })
            ->when($customer_phone, function ($q) use ($customer_phone) {
                $q->whereHas('order', function ($sub) use ($customer_phone) {
                    $sub->where('customer_phone', 'like', "%$customer_phone%");
                });
            })
            ->when($agency_phone, function ($q) use ($agency_phone) {
                $q->whereHas('order', function ($sub) use ($agency_phone) {
                    $sub->where('agency_phone', 'like', "%$agency_phone%");
                });
            })
            ->when($agency_name, function ($q) use ($agency_name) {
                $q->whereHas('order', function ($sub) use ($agency_name) {
                    $sub->where('agency_name', 'like', "%$agency_name%");
                });
            });
    }

    private function applyCommonFiltersToWarranty($query, $request)
    {
        $tungay = $request->input('tungay');
        $denngay = $request->input('denngay');
        $madon = $request->input('madon');
        $sanpham = $request->input('sanpham');
        $trangthai = $request->input('trangthai');
        $phanloai = $request->input('phanloai');
        $customer_name = $request->input('customer_name');
        $customer_phone = $request->input('customer_phone');
        $agency_phone = $request->input('agency_phone');
        $agency_name = $request->input('agency_name');

        return $query->when($madon, fn($q) => $q->where('serial_number', 'like', "%$madon%"))
            ->when($sanpham, fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('Ngaytao', '>=', $tungay))
            ->when($denngay && !empty($denngay), function($q) use ($denngay) {
                $q->whereDate('Ngaytao', '<=', $denngay);
            })
            ->when($trangthai, function ($q) use ($trangthai) {
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
            ->when($phanloai, function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name, fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone, fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name, fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone, fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
    }

    private function applyCommonFiltersToInstallation($query, $request)
    {
        $tungay = $request->input('tungay');
        $denngay = $request->input('denngay');
        $madon = $request->input('madon');
        $sanpham = $request->input('sanpham');
        $trangthai = $request->input('trangthai');
        $phanloai = $request->input('phanloai');
        $customer_name = $request->input('customer_name');
        $customer_phone = $request->input('customer_phone');
        $agency_phone = $request->input('agency_phone');
        $agency_name = $request->input('agency_name');

        return $query->when($madon, fn($q) => $q->where('order_code', 'like', "%$madon%"))
            ->when($sanpham, fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('created_at', '>=', $tungay))
            ->when($denngay && !empty($denngay), function($q) use ($denngay) {
                $q->whereDate('created_at', '<=', $denngay);
            })
            ->when($trangthai, function ($q) use ($trangthai) {
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
            ->when($phanloai, function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name, fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone, fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name, fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone, fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"));
    }
}
