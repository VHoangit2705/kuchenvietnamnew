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
            ->where('orders.order_code2', 'not like', 'KU%')
            ->where(function ($q) {
                $q->whereNull('orders.status_install')
                    ->orWhere('orders.status_install', 0);
            })
            ->whereNull('orders.collaborator_id');

        return $this->applyCommonFiltersToOrderProduct($query, $request)
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
            ->where(function ($q) {
                $q->whereNull('orders.status_install')
                    ->orWhere('orders.status_install', 0);
            })
            ->whereNull('orders.collaborator_id')
            ->whereIn('orders.type', [
                'warehouse_branch',
                'warehouse_ghtk',
                'warehouse_viettel'
            ]);

        return $this->applyCommonFiltersToOrderProduct($query, $request)
            ->count();
    }

    private function getBaoHanhCount($view, $request)
    {
        $query = WarrantyRequest::where('type', 'agent_home')
            ->where('view', $view);

        return $this->applyCommonFiltersToWarranty($query, $request)
            ->count();
    }

    private function getDaDieuPhoiCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on('installation_orders.product', '=', 'p.product_name');
            })
            ->where('p.view', $view)
            ->where('installation_orders.status_install', 1)
            ->whereNotNull('installation_orders.collaborator_id')
            ->where('installation_orders.collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
    
        return $this->applyCommonFiltersToInstallation($query, $request)
            ->count();
    }

    private function getAgencyInstallCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on('installation_orders.product', '=', 'p.product_name');
            })
            ->where('p.view', $view)
            ->where('installation_orders.status_install', 1)
            ->where('installation_orders.collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
    
        return $this->applyCommonFiltersToInstallation($query, $request)
            ->count();
    }

    private function getDaHoanThanhCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on('installation_orders.product', '=', 'p.product_name');
            })
            ->where('p.view', $view)
            ->where('installation_orders.status_install', 2);
    
        return $this->applyCommonFiltersToInstallation($query, $request)
            ->count();
    }

    private function getDaThanhToanCount($view, $request)
    {
        $query = InstallationOrder::join('products as p', function($join){
                $join->on('installation_orders.product', '=', 'p.product_name');
            })
            ->where('p.view', $view)
            ->where('installation_orders.status_install', 3);
    
        return $this->applyCommonFiltersToInstallation($query, $request)
            ->count();
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

        // Sử dụng điều kiện trực tiếp trên bảng orders đã join thay vì whereHas
        return $query->when($madon, fn($q) => $q->where('orders.order_code2', 'like', "%$madon%"))
            ->when($sanpham, fn($q) => $q->where('order_products.product_name', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('orders.created_at', '>=', $tungay))
            ->when($denngay && !empty($denngay), fn($q) => $q->whereDate('orders.created_at', '<=', $denngay))
            ->when($trangthai, function ($q) use ($trangthai) {
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
            ->when($phanloai, function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('orders.collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('orders.collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name, fn($q) => $q->where('orders.customer_name', 'like', "%$customer_name%"))
            ->when($customer_phone, fn($q) => $q->where('orders.customer_phone', 'like', "%$customer_phone%"))
            ->when($agency_phone, fn($q) => $q->where('orders.agency_phone', 'like', "%$agency_phone%"))
            ->when($agency_name, fn($q) => $q->where('orders.agency_name', 'like', "%$agency_name%"));
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

        return $query->when($madon, fn($q) => $q->where('installation_orders.order_code', 'like', "%$madon%"))
            ->when($sanpham, fn($q) => $q->where('installation_orders.product', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('installation_orders.created_at', '>=', $tungay))
            ->when($denngay && !empty($denngay), function($q) use ($denngay) {
                $q->whereDate('installation_orders.created_at', '<=', $denngay);
            })
            ->when($trangthai, function ($q) use ($trangthai) {
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
            ->when($phanloai, function ($q) use ($phanloai) {
                if ($phanloai === 'collaborator') {
                    $q->where('installation_orders.collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('installation_orders.collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name, fn($q) => $q->where('installation_orders.full_name', 'like', "%$customer_name%"))
            ->when($customer_phone, fn($q) => $q->where('installation_orders.phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name, fn($q) => $q->where('installation_orders.agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone, fn($q) => $q->where('installation_orders.agency_phone', 'like', "%$agency_phone%"));
    }
}
