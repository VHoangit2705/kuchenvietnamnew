<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\District;
use App\Models\KyThuat\Wards;
use Illuminate\Http\Request;
use App\Models\Kho\Agency;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\KyThuat\EditCtvHistory;
use App\Http\Controllers\SaveLogController;
use App\Enum;

class CollaboratorController extends Controller
{
    static $pageSize = 50;
    protected $saveLogController;

    public function __construct()
    {
        $this->middleware('permission:Xem danh sách CTV')->only(['Index']);
        $this->middleware('permission:Cập nhật CTV')->only(['CreateCollaborator', 'DeleteCollaborator']);
        $this->saveLogController = new SaveLogController();
    }

    /**
     * So sánh và ghi lại các thay đổi (sử dụng updateOrCreate)
     */
    private function logChangesNew($collaborator, $newData, $actionType = 'update', $orderCode = null)
    {
        return $this->saveLogController->logChangesNew($collaborator, $newData, $actionType, $orderCode);
    }

    /**
     * Lưu thông tin đại lý vào các cột old_* và new_*
     */
    private function saveAgencyDetails($history, $oldAgencyId = null, $newAgency)
    {
        return $this->saveLogController->saveAgencyDetails($history, $oldAgencyId, $newAgency);
    }
    
    public function Index(Request $request)
    {
        $query = WarrantyCollaborator::query();

        if ($request->filled('province')) {
            $query->where('province_id', $request->province);
        }

        if ($request->filled('district')) {
            $query->where('district_id', $request->district);
        }

        if ($request->filled('ward')) {
            $query->where('ward_id', $request->ward);
        }

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . $request->full_name . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        $data = $query->orderBy('id', 'desc')->paginate(self::$pageSize);
        $lstProvince = Province::all();

        if ($request->ajax()) {
            return view('collaborator.tablecontent', compact('data'))->render(); // không cần json()
        }

        return view('collaborator.index', compact('data', 'lstProvince'));
    }

    public function getByID(Request $request)
    {
        $id = $request->id;
        $collaborator = WarrantyCollaborator::find($id);

        if (!$collaborator) {
            return response()->json(['message' => 'Không tìm thấy cộng tác viên']);
        }
        $lstProvince = Province::all();
        $lstDistricts = District::getByProvinceID($collaborator->province_id);
        $lstWards = Wards::getByDistrictID($collaborator->district_id);
        return response()->json([
            'message' => 'Lấy dữ liệu thành công',
            'data' => [
                'collaborator' => $collaborator,
                'provinces' => $lstProvince,
                'districts' => $lstDistricts,
                'wards' => $lstWards,
            ]
        ]);
    }
    
    public function getCollaboratorByID($id)
    {
        $collaborator = WarrantyCollaborator::find($id);
        return response()->json($collaborator);
    }

    public function GetDistrictByProvinveId($province_id)
    {
        $districts = District::getByProvinceID($province_id);
        return response()->json($districts);
    }

    public function GetWardByDistrictId($district_id)
    {
        $wards = Wards::getByDistrictID($district_id);
        return response()->json($wards);
    }

    public function CreateCollaborator(Request $request)
    {
        // Không cho phép update CTV flag "Đại lý lắp đặt" (ID = 1)
        if ($request->id && Enum::isAgencyInstallFlag($request->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật "Đại lý lắp đặt" - Đây là flag hệ thống'
            ], 403);
        }
        
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            // 'date_of_birth' => 'required|date',
            'phone' => 'required|digits_between:9,12',
            'province' => 'required|string',
            'province_id' => 'required|string',
            'district' => 'required',
            'district_id' => 'required',
            'ward_id' => 'required',
            'ward' => 'required|string',
            'address' => 'required|string|max:1024',
            'bank_name' => 'nullable|string|max:255',
            'chinhanh' => 'nullable|string|max:255',
            'sotaikhoan' => 'nullable|string|max:255',
        ]);
        
        // Kiểm tra tên không được là "Đại lý lắp đặt"
        $fullNameLower = mb_strtolower(trim($validated['full_name']));
        $fullNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $fullNameLower);
        $fullNameClean = preg_replace('/\s+/', ' ', $fullNameClean);
        $fullNameClean = trim($fullNameClean);
        
        $flagName = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $flagNameLower = mb_strtolower(trim($flagName));
        $flagNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $flagNameLower);
        $flagNameClean = preg_replace('/\s+/', ' ', $flagNameClean);
        $flagNameClean = trim($flagNameClean);
        
        // Không cho phép tạo CTV với tên trùng hoặc chứa "Đại lý lắp đặt"
        if ($fullNameClean === $flagNameClean || 
            strpos($fullNameClean, 'đại lý lắp đặt') !== false ||
            strpos($fullNameClean, 'đại lý tự lắp') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo CTV với tên "Đại lý lắp đặt" - Vui lòng sử dụng checkbox "Đại lý lắp đặt" trong form điều phối'
            ], 403);
        }
        
        $validated['create_by'] = session('user');
        
        // Đảm bảo bank_name không vượt quá 255 ký tự (sau khi migration)
        if (isset($validated['bank_name']) && strlen($validated['bank_name']) > 255) {
            $validated['bank_name'] = mb_substr($validated['bank_name'], 0, 255);
        }
        
        try {
            if ($request->id) {
                WarrantyCollaborator::where('id', $request->id)->update($validated);
                return response()->json(['success' => true, 'message' => 'Cập nhật thành công']);
            }
            WarrantyCollaborator::create($validated);
            return response()->json(['success' => true, 'message' => 'Thêm mới thành công']);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo/cập nhật CTV: ' . $e->getMessage(), [
                'request_data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function DeleteCollaborator($id)
    {
        // Không cho phép xóa CTV flag "Đại lý lắp đặt"
        if (Enum::isAgencyInstallFlag($id)) {
            return response()->json([
                'success' => false, 
                'message' => 'Không thể xóa "Đại lý lắp đặt" - Đây là flag hệ thống'
            ]);
        }

        $item = WarrantyCollaborator::find($id);
        if ($item) {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Xoá thành công']);
        }
        return response()->json(['success' => false, 'message' => 'Lỗi trong quá trình xoá']);
    }

    public function UpdateCollaborator(Request $request)
    {
        try {
            // Không cho phép update CTV flag "Đại lý lắp đặt" (ID = 1)
            if (Enum::isAgencyInstallFlag($request->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể cập nhật "Đại lý lắp đặt" - Đây là flag hệ thống'
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'sotaikhoan' => 'nullable|string|max:255',
                'nganhang' => 'nullable|string|max:255',
                'chinhanh' => 'nullable|string|max:255',
                'cccd' => 'nullable|string|max:20',
                'ngaycap' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collab = WarrantyCollaborator::find($request->id);
            if (!$collab) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy cộng tác viên'
                ], 404);
            }

            // Chuẩn bị dữ liệu mới để so sánh
            $newData = [
                'sotaikhoan' => $request->sotaikhoan,
                'bank_name' => $request->nganhang,
                'chinhanh' => $request->chinhanh,
                'cccd' => $request->cccd,
                'ngaycap' => $request->ngaycap
            ];

            // Cập nhật CTV trong database
            $collab->sotaikhoan = $request->sotaikhoan;
            $collab->bank_name = $request->nganhang;
            $collab->chinhanh = $request->chinhanh;
            $collab->cccd = $request->cccd;
            $collab->ngaycap = $request->ngaycap;
            $collab->save();

            // Chỉ ghi log khi có order_code (thay đổi từ đơn hàng)
            if ($request->order_code) {
                // Tìm bản ghi hiện có để cập nhật
                $existingHistory = EditCtvHistory::where('order_code', $request->order_code)->first();
                
                if ($existingHistory) {
                    // Cập nhật bản ghi hiện có - cập nhật new_* với dữ liệu CTV mới
                    $existingHistory->new_collaborator_id = $collab->id;
                    $existingHistory->new_full_name = $collab->full_name;
                    $existingHistory->new_phone = $collab->phone;
                    $existingHistory->new_province = $collab->province;
                    $existingHistory->new_province_id = $collab->province_id;
                    $existingHistory->new_district = $collab->district;
                    $existingHistory->new_district_id = $collab->district_id;
                    $existingHistory->new_ward = $collab->ward;
                    $existingHistory->new_ward_id = $collab->ward_id;
                    $existingHistory->new_address = $collab->address;
                    $existingHistory->new_sotaikhoan = $collab->sotaikhoan;
                    $existingHistory->new_chinhanh = $collab->chinhanh;
                    $existingHistory->new_cccd = $collab->cccd;
                    $existingHistory->new_ngaycap = $collab->ngaycap;
                    
                    $existingHistory->action_type = 'update';
                    $existingHistory->edited_by = session('user', 'system');
                    $existingHistory->edited_at = now();
                    $existingHistory->comments = 'Cập nhật thông tin CTV: ' . $collab->full_name;
                    
                    $existingHistory->save();
                } else {
                    // Tạo bản ghi mới nếu chưa có
                    $this->logChangesNew($collab, $newData, 'update', $request->order_code);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thành công',
                'data' => $collab
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function UpdateAgency(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'agency_name' => 'nullable',
                'agency_address' => 'nullable',
                'agency_phone' => 'nullable',
                'agency_bank' => 'nullable',
                'agency_paynumber' => 'nullable',
                'agency_branch' => 'nullable',
                'agency_cccd' => 'nullable',
                'agency_release_date' => 'nullable',
                'order_code' => 'nullable',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Kiểm tra nếu không có agency_phone thì không cập nhật
            if (empty($request->agency_phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số điện thoại đại lý là bắt buộc'
                ], 422);
            }
            
            $agency = Agency::updateOrCreate(
                ['phone' => $request->agency_phone],
                [
                    'name'       => $request->agency_name,
                    'address'    => $request->agency_address,
                    'bank_name_agency' => $request->agency_bank,
                    'sotaikhoan' => $request->agency_paynumber,
                    'chinhanh'   => $request->agency_branch,
                    'cccd'       => $request->agency_cccd,
                    'ngaycap'    => $request->agency_release_date,
                    'create_by'  => session('user'),
                    'created_ad' => now(),
                ]
            );
            
            // Ghi log cho Agency nếu có order_code
            if ($request->order_code) {
                // Tìm bản ghi hiện có trước
                $existingHistory = EditCtvHistory::where('order_code', $request->order_code)->first();
                
                if ($existingHistory) {
                    // Kiểm tra xem có thay đổi thực sự không
                    $hasChanges = false;
                    $changes = [];
                    
                    // So sánh từng trường để kiểm tra thay đổi
                    $agencyFields = [
                        'name' => ['old_agency_name', 'new_agency_name'],
                        'phone' => ['old_agency_phone', 'new_agency_phone'],
                        'address' => ['old_agency_address', 'new_agency_address'],
                        'sotaikhoan' => ['old_agency_paynumber', 'new_agency_paynumber'],
                        'chinhanh' => ['old_agency_branch', 'new_agency_branch'],
                        'cccd' => ['old_agency_cccd', 'new_agency_cccd'],
                        'ngaycap' => ['old_agency_release_date', 'new_agency_release_date']
                    ];
                    
                    foreach ($agencyFields as $field => $columns) {
                        $oldValue = $existingHistory->{$columns[0]};
                        $newValue = $agency->$field;
                        
                        // Chuyển đổi về string để so sánh
                        $oldValue = is_null($oldValue) ? null : (string) $oldValue;
                        $newValue = is_null($newValue) ? null : (string) $newValue;
                        
                        if ($oldValue !== $newValue) {
                            $hasChanges = true;
                            $changes[] = $field;
                        }
                    }
                    
                    // CHỈ CẬP NHẬT KHI CÓ THAY ĐỔI THỰC SỰ
                    if ($hasChanges) {
                        // CẬP NHẬT: Đẩy dữ liệu cũ (new_*) vào old_*, dữ liệu mới vào new_*
                        $existingHistory->old_agency_name = $existingHistory->new_agency_name;
                        $existingHistory->new_agency_name = $agency->name;
                        $existingHistory->old_agency_phone = $existingHistory->new_agency_phone;
                        $existingHistory->new_agency_phone = $agency->phone;
                        $existingHistory->old_agency_address = $existingHistory->new_agency_address;
                        $existingHistory->new_agency_address = $agency->address;
                        $existingHistory->old_agency_paynumber = $existingHistory->new_agency_paynumber;
                        $existingHistory->new_agency_paynumber = $agency->sotaikhoan;
                        $existingHistory->old_agency_branch = $existingHistory->new_agency_branch;
                        $existingHistory->new_agency_branch = $agency->chinhanh;
                        $existingHistory->old_agency_cccd = $existingHistory->new_agency_cccd;
                        $existingHistory->new_agency_cccd = $agency->cccd;
                        $existingHistory->old_agency_release_date = $existingHistory->new_agency_release_date;
                        $existingHistory->new_agency_release_date = $agency->ngaycap;
                        
                        // Cập nhật thông tin chung
                        $existingHistory->action_type = 'update_agency';
                        $existingHistory->edited_by = session('user', 'system');
                        $existingHistory->edited_at = now();
                        $existingHistory->comments = 'Cập nhật thông tin đại lý';
                        
                        $existingHistory->save();
                    } else {
                        // Nếu không có thay đổi, vẫn cập nhật thời gian để ghi nhận việc truy cập
                        $existingHistory->edited_by = session('user', 'system');
                        $existingHistory->edited_at = now();
                        $existingHistory->comments = 'Truy cập thông tin đại lý (không có thay đổi)';
                        $existingHistory->save();
                    }
                    
                } else {
                    // TẠO MỚI: Lưu thông tin đại lý mới
                    $historyData = [
                        'collaborator_id' => null,
                        'old_collaborator_id' => null,
                        'new_collaborator_id' => null,
                        'action_type' => 'update_agency',
                        'edited_by' => session('user', 'system'),
                        'edited_at' => now(),
                        'order_code' => $request->order_code,
                        'comments' => 'Cập nhật thông tin đại lý'
                    ];
                    
                    $history = EditCtvHistory::create($historyData);
                    
                    // Lưu thông tin đại lý mới vào các cột
                    $history = $this->saveAgencyDetails($history, null, $agency);
                    $history->save();
                    
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đại lý thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy lịch sử thay đổi của cộng tác viên
     */
    public function getCollaboratorHistory($id)
    {
        try {
            $collaborator = WarrantyCollaborator::find($id);
            if (!$collaborator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy cộng tác viên'
                ], 404);
            }

            // Tìm lịch sử theo old_collaborator_id hoặc new_collaborator_id
            $history = EditCtvHistory::where(function($query) use ($id) {
                    $query->where('old_collaborator_id', $id)
                          ->orWhere('new_collaborator_id', $id);
                })
                ->orderBy('edited_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return $this->formatHistoryItem($item);
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'collaborator' => $collaborator,
                    'history' => $history
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy lịch sử thay đổi theo order_code
     */
    public function getOrderHistory($orderCode)
    {
        try {
            // Tìm lịch sử theo order_code
            $history = EditCtvHistory::where('order_code', $orderCode)
                ->orderBy('edited_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return $this->formatHistoryItem($item);
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'order_code' => $orderCode,
                    'history' => $history
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format một item lịch sử để hiển thị
     */
    private function formatHistoryItem($item)
    {
        $formatted = $item->toArray();
        
        // Tạo danh sách thay đổi từ các cột riêng biệt
        $changes = [];
        $fieldMappings = [
            // Thông tin CTV
            'full_name' => ['old_full_name', 'new_full_name', 'Họ và tên CTV'],
            'phone' => ['old_phone', 'new_phone', 'SĐT CTV'],
            'province' => ['old_province', 'new_province', 'Tỉnh/Thành phố CTV'],
            'district' => ['old_district', 'new_district', 'Quận/Huyện CTV'],
            'ward' => ['old_ward', 'new_ward', 'Phường/Xã CTV'],
            'address' => ['old_address', 'new_address', 'Địa chỉ CTV'],
            'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan', 'Số tài khoản CTV'],
            'chinhanh' => ['old_chinhanh', 'new_chinhanh', 'Chi nhánh CTV'],
            'cccd' => ['old_cccd', 'new_cccd', 'CCCD CTV'],
            'ngaycap' => ['old_ngaycap', 'new_ngaycap', 'Ngày cấp CTV'],
            // Thông tin đại lý
            'agency_name' => ['old_agency_name', 'new_agency_name', 'Tên đại lý'],
            'agency_phone' => ['old_agency_phone', 'new_agency_phone', 'SĐT đại lý'],
            'agency_address' => ['old_agency_address', 'new_agency_address', 'Địa chỉ đại lý'],
            'agency_paynumber' => ['old_agency_paynumber', 'new_agency_paynumber', 'Số tài khoản đại lý'],
            'agency_branch' => ['old_agency_branch', 'new_agency_branch', 'Chi nhánh đại lý'],
            'agency_cccd' => ['old_agency_cccd', 'new_agency_cccd', 'Căn cước công dân đại lý'],
            'agency_release_date' => ['old_agency_release_date', 'new_agency_release_date', 'Ngày cấp đại lý']
        ];
        
        foreach ($fieldMappings as $field => $mapping) {
            $oldValue = $item->{$mapping[0]};
            $newValue = $item->{$mapping[1]};
            
            // HIỂN THỊ TẤT CẢ THÔNG TIN (không chỉ khi có thay đổi)
            if (!is_null($oldValue) || !is_null($newValue)) {
                $changes[] = [
                    'field' => $field,
                    'field_name' => $mapping[2],
                    'old_value' => $oldValue ?: 'Trống',
                    'new_value' => $newValue ?: 'Trống',
                    'has_changed' => $oldValue !== $newValue,
                    'change_description' => $mapping[2] . ": '" . ($oldValue ?: 'Trống') . "' → '" . ($newValue ?: 'Trống') . "'"
                ];
            }
        }
        
        $formatted['changes_detail'] = $changes;
        $formatted['formatted_edited_at'] = $item->edited_at ? $item->edited_at->format('d/m/Y H:i:s') : '';
        $formatted['action_type_text'] = $this->getActionTypeText($item->action_type);
        
        // Thêm thông tin collaborator_id nếu có
        if ($item->old_collaborator_id || $item->new_collaborator_id) {
            $formatted['collaborator_changes'] = [
                'old_collaborator_id' => $item->old_collaborator_id,
                'new_collaborator_id' => $item->new_collaborator_id,
                'collaborator_changed' => $item->old_collaborator_id !== $item->new_collaborator_id
            ];
        }
        
        return $formatted;
    }

    /**
     * Chuyển đổi action_type thành text 
     */
    private function getActionTypeText($actionType)
    {
        $actionTypes = [
            'create' => 'Tạo mới',
            'update' => 'Cập nhật',
            'delete' => 'Xóa',
            'update_agency' => 'Cập nhật đại lý',
            'clear' => 'Xóa dữ liệu CTV',
            'switch_to_agency' => 'Chuyển sang Đại lý lắp đặt',
            'switch_to_ctv' => 'Chuyển về CTV'
        ];
        
        return $actionTypes[$actionType] ?? $actionType;
    }

    /**
     * Clear CTV data khi chọn "Đại lý lắp đặt"
     */
    public function ClearCollaborator(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_code' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->saveLogController->clearCollaborator($request->order_code);

            if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển sang "Đại lý lắp đặt" thành công'
            ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi chuyển sang "Đại lý lắp đặt"'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ghi log khi chuyển từ "Đại lý lắp đặt" về CTV
     */
    public function SwitchToCtv(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_code' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->saveLogController->switchToCtv($request->order_code);

            if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển về CTV thành công'
            ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi chuyển về CTV'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

}
