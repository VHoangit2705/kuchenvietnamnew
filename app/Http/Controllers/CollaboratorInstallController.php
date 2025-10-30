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
use App\Http\Controllers\SaveLogController;
use App\Models\KyThuat\EditCtvHistory;
use App\Enum;
use Illuminate\Support\Facades\DB;

class CollaboratorInstallController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:Xem danh sách CTV')->only(['Index']);
    //     $this->middleware('permission:Cập nhật CTV')->only(['CreateCollaborator', 'DeleteCollaborator']);
    // }
    static $pageSize = 50;
    protected $saveLogController;

    public function __construct()
    {
        $this->saveLogController = new SaveLogController();
        $this->ensureAgencyCollaboratorExists();
    }

    /**
     * Đảm bảo CTV flag "Đại lý lắp đặt" với ID = 1 luôn tồn tại
     * Đây chỉ là một flag để đánh dấu, không phải thông tin thật
     */
    private function ensureAgencyCollaboratorExists()
    {
        try {
            $agencyCtv = WarrantyCollaborator::find(Enum::AGENCY_INSTALL_FLAG_ID);
            if (!$agencyCtv) {
                WarrantyCollaborator::create([
                    'id' => Enum::AGENCY_INSTALL_FLAG_ID,
                    'full_name' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'phone' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'province' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'district' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'ward' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'address' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'sotaikhoan' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'chinhanh' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'cccd' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi tạo CTV flag Đại lý lắp đặt: ' . $e->getMessage());
        }
    }
    
    /**
     * Ghi log khi thay đổi CTV
     */
    private function logCollaboratorChange($oldCollaboratorId, $newCollaboratorId, $orderCode)
    {
        try {
            // Lấy thông tin CTV cũ và mới
            $oldCollaborator = $oldCollaboratorId ? WarrantyCollaborator::find($oldCollaboratorId) : null;
            $newCollaborator = $newCollaboratorId ? WarrantyCollaborator::find($newCollaboratorId) : null;

            if (!$oldCollaborator && !$newCollaborator) {
                return; 
            }

            // Tìm bản ghi lịch sử hiện có
            $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();

            if ($existingHistory) {
                // Kiểm tra xem dữ liệu hiện tại có phải là CTV thật không (không phải flag "Đại lý lắp đặt")
                $isRealCtv = $existingHistory->new_collaborator_id && 
                            !Enum::isAgencyInstallFlag($existingHistory->new_collaborator_id) && 
                            $existingHistory->new_full_name != Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                
                if ($isRealCtv) {
                    // Nếu đang có CTV thật, đẩy dữ liệu CTV vào old_*
                    $existingHistory->old_collaborator_id = $existingHistory->new_collaborator_id;
                    $existingHistory->old_full_name = $existingHistory->new_full_name;
                    $existingHistory->old_phone = $existingHistory->new_phone;
                    $existingHistory->old_province = $existingHistory->new_province;
                    $existingHistory->old_province_id = $existingHistory->new_province_id;
                    $existingHistory->old_district = $existingHistory->new_district;
                    $existingHistory->old_district_id = $existingHistory->new_district_id;
                    $existingHistory->old_ward = $existingHistory->new_ward;
                    $existingHistory->old_ward_id = $existingHistory->new_ward_id;
                    $existingHistory->old_address = $existingHistory->new_address;
                    $existingHistory->old_sotaikhoan = $existingHistory->new_sotaikhoan;
                    $existingHistory->old_chinhanh = $existingHistory->new_chinhanh;
                    $existingHistory->old_cccd = $existingHistory->new_cccd;
                    $existingHistory->old_ngaycap = $existingHistory->new_ngaycap;
                } else {
                    // Nếu đã là "Đại lý lắp đặt" rồi, không cần đẩy dữ liệu vào old_*
                    // Chỉ cần giữ nguyên dữ liệu cũ trong old_*
                }

                // Cập nhật new_collaborator_id
                $existingHistory->new_collaborator_id = $newCollaboratorId;

                // Lưu dữ liệu CTV mới vào new_*
                if ($newCollaborator) {
                    $existingHistory->new_full_name = $newCollaborator->full_name;
                    $existingHistory->new_phone = $newCollaborator->phone;
                    $existingHistory->new_province = $newCollaborator->province;
                    $existingHistory->new_province_id = $newCollaborator->province_id;
                    $existingHistory->new_district = $newCollaborator->district;
                    $existingHistory->new_district_id = $newCollaborator->district_id;
                    $existingHistory->new_ward = $newCollaborator->ward;
                    $existingHistory->new_ward_id = $newCollaborator->ward_id;
                    $existingHistory->new_address = $newCollaborator->address;
                    $existingHistory->new_sotaikhoan = $newCollaborator->sotaikhoan;
                    $existingHistory->new_chinhanh = $newCollaborator->chinhanh;
                    $existingHistory->new_cccd = $newCollaborator->cccd;
                    $existingHistory->new_ngaycap = $newCollaborator->ngaycap;
                } else {
                    // Nếu không có CTV mới (chuyển về đại lý) - chỉ set flag
                    $existingHistory->new_collaborator_id = Enum::AGENCY_INSTALL_FLAG_ID;
                    $existingHistory->new_full_name = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_phone = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_province = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_province_id = null;
                    $existingHistory->new_district = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_district_id = null;
                    $existingHistory->new_ward = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_ward_id = null;
                    $existingHistory->new_address = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_sotaikhoan = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_chinhanh = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_cccd = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                    $existingHistory->new_ngaycap = null;
                }

                $existingHistory->action_type = 'update';
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                
                $oldName = $oldCollaborator ? $oldCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $newName = $newCollaborator ? $newCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $existingHistory->comments = "Thay đổi CTV: '{$oldName}' → '{$newName}'";

                $existingHistory->save();
            } else {
                // TẠO MỚI: Tạo bản ghi lịch sử mới
                $historyData = [
                    'old_collaborator_id' => $oldCollaboratorId,
                    'new_collaborator_id' => $newCollaboratorId,
                    'action_type' => 'update',
                    'edited_by' => session('user', 'system'),
                    'edited_at' => now(),
                    'order_code' => $orderCode
                ];

                // Lưu thông tin CTV cũ vào old_*
                if ($oldCollaborator) {
                    $historyData['old_full_name'] = $oldCollaborator->full_name;
                    $historyData['old_phone'] = $oldCollaborator->phone;
                    $historyData['old_province'] = $oldCollaborator->province;
                    $historyData['old_province_id'] = $oldCollaborator->province_id;
                    $historyData['old_district'] = $oldCollaborator->district;
                    $historyData['old_district_id'] = $oldCollaborator->district_id;
                    $historyData['old_ward'] = $oldCollaborator->ward;
                    $historyData['old_ward_id'] = $oldCollaborator->ward_id;
                    $historyData['old_address'] = $oldCollaborator->address;
                    $historyData['old_sotaikhoan'] = $oldCollaborator->sotaikhoan;
                    $historyData['old_chinhanh'] = $oldCollaborator->chinhanh;
                    $historyData['old_cccd'] = $oldCollaborator->cccd;
                    $historyData['old_ngaycap'] = $oldCollaborator->ngaycap;
                }

                // Lưu thông tin CTV mới vào new_*
                if ($newCollaborator) {
                    $historyData['new_full_name'] = $newCollaborator->full_name;
                    $historyData['new_phone'] = $newCollaborator->phone;
                    $historyData['new_province'] = $newCollaborator->province;
                    $historyData['new_province_id'] = $newCollaborator->province_id;
                    $historyData['new_district'] = $newCollaborator->district;
                    $historyData['new_district_id'] = $newCollaborator->district_id;
                    $historyData['new_ward'] = $newCollaborator->ward;
                    $historyData['new_ward_id'] = $newCollaborator->ward_id;
                    $historyData['new_address'] = $newCollaborator->address;
                    $historyData['new_sotaikhoan'] = $newCollaborator->sotaikhoan;
                    $historyData['new_chinhanh'] = $newCollaborator->chinhanh;
                    $historyData['new_cccd'] = $newCollaborator->cccd;
                    $historyData['new_ngaycap'] = $newCollaborator->ngaycap;
                }

                $oldName = $oldCollaborator ? $oldCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $newName = $newCollaborator ? $newCollaborator->full_name : Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
                $historyData['comments'] = "Thay đổi CTV: '{$oldName}' → '{$newName}'";

                EditCtvHistory::create($historyData);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi ghi log thay đổi CTV: ' . $e->getMessage());
        }
    }

    /**
     * Ghi log thay đổi trạng thái đơn hàng
     */
    private function logStatusChange($orderCode, $oldStatus, $newStatus, $action)
    {
        try {
            Log::info('Bắt đầu ghi log thay đổi trạng thái', [
                'orderCode' => $orderCode,
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
                'action' => $action
            ]);
            
            // Mapping trạng thái số sang text
            $statusMapping = [
                0 => 'Chưa điều phối',
                1 => 'Đã điều phối', 
                2 => 'Đã hoàn thành',
                3 => 'Đã thanh toán'
            ];
            
            $oldStatusText = $statusMapping[$oldStatus] ?? 'Không xác định';
            $newStatusText = $statusMapping[$newStatus] ?? 'Không xác định';
            
            // Tìm bản ghi hiện có để cập nhật
            $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();
            
            if ($existingHistory) {
                // Cập nhật bản ghi hiện có
                $existingHistory->action_type = $action;
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                $existingHistory->comments = "Thay đổi trạng thái: {$oldStatusText} → {$newStatusText}";
                
                $existingHistory->save();
                Log::info('Đã cập nhật bản ghi lịch sử hiện có', ['id' => $existingHistory->id]);
            } else {
                // Tạo bản ghi mới
                $newHistory = EditCtvHistory::create([
                    'order_code' => $orderCode,
                    'action_type' => $action,
                    'edited_by' => session('user', 'system'),
                    'edited_at' => now(),
                    'comments' => "Thay đổi trạng thái: {$oldStatusText} → {$newStatusText}"
                ]);
                Log::info('Đã tạo bản ghi lịch sử mới', ['id' => $newHistory->id]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi ghi log thay đổi trạng thái: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Chuẩn hóa và validate trường product
     */
    private function normalizeProduct($product)
    {
        $product = trim($product ?? '');
        if (empty($product)) {
            return 'Không xác định';
        }
        
        // Đảm bảo encoding UTF-8 đúng
        $product = mb_convert_encoding($product, 'UTF-8', 'auto');
        
        // Chỉ loại bỏ ký tự điều khiển, giữ lại ký tự tiếng Việt
        $product = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $product);
        
        return $product;
    }
    public function Index(Request $request)
    {
        $tungay = request('tungay');
        // Lọc theo view: 1 = KUCHEN, 3 = HUROM (theo session brand)
        $view = session('brand') === 'hurom' ? 3 : 1;
        
        $lstOrder = OrderProduct::with('order')
            ->join('products as p', function($join){
                $join->on(DB::raw("order_products.product_name COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('p.view', $view)
            ->select('order_products.*')
            ->where('order_products.install', 1)
            ->whereHas('order', function ($q) {
                $q->where('order_code2', 'not like', 'KU%')
                  ->where(function ($sub) {
                    // Chỉ hiển thị các đơn hàng chưa điều phối
                    $sub->whereNull('status_install')
                        ->orWhere('status_install', 0);
                  });
                // Chỉ lấy đơn thực sự chưa có CTV gán
                $q->whereNull('collaborator_id');
            })
            ->when($madon = request('madon'), function ($q) use ($madon) {
                $q->whereHas('order', function ($sub) use ($madon) {
                    $sub->where('order_code2', 'like', "%$madon%");
                });
            })
            ->when($sanpham = request('sanpham'), function ($q) use ($sanpham) {
                $q->where('product_name', 'like', "%$sanpham%");
            })
            ->when($tungay && !empty($tungay), function ($q) use ($tungay) {
                $q->whereHas('order', function ($sub) use ($tungay) {
                    $sub->whereDate('created_at', '>=', $tungay);
                });
            })
            ->when($denngay = request('denngay'), function ($q) use ($denngay) {
                if (!empty($denngay)) {
                    $q->whereHas('order', function ($sub) use ($denngay) {
                        $sub->whereDate('created_at', '<=', $denngay);
                    });
                }
            })
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
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
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                $q->whereHas('order', function ($sub) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $sub->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                    } elseif ($phanloai === 'agency') {
                        $sub->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                    }
                });
            })
            ->when($customer_name = request('customer_name'), function ($q) use ($customer_name) {
                $q->whereHas('order', function ($sub) use ($customer_name) {
                    $sub->where('customer_name', 'like', "%$customer_name%");
                });
            })
            ->when($customer_phone = request('customer_phone'), function ($q) use ($customer_phone) {
                $q->whereHas('order', function ($sub) use ($customer_phone) {
                    $sub->where('customer_phone', 'like', "%$customer_phone%");
                });
            })
            ->when($agency_phone = request('agency_phone'), function ($q) use ($agency_phone) {
                $q->whereHas('order', function ($sub) use ($agency_phone) {
                    $sub->where('agency_phone', 'like', "%$agency_phone%");
                });
            })
            ->when($agency_name = request('agency_name'), function ($q) use ($agency_name) {
                $q->whereHas('order', function ($sub) use ($agency_name) {
                    $sub->where('agency_name', 'like', "%$agency_name%");
                });
            })
            ->when($include_old_data = request('include_old_data'), function ($q) {
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
        
        $lstOrderLe = OrderProduct::with('order')
            ->join('products as p', function($join){
                $join->on(DB::raw("order_products.product_name COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('p.view', $view)
            ->select('order_products.*')
            ->where('order_products.install', 1)
            ->whereHas('order', function ($q) {
                $q->where('order_code2', 'like', 'KU%')
                  ->where(function ($sub) {
                    // Chỉ hiển thị các đơn hàng chưa điều phối
                    $sub->whereNull('status_install')
                        ->orWhere('status_install', 0);
                  })
                  // Loại các đơn đã có bản ghi trong installation_orders (tránh trùng tab)
                  ->whereNotExists(function($sub){
                      $sub->select(DB::raw(1))
                          ->from('installation_orders as io')
                          ->whereRaw('io.order_code COLLATE utf8mb4_unicode_ci = orders.order_code2 COLLATE utf8mb4_unicode_ci');
                  });
                // Chỉ lấy đơn thực sự chưa có CTV gán
                $q->whereNull('collaborator_id');
            })
            ->when($madon = request('madon'), function ($q) use ($madon) {
                $q->whereHas('order', function ($sub) use ($madon) {
                    $sub->where('order_code2', 'like', "%$madon%");
                });
            })
            ->when($sanpham = request('sanpham'), function ($q) use ($sanpham) {
                $q->where('product_name', 'like', "%$sanpham%");
            })
            ->when($tungay && !empty($tungay), function ($q) use ($tungay) {
                $q->whereHas('order', function ($sub) use ($tungay) {
                    $sub->whereDate('created_at', '>=', $tungay);
                });
            })
            ->when($denngay = request('denngay'), function ($q) use ($denngay) {
                if (!empty($denngay)) {
                    $q->whereHas('order', function ($sub) use ($denngay) {
                        $sub->whereDate('created_at', '<=', $denngay);
                    });
                }
            })
            ->when($trangthai = request('trangthai'), function ($q) use ($trangthai) {
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
            ->when($phanloai = request('phanloai'), function ($q) use ($phanloai) {
                $q->whereHas('order', function ($sub) use ($phanloai) {
                    if ($phanloai === 'collaborator') {
                        $sub->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                    } elseif ($phanloai === 'agency') {
                        $sub->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                    }
                });
            })
            ->when($customer_name = request('customer_name'), function ($q) use ($customer_name) {
                $q->whereHas('order', function ($sub) use ($customer_name) {
                    $sub->where('customer_name', 'like', "%$customer_name%");
                });
            })
            ->when($customer_phone = request('customer_phone'), function ($q) use ($customer_phone) {
                $q->whereHas('order', function ($sub) use ($customer_phone) {
                    $sub->where('customer_phone', 'like', "%$customer_phone%");
                });
            })
            ->when($agency_phone = request('agency_phone'), function ($q) use ($agency_phone) {
                $q->whereHas('order', function ($sub) use ($agency_phone) {
                    $sub->where('agency_phone', 'like', "%$agency_phone%");
                });
            })
            ->when($agency_name = request('agency_name'), function ($q) use ($agency_name) {
                $q->whereHas('order', function ($sub) use ($agency_name) {
                    $sub->where('agency_name', 'like', "%$agency_name%");
                });
            })
            ->when($include_old_data = request('include_old_data'), function ($q) {
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');


        // CA BẢO HÀNH: hiển thị đầy đủ các ca thuộc nguồn bảo hành tại đại lý (không khóa cứng trạng thái/CTV)
        $lstWarranty = WarrantyRequest::where('type', 'agent_home')
            ->where('view', $view)
            ->when($madon = request('madon'), fn($q) => $q->where('serial_number', 'like', "%$madon%"))
            ->when($sanpham = request('sanpham'), fn($q) => $q->where('product', 'like', "%$sanpham%"))
            ->when($tungay && !empty($tungay), fn($q) => $q->whereDate('Ngaytao', '>=', $tungay))
            ->when($denngay = request('denngay'), function($q) use ($denngay) {
                if (!empty($denngay)) {
                    $q->whereDate('Ngaytao', '<=', $denngay);
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
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('Ngaytao')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // Đã điều phối status_install = 1 VÀ có CTV thật (không phải null và không phải agency flag)
        $lstDaDieuPhoi = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->select('installation_orders.*')
            ->where('status_install', 1)
            ->whereNotNull('collaborator_id')
            ->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
            
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
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
            
        // Đã hoàn thành status_install = 2
        $lstInstallOrder = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->select('installation_orders.*')
            ->where('status_install', 2)
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
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
            
        // Đại lý lắp đặt - status_install = 1 VÀ collaborator_id = AGENCY_INSTALL_FLAG_ID
        $lstAgencyInstall = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->select('installation_orders.*')
            ->where('status_install', 1)
            ->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
            
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
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
            
        // Đã thanh toán status_install = 3
        $lstPaidOrder = InstallationOrder::join('products as p', function($join){
                $join->on(DB::raw("installation_orders.product COLLATE utf8mb4_unicode_ci"), '=', DB::raw("p.product_name COLLATE utf8mb4_unicode_ci"));
            })
            ->where('p.view', $view)
            ->select('installation_orders.*')
            ->where('status_install', 3)
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
                    $q->where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID);
                } elseif ($phanloai === 'agency') {
                    $q->where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID);
                }
            })
            ->when($customer_name = request('customer_name'), fn($q) => $q->where('full_name', 'like', "%$customer_name%"))
            ->when($customer_phone = request('customer_phone'), fn($q) => $q->where('phone_number', 'like', "%$customer_phone%"))
            ->when($agency_name = request('agency_name'), fn($q) => $q->where('agency_name', 'like', "%$agency_name%"))
            ->when($agency_phone = request('agency_phone'), fn($q) => $q->where('agency_phone', 'like', "%$agency_phone%"))
            ->orderByDesc('id');
            
        $counts = [
            'donhang'        => (clone $lstOrder)->count(),
            'dieuphoidonhangle' => (clone $lstOrderLe)->count(),
            'dieuphoibaohanh' => (clone $lstWarranty)->count(),
            'dadieuphoi'      => (clone $lstDaDieuPhoi)->count(),
            'dailylapdat'     => (clone $lstAgencyInstall)->count(),
            'dahoanthanh'     => (clone $lstInstallOrder)->count(),
            'dathanhtoan'     => (clone $lstPaidOrder)->count(),
        ];

        $tab = $request->get('tab', 'donhang');
        $tabQuery = match ($tab) {
            'donhang'        => $lstOrder,
            'dieuphoidonhangle' => $lstOrderLe,
            'dieuphoibaohanh' => $lstWarranty,
            'dadieuphoi'      => $lstDaDieuPhoi,
            'dailylapdat'     => $lstAgencyInstall,
            'dahoanthanh'     => $lstInstallOrder,
            'dathanhtoan'     => $lstPaidOrder,
            default           => $lstOrder,
        };

        // Fix ordering - use appropriate date column based on the tab
        if ($tab === 'dieuphoibaohanh') {
            $data = $tabQuery->orderByDesc('Ngaytao')->orderByDesc('id')->paginate(50)->withQueryString();
        } elseif (in_array($tab, ['donhang', 'dieuphoidonhangle'])) {
            $data = $tabQuery->orderByDesc('orders.created_at')->orderByDesc('order_products.id')->paginate(50)->withQueryString();
        } else {
            $data = $tabQuery->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->withQueryString();
        }
        
        // Debug log để kiểm tra dữ liệu
        Log::info("Total records found: " . $data->total());
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
            
            // Lưu collaborator_id cũ và trạng thái cũ để so sánh (TRƯỚC khi thay đổi)
            $oldCollaboratorId = $model->collaborator_id;
            $oldStatus = $model->status_install;
            
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

            // Ghi log thay đổi CTV nếu có thay đổi
            $orderCode = $model->order_code2 ?? $model->serial_number ?? $model->order_code;
            if (!empty($orderCode) && $oldCollaboratorId != $request->ctv_id) {
                $this->logCollaboratorChange($oldCollaboratorId, $request->ctv_id, $orderCode);
            }
            
            // Ghi log thay đổi trạng thái nếu có thay đổi
            if (!empty($orderCode) && isset($oldStatus) && $oldStatus != $model->status_install) {
                Log::info('Ghi log thay đổi trạng thái', [
                    'orderCode' => $orderCode,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $model->status_install,
                    'action' => $action
                ]);
                $this->logStatusChange($orderCode, $oldStatus, $model->status_install, $action);
            }

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
                            'created_at'       => $model->created_at ?? $model->Ngaytao ?? null,
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
            ->orderBy('full_name')
            ->get();

        $html = view('collaboratorinstall.tablecollaborator', compact('lstCollaborator'))->render();
        return response()->json(['html' => $html]);
    }
    
    public function ReportCollaboratorInstall(Request $request)
    {
        $tungay  = $request->query('start_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $denngay = $request->query('end_date')   ?? Carbon::now()->endOfMonth()->toDateString();

        $dataCollaborator = InstallationOrder::where('collaborator_id', '!=', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)->get();

        $dataAgency = InstallationOrder::where('collaborator_id', Enum::AGENCY_INSTALL_FLAG_ID)
            ->where('status_install', 2)->get();

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

        // Tăng thời gian thực thi và memory cho file lớn
        ini_set('memory_limit', '4096M');      // 4GB memory
        ini_set('max_execution_time', '3600');  // 60 phút
        set_time_limit(3600);                   // 60 phút
        ini_set('default_socket_timeout', '300'); // 5 phút cho socket timeout

        try {
            $file = $request->file('excelFile');
            
            // Tối ưu hóa việc đọc Excel - cải thiện để xử lý ngày tháng
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false); // Đọc cả formatting để xử lý ngày tháng đúng
            $reader->setReadEmptyCells(false); // Bỏ qua ô trống
            $reader->setReadFilter(new \PhpOffice\PhpSpreadsheet\Reader\DefaultReadFilter()); // Đọc tất cả dữ liệu
            
            // Cấu hình để xử lý ngày tháng đúng
            \PhpOffice\PhpSpreadsheet\Shared\Date::setExcelCalendar(\PhpOffice\PhpSpreadsheet\Shared\Date::CALENDAR_WINDOWS_1900);
            
            $spreadsheet = $reader->load($file->getRealPath());
            $sheetCount = $spreadsheet->getSheetCount();

            $stats = [
                'imported' => 0,
                'collaborators_created' => 0,
                'agencies_created' => 0,
                'products_created' => 0,
                'order_products_created' => 0,
                'orders_created' => 0,
                'orders_updated' => 0,
                'installation_orders_created' => 0,
                'installation_orders_updated' => 0,
                'warranty_requests_created' => 0,
                'warranty_requests_updated' => 0,
                'sheets_processed' => 0,
                'errors' => []
            ];

            // Cache để tối ưu performance - sử dụng array để tăng tốc độ lookup
            $collaboratorCache = [];
            $agencyCache = [];
            $productCache = [];
            
            // Pre-load existing data để giảm database queries
            $existingCollaborators = WarrantyCollaborator::pluck('id', 'phone')->toArray();
            $existingAgencies = Agency::pluck('id', 'phone')->toArray();
            $existingProducts = OrderProduct::pluck('id', 'product_name')->toArray();

            // Hàm chuẩn hóa số điện thoại - tối ưu hóa
            $sanitizePhone = function ($value) {
                if (empty($value)) return '';
                $digits = preg_replace('/\D+/', '', $value);
                return mb_strlen($digits) > 11 ? mb_substr($digits, -11) : $digits;
            };

            // Hàm chuẩn hóa ngày tháng - cải thiện để xử lý nhiều format
            $parseDate = function ($dateRaw) {
                if (empty($dateRaw)) return null;
                
                $dateRaw = trim($dateRaw);
                
                // Debug log để theo dõi
                Log::info('Parsing date', ['raw_value' => $dateRaw, 'type' => gettype($dateRaw)]);
                
                // 1. Kiểm tra Excel date serial number (số nguyên hoặc số thập phân)
                if (is_numeric($dateRaw)) {
                    try {
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateRaw);
                        $result = $excelDate->format('Y-m-d H:i:s');
                        Log::info('Excel serial date parsed', ['raw' => $dateRaw, 'result' => $result]);
                        return $result;
                    } catch (\Exception $e) {
                        Log::warning('Excel serial date parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                    }
                }
                
                // 2. Xử lý đặc biệt cho trường hợp Excel có thể lưu m/d/Y nhưng cần hiểu là d-m-Y
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateRaw, $matches)) {
                    $first = intval($matches[1]);
                    $second = intval($matches[2]);
                    $year = $matches[3];
                    
                    // LUÔN thử d-m-Y trước (ngày-tháng-năm) - đây là format Việt Nam
                    try {
                        $date = Carbon::createFromFormat('d-m-Y', $first . '-' . $second . '-' . $year);
                        if ($date->isValid()) {
                            $result = $date->format('Y-m-d H:i:s');
                            Log::info('Force d-m-Y parse success', ['raw' => $dateRaw, 'result' => $result, 'interpreted_as' => "day=$first, month=$second, year=$year"]);
                            return $result;
                        }
                    } catch (\Exception $e) {
                        Log::warning('d-m-Y parse failed, trying m-d-Y', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                        // Nếu d-m-Y không hợp lệ, thử m-d-Y
                        try {
                            $date = Carbon::createFromFormat('m-d-Y', $first . '-' . $second . '-' . $year);
                            if ($date->isValid()) {
                                $result = $date->format('Y-m-d H:i:s');
                                Log::info('Fallback m-d-Y parse success', ['raw' => $dateRaw, 'result' => $result, 'interpreted_as' => "month=$first, day=$second, year=$year"]);
                                return $result;
                            }
                        } catch (\Exception $e2) {
                            Log::warning('Both d-m-Y and m-d-Y failed', ['raw' => $dateRaw, 'error' => $e2->getMessage()]);
                            // Tiếp tục thử các format khác
                        }
                    }
                }

                // 3. Thử parse với Carbon (tự động detect format)
                try {
                    $date = Carbon::parse($dateRaw);
                    if ($date->isValid()) {
                        $result = $date->format('Y-m-d H:i:s');
                        Log::info('Carbon parse success', ['raw' => $dateRaw, 'result' => $result]);
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('Carbon parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                }
                
                // 4. Thử các format phổ biến của Việt Nam - Ưu tiên d-m-y
                $commonFormats = [
                    'd-m-Y',           // 25-12-2024 (Việt Nam - ưu tiên cao nhất)
                    'd/m/Y',           // 25/12/2024 (Việt Nam)
                    'd.m.Y',           // 25.12.2024 (Việt Nam)
                    'd-m-y',           // 25-12-24 (Việt Nam - năm 2 chữ số)
                    'd/m/y',           // 25/12/24 (Việt Nam - năm 2 chữ số)
                    'Y-m-d',           // 2024-12-25 (ISO)
                    'Y/m/d',           // 2024/12/25 (ISO)
                    'd/m/Y H:i:s',     // 25/12/2024 10:30:00
                    'd-m-Y H:i:s',     // 25-12-2024 10:30:00
                    'Y-m-d H:i:s',     // 2024-12-25 10:30:00
                    'd/m/Y H:i',       // 25/12/2024 10:30
                    'd-m-Y H:i',       // 25-12-2024 10:30
                    'Y-m-d H:i',       // 2024-12-25 10:30
                ];
                
                foreach ($commonFormats as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, $dateRaw);
                        if ($date->isValid()) {
                            $result = $date->format('Y-m-d H:i:s');
                            Log::info('Format parse success', ['raw' => $dateRaw, 'format' => $format, 'result' => $result]);
                            return $result;
                        }
                    } catch (\Exception $e) {
                        // Tiếp tục thử format tiếp theo
                    }
                }
                
                // 5. Thử parse với strtotime (fallback)
                try {
                    $timestamp = strtotime($dateRaw);
                    if ($timestamp !== false) {
                        $result = date('Y-m-d H:i:s', $timestamp);
                        Log::info('strtotime parse success', ['raw' => $dateRaw, 'result' => $result]);
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('strtotime parse failed', ['raw' => $dateRaw, 'error' => $e->getMessage()]);
                }
                
                Log::error('All date parsing methods failed', ['raw' => $dateRaw]);
                return null;
            };

            // Hàm kiểm tra ô có bị merge hay không
            $isMergedCell = function ($sheet, $cellCoordinate) {
                try {
                    $mergedRanges = $sheet->getMergeCells();
                    foreach ($mergedRanges as $range) {
                        if ($sheet->getCell($cellCoordinate)->isInRange($range)) {
                            return true;
                        }
                    }
                    return false;
                } catch (\Exception $e) {
                    return false;
                }
            };

            // Hàm lấy giá trị từ ô, xử lý merged cells và ngày tháng - tối ưu cho việc đọc ít dòng
            $getCellValue = function ($sheet, $cellCoordinate) use ($isMergedCell) {
                try {
                    // Chỉ đọc cell khi cần thiết, không load toàn bộ sheet
                    $cell = $sheet->getCell($cellCoordinate, false); // false = không tính toán lại
                    $value = $cell->getCalculatedValue();
                    
                    // Nếu ô bị merge và giá trị trống, thử lấy từ ô đầu tiên của range
                    if (empty($value) && $isMergedCell($sheet, $cellCoordinate)) {
                        $mergedRanges = $sheet->getMergeCells();
                        foreach ($mergedRanges as $range) {
                            if ($sheet->getCell($cellCoordinate)->isInRange($range)) {
                                $rangeArray = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($range);
                                $startRow = $rangeArray[0][1];
                                $startCol = $rangeArray[0][0];
                                $startCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol) . $startRow;
                                $value = $sheet->getCell($startCell, false)->getCalculatedValue();
                                break;
                            }
                        }
                    }
                    
                    // Xử lý đặc biệt cho ngày tháng
                    if ($value instanceof \DateTime) {
                        // Nếu là DateTime object, chuyển về string
                        $value = $value->format('Y-m-d H:i:s');
                    } elseif (is_numeric($value) && $cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                        // Kiểm tra xem có phải là Excel date serial number không
                        try {
                            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                            $value = $excelDate->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            // Nếu không phải ngày, giữ nguyên giá trị số
                        }
                    }
                    
                    // Đảm bảo encoding UTF-8 cho tất cả giá trị string
                    if (is_string($value)) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                    
                    return $value;
                } catch (\Exception $e) {
                    Log::warning('Error getting cell value', [
                        'cell' => $cellCoordinate,
                        'error' => $e->getMessage()
                    ]);
                    return '';
                }
            };

            // Hàm chuẩn hóa trạng thái - tối ưu hóa và xử lý lộn xộn
            $parseStatus = function ($statusRaw) {
                // Nếu cột L (trạng thái) rỗng → trạng thái chưa điều phối
                if (empty($statusRaw)) return 0;
                
                // Loại bỏ khoảng trắng thừa và chuyển về chữ thường
                $statusLower = mb_strtolower(trim($statusRaw));
                
                // Loại bỏ các ký tự đặc biệt và số thừa
                $statusClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $statusLower);
                $statusClean = preg_replace('/\s+/', ' ', $statusClean);
                $statusClean = trim($statusClean);
                
                // Xử lý đặc biệt cho "Đại lý tự lắp" - trả về trạng thái đã điều phối (1)
                $agencySelfInstallStatuses = [
                    'đại lý tự lắp', 'đl đl tự lắp', 'đại lý lắp đặt', 'đl lắp đặt',
                    'agency self install', 'dealer install', 'đại lý tự làm'
                ];
                
                foreach ($agencySelfInstallStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 1;
                }
                
                // Trạng thái đã thanh toán (3)
                $paidStatuses = [
                    'đã thanh toán', 'thanh toán', 'đã trả', 'trả tiền', 'paid', 'payment',
                    'đã chi', 'chi trả', 'hoàn tất thanh toán', 'thanh toán xong'
                ];
                
                // Trạng thái hoàn thành (2) 
                $completedStatuses = [
                    'đã hoàn thành', 'hoàn thành', 'done', 'x', '1', 'yes', 'ok', 'okay',
                    'xong', 'hoàn tất', 'kết thúc', 'finish', 'completed', 'success',
                    'đã xong', 'đã làm xong', 'đã lắp xong', 'lắp xong'
                ];
                
                // Trạng thái đang xử lý (Đã điều phối) (1)
                $processingStatuses = [
                    'đang xử lý', 'đang làm', 'đang theo dõi', 'đang thực hiện', 'đang lắp',
                    'processing', 'in progress', 'đang tiến hành', 'đang thực hiện',
                    'đang lắp đặt', 'lắp đặt', 'đang thi công', 'thi công', 'CTV bận', 'gọi thuê bao', 'Báo bận',
                    'Chưa lắp', 'chưa có mạch'
                ];
                
                // Trạng thái chưa điều phối (0)
                $notAssignedStatuses = [
                    'chưa điều phối', 'chưa giao', 'chưa làm', 'pending', 'waiting',
                    'chờ', 'chưa xử lý', 'chưa thực hiện', '0', 'no', 'false'
                ];
                
                // Kiểm tra từng nhóm trạng thái
                foreach ($paidStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 3;
                }
                
                foreach ($completedStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 2;
                }
                
                foreach ($processingStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 1;
                }
                
                foreach ($notAssignedStatuses as $status) {
                    if (strpos($statusClean, $status) !== false) return 0;
                }
                
                // Nếu không khớp với bất kỳ pattern nào, mặc định là đang xử lý (1)
                return 1;
            };

            // Xử lý tất cả sheet
            $startSheet = 0; // Bắt đầu từ sheet đầu tiên
            $endSheet = $sheetCount; // Đến sheet cuối cùng

            for ($s = $startSheet; $s < $endSheet; $s++) {
                try {
                    $currentSheet = $spreadsheet->getSheet($s);
                    $sheetName = $currentSheet->getTitle();
                    
                    // Chỉ lấy dữ liệu cần thiết, không load toàn bộ sheet
                    $highestRow = $currentSheet->getHighestDataRow();
                    
                    // Bỏ qua sheet trống
                    if ($highestRow <= 1) {
                        continue;
                    }

                    $stats['sheets_processed']++;


                    // Xử lý từng dòng - đọc đến dòng cuối cùng có dữ liệu
                    for ($row = 3; $row <= $highestRow; $row++) { // Bắt đầu từ dòng 3 (bỏ header dòng 1 và 2)
                        try {
                            // Lấy dữ liệu từ các cột cụ thể - sử dụng getCellValue để xử lý merged cells
                            $orderCode = trim($getCellValue($currentSheet, 'Q' . $row) ?? '');
                            
                            // Nếu không có mã đơn hàng, đặt giá trị null
                            if (empty($orderCode)) {
                                $orderCode = null;
                            }

                            // Xử lý ngày - cải thiện để xử lý nhiều trường hợp
                            $dateRaw = trim($getCellValue($currentSheet, 'B' . $row) ?? '');
                            
                            // Debug log để theo dõi
                            Log::info('Processing date for row', [
                                'row' => $row,
                                'raw_date' => $dateRaw,
                                'type' => gettype($dateRaw),
                                'is_empty' => empty($dateRaw)
                            ]);
                            
                            $parsedDate = $parseDate($dateRaw);
                            
                            if ($parsedDate) {
                                $createdAt = $parsedDate;
                                Log::info('Date parsed successfully', [
                                    'row' => $row,
                                    'raw' => $dateRaw,
                                    'parsed' => $parsedDate
                                ]);
                            } else {
                                // Nếu ô ngày trống hoặc không parse được, để null
                                $createdAt = null;
                                Log::info('Processing row with empty date - will set created_at to NULL', [
                                    'row' => $row,
                                    'raw' => $dateRaw,
                                    'order_code' => $orderCode ?? 'N/A'
                                ]);
                            }

                            $agencyName = trim($getCellValue($currentSheet, 'C' . $row) ?? '');
                            $agencyPhoneRaw = trim($getCellValue($currentSheet, 'D' . $row) ?? '');
                            $customerName = trim($getCellValue($currentSheet, 'F' . $row) ?? '');
                            $customerPhoneRaw = trim($getCellValue($currentSheet, 'G' . $row) ?? '');
                            $customerAddress = trim($getCellValue($currentSheet, 'H' . $row) ?? '');
                            $product = $this->normalizeProduct($getCellValue($currentSheet, 'I' . $row) ?? '');
                            $collabName = trim($getCellValue($currentSheet, 'J' . $row) ?? '');
                            $collabPhoneRaw = trim($getCellValue($currentSheet, 'K' . $row) ?? '');
                            $statusRaw = trim($getCellValue($currentSheet, 'L' . $row) ?? '');
                            $collabAccount = trim($getCellValue($currentSheet, 'M' . $row) ?? '');
                            $bank = trim($getCellValue($currentSheet, 'N' . $row) ?? '');
                            $note = trim($getCellValue($currentSheet, 'O' . $row) ?? '');
                            $accessories = trim($getCellValue($currentSheet, 'P' . $row) ?? '');

                            // Chuẩn hóa dữ liệu
                            $agencyPhone = $sanitizePhone($agencyPhoneRaw);
                            $customerPhone = $sanitizePhone($customerPhoneRaw);
                            $collabPhone = $sanitizePhone($collabPhoneRaw);

                            // 1. Xử lý Collaborator (CTV) & Đại lý tự lắp
                            $collaboratorId = null; // Default null

                            // Kiểm tra trước: trạng thái mô tả có phải "Đại lý tự lắp/Đại lý lắp đặt" không
                            $isAgencySelfInstall = false;
                            if (!empty($statusRaw)) {
                                $statusLower = mb_strtolower(trim($statusRaw));
                                $statusClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $statusLower);
                                $statusClean = preg_replace('/\s+/', ' ', $statusClean);
                                $statusClean = trim($statusClean);

                                $agencySelfInstallStatuses = [
                                    'đại lý tự lắp', 'đại lý lắp đặt', 'đl đl tự lắp', 'đl lắp đặt',
                                    'Đại Lý tự lắp', 'đại lý lắp đặt', 'ĐL tự lắp', 'đại lý tự làm'
                                ];

                                foreach ($agencySelfInstallStatuses as $status) {
                                    if (strpos($statusClean, $status) !== false) {
                                        $isAgencySelfInstall = true;
                                        break;
                                    }
                                }
                            }

                            if ($isAgencySelfInstall) {
                                // Đại lý tự lắp → coi như đã điều phối cho đại lý
                                $statusInstall = 1;
                                $collaboratorId = Enum::AGENCY_INSTALL_FLAG_ID;
                            } else {
                                // Không ép trạng thái về 0 khi thiếu CTV; dùng trạng thái từ file Excel
                                $parsedStatus = $parseStatus($statusRaw);
                                $statusInstall = $parsedStatus; // 0/1/2/3 theo mô tả

                                // Nếu có CTV thật, tạo/tìm CTV và gán id; nếu trống thì để NULL
                                $isCtvEmpty = empty($collabName) || empty($collabPhone);
                                if (!$isCtvEmpty) {
                                    if (!isset($collaboratorCache[$collabPhone])) {
                                        if (isset($existingCollaborators[$collabPhone])) {
                                            $collaboratorCache[$collabPhone] = $existingCollaborators[$collabPhone];
                                        } else {
                                            try {
                                                $collaborator = new WarrantyCollaborator();
                                                $collaborator->full_name = $collabName;
                                                $collaborator->phone = $collabPhone;
                                                $collaborator->sotaikhoan = $collabAccount;
                                                $collaborator->chinhanh = $bank;
                                                $collaborator->created_at = now();
                                                $collaborator->save();

                                                $collaboratorCache[$collabPhone] = $collaborator->id;
                                                $stats['collaborators_created']++;
                                            } catch (\Exception $e) {
                                                $stats['errors'][] = "Lỗi tạo collaborator: " . $e->getMessage();
                                                $collaboratorCache[$collabPhone] = null;
                                            }
                                        }
                                    }
                                    $collaboratorId = $collaboratorCache[$collabPhone];
                                } else {
                                    $collaboratorId = null;
                                }
                            }

                            // 2. Xử lý Agency - Tối ưu hóa với pre-loaded data
                            if (!empty($agencyName) && !empty($agencyPhone)) {
                                if (!isset($agencyCache[$agencyPhone])) {
                                    // Kiểm tra trong pre-loaded data trước
                                    if (isset($existingAgencies[$agencyPhone])) {
                                        $agencyCache[$agencyPhone] = $existingAgencies[$agencyPhone];
                                    } else {
                                        try {
                                            // Tạo agency mới
                                            $agency = new Agency();
                                            $agency->name = $agencyName;
                                            $agency->phone = $agencyPhone;
                                            $agency->sotaikhoan = $collabAccount;
                                            $agency->chinhanh = $bank;
                                            $agency->created_ad = now();
                                            $agency->save();
                                            
                                            $agencyCache[$agencyPhone] = $agency->id;
                                            $stats['agencies_created']++;
                                        } catch (\Exception $e) {
                                            $stats['errors'][] = "Lỗi tạo agency: " . $e->getMessage();
                                        }
                                    }
                                }
                            }

                            // 3. Xử lý Product - Tối ưu hóa với pre-loaded data
                            if (!empty($product) && !isset($productCache[$product])) {
                                // Kiểm tra trong pre-loaded data
                                if (!isset($existingProducts[$product])) {
                                    $stats['products_created']++;
                                }
                                $productCache[$product] = true;
                            }

                            // 4. Tạo/Cập nhật Order - Tối ưu hóa
                            $order = null;
                            if ($orderCode) {
                                $order = Order::where('order_code2', $orderCode)->first();
                            }
                            
                            if (!$order) {
                                $order = new Order();
                                $order->order_code2 = $orderCode;
                                // Chỉ set created_at nếu có ngày từ Excel, không tự động thêm now()
                                if ($createdAt) {
                                    $order->created_at = $createdAt;
                                    Log::info('Setting created_at from Excel', ['order_code' => $orderCode, 'created_at' => $createdAt]);
                                } else {
                                    // KHÔNG set created_at, để database tự xử lý (sẽ là NULL)
                                    Log::info('No created_at from Excel, keeping null', ['order_code' => $orderCode, 'dateRaw' => $dateRaw]);
                                }
                                $stats['orders_created']++;
                            } else {
                                $stats['orders_updated']++;
                            }
                            
                            // Cập nhật Order với trạng thái đã được xử lý
                            $order->fill([
                                'customer_name' => $customerName,
                                'customer_phone' => $customerPhone,
                                'customer_address' => $customerAddress,
                                'agency_name' => $agencyName,
                                'agency_phone' => $agencyPhone,
                                'collaborator_id' => $collaboratorId,
                                'status_install' => $statusInstall,
                                'successed_at' => ($statusInstall == 2 && $createdAt) ? $createdAt : null,
                                'payment_method' => 'cash',
                                'status' => 'Đã quét QR',
                                'status_tracking' => 'Giao thành công',
                                'staff' => 'system',
                                'zone' => '',
                                'type' => 'online',
                                'shipping_unit' => 'default',
                                'send_camon' => 0,
                                'send_khbh' => 0,
                                'ip_rate' => '',
                                'note' => $statusRaw, // Lưu trạng thái gốc để hiển thị
                                'note_admin' => '',
                                'check_return' => 0
                            ]);
                            $order->save();
                            
                            // Debug log để kiểm tra giá trị created_at sau khi save
                            Log::info('Order saved', [
                                'order_code' => $orderCode,
                                'created_at_after_save' => $order->created_at,
                                'was_null_before' => !$createdAt
                            ]);

                            // 4.1. Tạo OrderProduct nếu có sản phẩm và order đã được tạo
                            if (!empty($product) && $order) {
                                $orderProduct = OrderProduct::where('order_id', $order->id)
                                    ->where('product_name', $product)
                                    ->first();
                                
                                if (!$orderProduct) {
                                    $orderProduct = new OrderProduct();
                                    $orderProduct->order_id = $order->id;
                                    $orderProduct->product_name = $product;
                                    $orderProduct->sub_address = $customerAddress;
                                    $orderProduct->install = 1; // Đánh dấu cần lắp đặt
                                    $orderProduct->quantity = 1;
                                    $orderProduct->excluding_VAT = 0;
                                    $orderProduct->VAT = '0%';
                                    $orderProduct->VAT_price = 0;
                                    $orderProduct->price = 0;
                                    $orderProduct->price_difference = 0;
                                    $orderProduct->is_promotion = false;
                                    $orderProduct->warranty_scan = 0;
                                    $orderProduct->save();
                                    
                                    $stats['order_products_created']++;
                                }
                            }

                            // 5. Tạo/Cập nhật InstallationOrder - Xử lý tất cả trạng thái
                            if ($statusInstall > 0) {
                                $installationOrder = null;
                                if ($orderCode) {
                                    $installationOrder = InstallationOrder::where('order_code', $orderCode)->first();
                                }
                                
                                if (!$installationOrder) {
                                    $installationOrder = new InstallationOrder();
                                    $stats['installation_orders_created']++;
                                } else {
                                    $stats['installation_orders_updated']++;
                                }
                                
                                $installationOrder->fill([
                                    'order_code' => $orderCode,
                                    'full_name' => $customerName,
                                    'phone_number' => $customerPhone,
                                    'address' => $customerAddress,
                                    'product' => $product,
                                    'collaborator_id' => $collaboratorId,
                                    'status_install' => $statusInstall,
                                    'reviews_install' => $note . ($statusRaw ? ' | Trạng thái gốc: ' . $statusRaw : ''),
                                    'agency_name' => $agencyName,
                                    'agency_phone' => $agencyPhone,
                                    'type' => 'donhang',
                                    'successed_at' => ($statusInstall == 2 && $createdAt) ? $createdAt : null
                                ]);
                                
                                // Chỉ set created_at nếu có ngày từ Excel
                                if ($createdAt) {
                                    $installationOrder->created_at = $createdAt;
                                    Log::info('Setting InstallationOrder created_at from Excel', ['order_code' => $orderCode, 'created_at' => $createdAt]);
                                } else {
                                    Log::info('InstallationOrder created_at will be NULL', ['order_code' => $orderCode]);
                                }
                                $installationOrder->save();
                            }

                            // 6. Tạo/Cập nhật WarrantyRequest - Xử lý tất cả trạng thái
                            if (!empty($product)) {
                                $warrantyRequest = null;
                                if ($orderCode) {
                                    $warrantyRequest = WarrantyRequest::where('serial_number', $orderCode)->first();
                                }
                                
                                if (!$warrantyRequest) {
                                    $warrantyRequest = new WarrantyRequest();
                                    $warrantyRequest->serial_number = $orderCode;
                                    $stats['warranty_requests_created']++;
                                } else {
                                    $stats['warranty_requests_updated']++;
                                }
                                
                                $warrantyEnd = $createdAt ? Carbon::parse($createdAt)->addYear() : null;
                                $warrantyRequest->fill([
                                    'product' => $product,
                                    'full_name' => $customerName,
                                    'phone_number' => $customerPhone,
                                    'address' => $customerAddress,
                                    'collaborator_id' => $collaboratorId,
                                    'agency_name' => $agencyName,
                                    'agency_phone' => $agencyPhone,
                                    'status_install' => $statusInstall,
                                    'Ngaytao' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'type' => 'agent_home',
                                    'branch' => 'default',
                                    'return_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'shipment_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'received_date' => $createdAt, // Giữ nguyên null nếu không có ngày từ Excel
                                    'warranty_end' => $warrantyEnd,
                                    'staff_received' => 'system'
                                ]);
                                
                                // Debug log cho WarrantyRequest
                                if ($createdAt) {
                                    Log::info('Setting WarrantyRequest dates from Excel', ['order_code' => $orderCode, 'created_at' => $createdAt]);
                                } else {
                                    Log::info('WarrantyRequest dates will be NULL', ['order_code' => $orderCode]);
                                }
                                $warrantyRequest->save();
                            }

                            $stats['imported']++;
                            
                            // Giải phóng memory sau mỗi 50 dòng để tối ưu performance
                            if ($row % 50 == 0) {
                                gc_collect_cycles(); // Garbage collection
                            }

                        } catch (\Exception $e) {
                            $stats['errors'][] = "Sheet $sheetName, Dòng $row: " . $e->getMessage();
                        }
                    }

                    // Giải phóng memory sau mỗi sheet
                    $currentSheet->disconnectCells();
                    unset($currentSheet);
                    
                } catch (\Exception $e) {
                    $stats['errors'][] = "Lỗi xử lý sheet $s: " . $e->getMessage();
                }
            }

            // Giải phóng memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Tạo thông báo chi tiết
            $message = "Đồng bộ thành công! Đã xử lý {$stats['imported']} dòng từ {$stats['sheets_processed']} sheet.\n";
            $message .= "Tạo mới: {$stats['orders_created']} đơn hàng, {$stats['installation_orders_created']} lắp đặt, {$stats['warranty_requests_created']} bảo hành, {$stats['collaborators_created']} CTV, {$stats['agencies_created']} đại lý.\n";
            $message .= "Cập nhật: {$stats['orders_updated']} đơn hàng, {$stats['installation_orders_updated']} lắp đặt, {$stats['warranty_requests_updated']} bảo hành.";
            
            if (!empty($stats['errors'])) {
                $message .= "\nLỗi: " . count($stats['errors']) . " lỗi xảy ra.";
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý file: ' . $e->getMessage(),
            ], 500);
        }
    }
}