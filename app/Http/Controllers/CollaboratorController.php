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
use App\Models\Kho\InstallationOrder;

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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|digits_between:9,12',
            'province' => 'required|string',
            'province_id' => 'required|string',
            'district' => 'required',
            'district_id' => 'required',
            'ward_id' => 'required',
            'ward' => 'required|string',
            'address' => 'required|string|max:1024',
        ]);

        $fullNameLower = mb_strtolower(trim($validated['full_name']));
        $fullNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $fullNameLower);
        $fullNameClean = preg_replace('/\s+/', ' ', $fullNameClean);
        $fullNameClean = trim($fullNameClean);

        $flagName = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $flagNameLower = mb_strtolower(trim($flagName));
        $flagNameClean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $flagNameLower);
        $flagNameClean = preg_replace('/\s+/', ' ', $flagNameClean);
        $flagNameClean = trim($flagNameClean);

        if (
            $fullNameClean === $flagNameClean ||
            strpos($fullNameClean, 'đại lý lắp đặt') !== false ||
            strpos($fullNameClean, 'đại lý tự lắp') !== false
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo CTV với tên "Đại lý lắp đặt" - Vui lòng sử dụng checkbox "Đại lý lắp đặt" trong form điều phối'
            ], 403);
        }

        $validated['create_by'] = session('user');
        if ($request->id) {
            WarrantyCollaborator::where('id', $request->id)->update($validated);
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công']);
        }
        WarrantyCollaborator::create($validated);
        return response()->json(['success' => true, 'message' => 'Thêm mới thành công']);
    }

    public function DeleteCollaborator($id)
    {
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
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'sotaikhoan' => 'nullable|string|max:255',
                'nganhang' => 'nullable|string|max:255',
                'bank_account' => 'nullable|string|max:255',
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

            $oldCollab = [
                'sotaikhoan' => $collab->sotaikhoan,
                'bank_name' => $collab->bank_name,
                'bank_account' => $collab->bank_account ?? null,
                'chinhanh' => $collab->chinhanh,
                'cccd' => $collab->cccd,
                'ngaycap' => $collab->ngaycap,
            ];

            $newData = [
                'sotaikhoan' => $request->sotaikhoan,
                'bank_name' => $request->nganhang,
                'bank_account' => $request->bank_account ?? ($request->chutaikhoan ?? null),
                'chinhanh' => $request->chinhanh,
                'cccd' => $request->cccd,
                'ngaycap' => $request->ngaycap
            ];

            $collab->sotaikhoan = $request->sotaikhoan;
            $collab->bank_name = $request->nganhang;
            if ($request->filled('bank_account')) {
                $collab->bank_account = $request->bank_account;
            }
            $collab->chinhanh = $request->chinhanh;
            $collab->cccd = $request->cccd;
            $collab->ngaycap = $request->ngaycap;
            $collab->save();

            if ($request->order_code) {
                $orderCodeRaw = (string) $request->order_code;
                $orderCodeBase = preg_replace('/\s*\(\d+\)\s*$/u', '', $orderCodeRaw);
                $orderCodeBase = trim((string) $orderCodeBase);
                $installationOrdersId = InstallationOrder::query()
                    ->where('order_code', $orderCodeRaw)
                    ->orWhere('order_code', $orderCodeBase)
                    ->orWhere('order_code', 'like', '%' . $orderCodeBase . '%')
                    ->orderByDesc('id')
                    ->value('id');

                if ($installationOrdersId) {
                    $this->saveLogController->auditLog(
                        (int) $installationOrdersId,
                        $orderCodeRaw,
                        'collaborator_finance_updated',
                        $oldCollab,
                        [
                            'sotaikhoan' => $collab->sotaikhoan,
                            'bank_name' => $collab->bank_name,
                            'bank_account' => $collab->bank_account ?? null,
                            'chinhanh' => $collab->chinhanh,
                            'cccd' => $collab->cccd,
                            'ngaycap' => $collab->ngaycap,
                        ],
                        'source: CollaboratorController@UpdateCollaborator'
                    );
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

            if (empty($request->agency_phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số điện thoại đại lý là bắt buộc'
                ], 422);
            }

            $existingAgency = Agency::where('phone', $request->agency_phone)->first();
            $oldAgency = $existingAgency ? [
                'agency_name' => $existingAgency->name,
                'agency_phone' => $existingAgency->phone,
                'agency_address' => $existingAgency->address,
                'agency_bank' => $existingAgency->bank_name_agency,
                'agency_paynumber' => $existingAgency->sotaikhoan,
                'agency_branch' => $existingAgency->chinhanh,
                'agency_cccd' => $existingAgency->cccd,
                'agency_release_date' => $existingAgency->ngaycap,
            ] : [];

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

            if ($request->order_code) {
                $orderCodeRaw = (string) $request->order_code;
                $orderCodeBase = preg_replace('/\s*\(\d+\)\s*$/u', '', $orderCodeRaw);
                $orderCodeBase = trim((string) $orderCodeBase);
                $installationOrdersId = InstallationOrder::query()
                    ->where('order_code', $orderCodeRaw)
                    ->orWhere('order_code', $orderCodeBase)
                    ->orWhere('order_code', 'like', '%' . $orderCodeBase . '%')
                    ->orderByDesc('id')
                    ->value('id');

                if ($installationOrdersId) {
                    $newAgency = [
                        'agency_name' => $agency->name,
                        'agency_phone' => $agency->phone,
                        'agency_address' => $agency->address,
                        'agency_bank' => $agency->bank_name_agency,
                        'agency_paynumber' => $agency->sotaikhoan,
                        'agency_branch' => $agency->chinhanh,
                        'agency_cccd' => $agency->cccd,
                        'agency_release_date' => $agency->ngaycap,
                    ];
                    $this->saveLogController->auditLog(
                        (int) $installationOrdersId,
                        $orderCodeRaw,
                        'agency_updated',
                        $oldAgency,
                        $newAgency,
                        'source: CollaboratorController@UpdateAgency'
                    );
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

            $history = EditCtvHistory::where(function ($query) use ($id) {
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
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getOrderHistory($orderCode)
    {
        try {
            $history = EditCtvHistory::where('order_code', $orderCode)
                ->orderBy('edited_at', 'desc')
                ->get();

            $groupedHistory = $this->groupHistoryByUser($history);

            $formattedHistory = $groupedHistory->map(function ($item) {
                return $this->formatHistoryItem($item);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'order_code' => $orderCode,
                    'history' => $formattedHistory
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function groupHistoryByUser($history)
    {
        if ($history->isEmpty()) {
            return collect();
        }

        $grouped = collect();
        $currentGroup = null;

        foreach ($history as $item) {
            if ($currentGroup === null) {
                $currentGroup = $item;
            } else {
                $currentUser = $currentGroup->edited_by ?? 'system';
                $itemUser = $item->edited_by ?? 'system';

                if ($currentUser === $itemUser) {
                    $currentGroup = $this->mergeHistoryItems($currentGroup, $item);
                } else {
                    $grouped->push($currentGroup);
                    $currentGroup = $item;
                }
            }
        }

        if ($currentGroup !== null) {
            $grouped->push($currentGroup);
        }

        return $grouped;
    }

    private function mergeHistoryItems($item1, $item2)
    {
        $mergedTime = $item1->edited_at < $item2->edited_at ? $item1->edited_at : $item2->edited_at;

        $changesOld1 = is_array($item1->changes_old) ? $item1->changes_old : (json_decode($item1->changes_old ?? '[]', true) ?: []);
        $changesNew1 = is_array($item1->changes_new) ? $item1->changes_new : (json_decode($item1->changes_new ?? '[]', true) ?: []);

        $changesOld2 = is_array($item2->changes_old) ? $item2->changes_old : (json_decode($item2->changes_old ?? '[]', true) ?: []);
        $changesNew2 = is_array($item2->changes_new) ? $item2->changes_new : (json_decode($item2->changes_new ?? '[]', true) ?: []);

        $mergedOld = $changesOld1;

        $mergedNew = array_merge($changesNew1, $changesNew2);

        foreach ($changesOld2 as $key => $value) {
            if (!isset($mergedOld[$key])) {
                $mergedOld[$key] = $value;
            }
        }

        $merged = clone $item1;
        $merged->edited_at = $mergedTime;
        $merged->changes_old = json_encode($mergedOld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $merged->changes_new = json_encode($mergedNew, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $priorityEvents = ['status_changed', 'ctv_dispatched', 'agency_updated', 'collaborator_finance_updated'];
        $item1Event = $item1->event ?? $item1->action_type;
        $item2Event = $item2->event ?? $item2->action_type;

        if (in_array($item2Event, $priorityEvents) && !in_array($item1Event, $priorityEvents)) {
            $merged->event = $item2Event;
            $merged->action_type = $item2Event;
        }

        return $merged;
    }

    private function formatHistoryItem($item)
    {
        $formatted = [
            'id' => $item->id,
            'event' => $item->event ?? $item->action_type,
            'edited_by' => $item->edited_by ?? 'Hệ thống',
            'edited_at' => $item->edited_at,
            'formatted_edited_at' => $item->edited_at ? $item->edited_at->format('d/m/Y H:i:s') : '',
            'action_type_text' => $this->getActionTypeText($item->event ?? $item->action_type),
        ];

        $changesOld = is_array($item->changes_old) ? $item->changes_old : (json_decode($item->changes_old ?? '[]', true) ?: []);
        $changesNew = is_array($item->changes_new) ? $item->changes_new : (json_decode($item->changes_new ?? '[]', true) ?: []);

        $installationOrder = null;
        $agency = null;
        $oldAgency = null;
        $collaborator = null;
        $oldCollaborator = null;

        if ($item->installation_orders_id) {
            $installationOrder = InstallationOrder::find($item->installation_orders_id);

            if ($installationOrder) {
                $oldAgencyId = $changesOld['agency_id'] ?? null;
                if (!$oldAgencyId && isset($changesNew['agency_id'])) {
                    $oldAgencyId = null;
                }

                if ($oldAgencyId) {
                    $oldAgency = Agency::find($oldAgencyId);
                }

                if ($installationOrder->agency_id) {
                    $agency = Agency::find($installationOrder->agency_id);
                } elseif ($installationOrder->agency_phone) {
                    $normalizedPhone = preg_replace('/[^0-9]/', '', $installationOrder->agency_phone);
                    if ($normalizedPhone) {
                        $agency = Agency::where('phone', $normalizedPhone)->first();
                    }
                }

                $oldCollaboratorId = $changesOld['collaborator_id'] ?? null;
                $oldCollaboratorId = is_numeric($oldCollaboratorId) ? (int) $oldCollaboratorId : null;
                
                if ($oldCollaboratorId) {
                    $oldCollaborator = WarrantyCollaborator::find($oldCollaboratorId);
                }

                if ($installationOrder->collaborator_id) {
                    $collaborator = WarrantyCollaborator::find($installationOrder->collaborator_id);
                }
            }
        }

        $labelMap = [
            'full_name' => 'Họ tên khách hàng',
            'phone_number' => 'SĐT khách hàng',
            'address' => 'Địa chỉ khách hàng',
            'agency_name' => 'Tên đại lý',
            'agency_phone' => 'SĐT đại lý',
            'agency_address' => 'Địa chỉ đại lý',
            'bank_account' => 'Chủ tài khoản ngân hàng',
            'agency_bank' => 'Ngân hàng đại lý',
            'agency_paynumber' => 'Số tài khoản đại lý',
            'agency_branch' => 'Chi nhánh đại lý',
            'agency_cccd' => 'CCCD đại lý',
            'agency_release_date' => 'Ngày cấp đại lý',
            'sotaikhoan' => 'Số tài khoản CTV',
            'bank_name' => 'Ngân hàng CTV',
            'chinhanh' => 'Chi nhánh CTV',
            'cccd' => 'CCCD CTV',
            'ngaycap' => 'Ngày cấp CTV',
            'status_install' => 'Trạng thái',
            'install_cost' => 'Phí lắp đặt',
            'product' => 'Sản phẩm',
            'type' => 'Loại',
            'zone' => 'Khu vực',
            'successed_at' => 'Thời gian hoàn thành',
            'dispatched_at' => 'Thời gian điều phối',
            'paid_at' => 'Thời gian thanh toán',
            'order_code' => 'Mã đơn hàng',
        ];

        $fieldsToSkip = ['agency_id', 'province_id', 'district_id', 'ward_id', 'collaborator_id', 'agency_at'];

        $formatStatusValue = function ($value) {
            if ($value === null || $value === '' || $value === '0' || $value === 0) {
                return 'Chưa điều phối';
            }
            switch ((string)$value) {
                case '1':
                    return 'Đã điều phối';
                case '2':
                    return 'Đã hoàn thành';
                case '3':
                    return 'Đã thanh toán';
                default:
                    return $value;
            }
        };

        $formatDate = function ($value) {
            if (!$value) return null;
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                try {
                    $date = \Carbon\Carbon::parse($value);
                    return $date->format('d/m/Y');
                } catch (\Exception $e) {
                    return $value;
                }
            }
            if (is_object($value) && method_exists($value, 'format')) {
                return $value->format('d/m/Y');
            }
            return $value;
        };

        $changes = [];

        $keys = array_unique(array_merge(array_keys($changesOld), array_keys($changesNew)));

        foreach ($keys as $field) {
            if (in_array($field, $fieldsToSkip)) {
                continue;
            }

            $oldValue = $changesOld[$field] ?? null;

            $newValue = null;

            if ($installationOrder) {
                switch ($field) {
                    case 'full_name':
                        $newValue = $installationOrder->full_name;
                        break;
                    case 'phone_number':
                        $newValue = $installationOrder->phone_number;
                        break;
                    case 'address':
                        $newValue = $installationOrder->address;
                        break;

                    case 'agency_name':
                        $newValue = $installationOrder->agency_name;
                        break;
                    case 'agency_phone':
                        $newValue = $installationOrder->agency_phone;
                        break;
                    case 'agency_address':
                        $newValue = $installationOrder->agency_address;
                        break;

                    case 'order_code':
                        $newValue = $installationOrder->order_code;
                        break;
                    case 'product':
                        $newValue = $installationOrder->product;
                        break;
                    case 'status_install':
                        $newValue = $installationOrder->status_install;
                        break;
                    case 'install_cost':
                        $newValue = $installationOrder->install_cost;
                        break;
                    case 'dispatched_at':
                        $newValue = $installationOrder->dispatched_at;
                        break;
                }
            }

            if ($agency && in_array($field, ['agency_address', 'agency_bank', 'bank_account', 'agency_paynumber', 'agency_branch', 'agency_cccd', 'agency_release_date'])) {
                switch ($field) {
                    case 'agency_address':
                        $newValue = $agency->address;
                        break;
                    case 'agency_bank':
                        $newValue = $agency->bank_name_agency;
                        break;
                    case 'bank_account':
                        $newValue = $agency->bank_account;
                        break;
                    case 'agency_paynumber':
                        $newValue = $agency->sotaikhoan;
                        break;
                    case 'agency_branch':
                        $newValue = $agency->chinhanh;
                        break;
                    case 'agency_cccd':
                        $newValue = $agency->cccd;
                        break;
                    case 'agency_release_date':
                        $newValue = $agency->ngaycap;
                        break;
                }
            }

            if ($oldAgency && in_array($field, ['agency_address', 'agency_bank', 'bank_account', 'agency_paynumber', 'agency_branch', 'agency_cccd', 'agency_release_date'])) {
                if ($oldValue === null) {
                    switch ($field) {
                        case 'agency_address':
                            $oldValue = $oldAgency->address;
                            break;
                        case 'agency_bank':
                            $oldValue = $oldAgency->bank_name_agency;
                            break;
                        case 'bank_account':
                            $oldValue = $oldAgency->bank_account;
                            break;
                        case 'agency_paynumber':
                            $oldValue = $oldAgency->sotaikhoan;
                            break;
                        case 'agency_branch':
                            $oldValue = $oldAgency->chinhanh;
                            break;
                        case 'agency_cccd':
                            $oldValue = $oldAgency->cccd;
                            break;
                        case 'agency_release_date':
                            $oldValue = $oldAgency->ngaycap;
                            break;
                    }
                }
            }

            if ($collaborator && in_array($field, ['sotaikhoan', 'bank_name', 'chinhanh', 'cccd', 'ngaycap'])) {
                switch ($field) {
                    case 'sotaikhoan':
                        $newValue = $collaborator->sotaikhoan;
                        break;
                    case 'bank_name':
                        $newValue = $collaborator->bank_name;
                        break;
                    case 'chinhanh':
                        $newValue = $collaborator->chinhanh;
                        break;
                    case 'cccd':
                        $newValue = $collaborator->cccd;
                        break;
                    case 'ngaycap':
                        $newValue = $collaborator->ngaycap;
                        break;
                }
            }

            if ($oldCollaborator && in_array($field, ['sotaikhoan', 'bank_name', 'chinhanh', 'cccd', 'ngaycap'])) {
                if ($oldValue === null) {
                    switch ($field) {
                        case 'sotaikhoan':
                            $oldValue = $oldCollaborator->sotaikhoan;
                            break;
                        case 'bank_name':
                            $oldValue = $oldCollaborator->bank_name;
                            break;
                        case 'chinhanh':
                            $oldValue = $oldCollaborator->chinhanh;
                            break;
                        case 'cccd':
                            $oldValue = $oldCollaborator->cccd;
                            break;
                        case 'ngaycap':
                            $oldValue = $oldCollaborator->ngaycap;
                            break;
                    }
                }
            }

            if ($newValue === null) {
                $newValue = $changesNew[$field] ?? null;
            }

            $fieldName = $labelMap[$field] ?? $field;

            if ($field === 'status_install') {
                $oldValue = $formatStatusValue($oldValue);
                $newValue = $formatStatusValue($newValue);
            }

            if (in_array($field, ['agency_release_date', 'dispatched_at', 'ngaycap'])) {
                $oldValue = $formatDate($oldValue);
                $newValue = $formatDate($newValue);
            }

            $changes[] = [
                'field' => $field,
                'field_name' => $fieldName,
                'old_value' => ($oldValue === null || $oldValue === '') ? 'Trống' : $oldValue,
                'new_value' => ($newValue === null || $newValue === '') ? 'Trống' : $newValue,
            ];
        }

        if ($agency) {
            $agencyFieldsMap = [
                'agency_address' => ['value' => $agency->address, 'label' => 'Địa chỉ đại lý'],
                'agency_bank' => ['value' => $agency->bank_name_agency, 'label' => 'Ngân hàng đại lý'],
                'bank_account' => ['value' => $agency->bank_account, 'label' => 'Chủ tài khoản ngân hàng'],
                'agency_paynumber' => ['value' => $agency->sotaikhoan, 'label' => 'Số tài khoản đại lý'],
                'agency_branch' => ['value' => $agency->chinhanh, 'label' => 'Chi nhánh đại lý'],
                'agency_cccd' => ['value' => $agency->cccd, 'label' => 'CCCD đại lý'],
                'agency_release_date' => ['value' => $agency->ngaycap, 'label' => 'Ngày cấp đại lý'],
            ];

            foreach ($agencyFieldsMap as $field => $data) {
                $existsInChanges = false;
                foreach ($changes as $change) {
                    if ($change['field'] === $field) {
                        $existsInChanges = true;
                        break;
                    }
                }

                if (!$existsInChanges && ($data['value'] !== null && $data['value'] !== '')) {
                    $oldValue = null;
                    if ($oldAgency) {
                        switch ($field) {
                            case 'agency_address':
                                $oldValue = $oldAgency->address;
                                break;
                            case 'agency_bank':
                                $oldValue = $oldAgency->bank_name_agency;
                                break;
                            case 'bank_account':
                                $oldValue = $oldAgency->bank_account;
                                break;
                            case 'agency_paynumber':
                                $oldValue = $oldAgency->sotaikhoan;
                                break;
                            case 'agency_branch':
                                $oldValue = $oldAgency->chinhanh;
                                break;
                            case 'agency_cccd':
                                $oldValue = $oldAgency->cccd;
                                break;
                            case 'agency_release_date':
                                $oldValue = $oldAgency->ngaycap;
                                break;
                        }
                    }

                    $displayValue = $data['value'];
                    if ($field === 'agency_release_date') {
                        $displayValue = $formatDate($displayValue);
                        $oldValue = $formatDate($oldValue);
                    }

                    $changes[] = [
                        'field' => $field,
                        'field_name' => $data['label'],
                        'old_value' => ($oldValue === null || $oldValue === '') ? 'Trống' : $oldValue,
                        'new_value' => ($displayValue === null || $displayValue === '') ? 'Trống' : $displayValue,
                    ];
                }
            }
        }

        if ($collaborator) {
            $collaboratorFieldsMap = [
                'sotaikhoan' => ['value' => $collaborator->sotaikhoan, 'label' => 'Số tài khoản CTV'],
                'bank_name' => ['value' => $collaborator->bank_name, 'label' => 'Ngân hàng CTV'],
                'chinhanh' => ['value' => $collaborator->chinhanh, 'label' => 'Chi nhánh CTV'],
                'cccd' => ['value' => $collaborator->cccd, 'label' => 'CCCD CTV'],
                'ngaycap' => ['value' => $collaborator->ngaycap, 'label' => 'Ngày cấp CTV'],
            ];

            foreach ($collaboratorFieldsMap as $field => $data) {
                $existsInChanges = false;
                foreach ($changes as $change) {
                    if ($change['field'] === $field) {
                        $existsInChanges = true;
                        break;
                    }
                }

                if (!$existsInChanges && ($data['value'] !== null && $data['value'] !== '')) {
                    $oldValue = null;
                    if ($oldCollaborator) {
                        switch ($field) {
                            case 'sotaikhoan':
                                $oldValue = $oldCollaborator->sotaikhoan;
                                break;
                            case 'bank_name':
                                $oldValue = $oldCollaborator->bank_name;
                                break;
                            case 'chinhanh':
                                $oldValue = $oldCollaborator->chinhanh;
                                break;
                            case 'cccd':
                                $oldValue = $oldCollaborator->cccd;
                                break;
                            case 'ngaycap':
                                $oldValue = $oldCollaborator->ngaycap;
                                break;
                        }
                    }

                    $displayValue = $data['value'];
                    if ($field === 'ngaycap') {
                        $displayValue = $formatDate($displayValue);
                        $oldValue = $formatDate($oldValue);
                    }

                    $changes[] = [
                        'field' => $field,
                        'field_name' => $data['label'],
                        'old_value' => ($oldValue === null || $oldValue === '') ? 'Trống' : $oldValue,
                        'new_value' => ($displayValue === null || $displayValue === '') ? 'Trống' : $displayValue,
                    ];
                }
            }
        }

        if ($installationOrder) {
            $customerFieldsMap = [
                'full_name' => ['value' => $installationOrder->full_name, 'label' => 'Họ tên khách hàng'],
                'phone_number' => ['value' => $installationOrder->phone_number, 'label' => 'SĐT khách hàng'],
                'address' => ['value' => $installationOrder->address, 'label' => 'Địa chỉ khách hàng'],
            ];

            foreach ($customerFieldsMap as $field => $data) {
                $existsInChanges = false;
                foreach ($changes as $change) {
                    if ($change['field'] === $field) {
                        $existsInChanges = true;
                        break;
                    }
                }

                if (!$existsInChanges && ($data['value'] !== null && $data['value'] !== '')) {
                    $oldValue = $changesOld[$field] ?? null;
                    $changes[] = [
                        'field' => $field,
                        'field_name' => $data['label'],
                        'old_value' => ($oldValue === null || $oldValue === '') ? 'Trống' : $oldValue,
                        'new_value' => ($data['value'] === null || $data['value'] === '') ? 'Trống' : $data['value'],
                    ];
                }
            }
        }

        if ($installationOrder) {
            $orderFieldsMap = [
                'order_code' => ['value' => $installationOrder->order_code, 'label' => 'Mã đơn hàng'],
                'product' => ['value' => $installationOrder->product, 'label' => 'Sản phẩm'],
                'status_install' => ['value' => $installationOrder->status_install, 'label' => 'Trạng thái'],
                'install_cost' => ['value' => $installationOrder->install_cost, 'label' => 'Phí lắp đặt'],
                'dispatched_at' => ['value' => $installationOrder->dispatched_at, 'label' => 'Thời gian điều phối'],
            ];

            foreach ($orderFieldsMap as $field => $data) {
                $existsInChanges = false;
                foreach ($changes as $change) {
                    if ($change['field'] === $field) {
                        $existsInChanges = true;
                        break;
                    }
                }

                if (!$existsInChanges && ($data['value'] !== null && $data['value'] !== '')) {
                    $oldValue = $changesOld[$field] ?? null;

                    // Format status_install
                    if ($field === 'status_install') {
                        $oldValue = $formatStatusValue($oldValue);
                        $newValue = $formatStatusValue($data['value']);
                    } else {
                        $newValue = $data['value'];
                    }

                    // Format dispatched_at
                    if ($field === 'dispatched_at') {
                        $oldValue = $formatDate($oldValue);
                        $newValue = $formatDate($newValue);
                    }

                    $changes[] = [
                        'field' => $field,
                        'field_name' => $data['label'],
                        'old_value' => ($oldValue === null || $oldValue === '') ? 'Trống' : $oldValue,
                        'new_value' => ($newValue === null || $newValue === '') ? 'Trống' : $newValue,
                    ];
                }
            }
        }

        $formatted['changes_detail'] = $changes;

        // Xác định source dựa trên event
        $event = $item->event ?? $item->action_type ?? '';
        $sourceMap = [
            'installation_order_updated' => 'Cập nhật thông tin đơn lắp đặt',
            'agency_updated' => 'Cập nhật thông tin đại lý',
            'collaborator_finance_updated' => 'Cập nhật thông tin tài khoản CTV',
            'ctv_dispatched' => 'Điều phối CTV',
            'status_changed' => 'Thay đổi trạng thái đơn hàng',
        ];
        $formatted['source'] = $sourceMap[$event] ?? 'Cập nhật thông tin';

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
            'switch_to_ctv' => 'Chuyển về CTV',
            'installation_order_updated' => 'Cập nhật điều phối/lắp đặt',
            'agency_updated' => 'Cập nhật thông tin đại lý',
            'collaborator_finance_updated' => 'Cập nhật thông tin tài khoản CTV',
            'ctv_dispatched' => 'Điều phối CTV',
            'status_changed' => 'Thay đổi trạng thái',
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

            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển sang "Đại lý lắp đặt" thành công'
            ]);
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

            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển về CTV thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
