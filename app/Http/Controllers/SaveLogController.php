<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\EditCtvHistory;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\Kho\Agency;
use Illuminate\Support\Facades\Log;
use App\Enum;

class SaveLogController extends Controller
{
    /**
     * Trả về true nếu value là nhãn "Đại lý lắp đặt"
     */
    private function isAgencyLabel($value): bool
    {
        if ($value === null) return false;
        return mb_strtolower(trim((string) $value)) === mb_strtolower(trim(Enum::AGENCY_INSTALL_CHECKBOX_LABEL));
    }

    /**
     * Normalize legacy: nếu đang là "Đại lý lắp đặt" thì không được giữ collaborator_id (đặc biệt legacy hay là 1)
     */
    private function normalizeAgencyCollaboratorId($collaboratorId, $fullName)
    {
        if ($this->isAgencyLabel($fullName)) {
            return null;
        }
        return $collaboratorId;
    }

    private function ctvFieldMappings(): array
    {
        return [
            'full_name' => ['old_full_name', 'new_full_name'],
            'phone' => ['old_phone', 'new_phone'],
            'province' => ['old_province', 'new_province'],
            'province_id' => ['old_province_id', 'new_province_id'],
            'district' => ['old_district', 'new_district'],
            'district_id' => ['old_district_id', 'new_district_id'],
            'ward' => ['old_ward', 'new_ward'],
            'ward_id' => ['old_ward_id', 'new_ward_id'],
            'address' => ['old_address', 'new_address'],
            'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
            'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
            'cccd' => ['old_cccd', 'new_cccd'],
            'ngaycap' => ['old_ngaycap', 'new_ngaycap'],
        ];
    }

    private function agencyFieldMappings(): array
    {
        return [
            'name' => ['old_agency_name', 'new_agency_name'],
            'phone' => ['old_agency_phone', 'new_agency_phone'],
            'address' => ['old_agency_address', 'new_agency_address'],
            'sotaikhoan' => ['old_agency_paynumber', 'new_agency_paynumber'],
            'chinhanh' => ['old_agency_branch', 'new_agency_branch'],
            'cccd' => ['old_agency_cccd', 'new_agency_cccd'],
            'ngaycap' => ['old_agency_release_date', 'new_agency_release_date'],
        ];
    }

    private function latestHistoryByOrderCode(string $orderCode): ?EditCtvHistory
    {
        return EditCtvHistory::where('order_code', $orderCode)
            ->orderByDesc('edited_at')
            ->orderByDesc('id')
            ->first();
    }

    private function buildOldSnapshotFromLatest(?EditCtvHistory $latest): array
    {
        if (!$latest) {
            return [];
        }

        $data = [];

        // Normalize legacy: nếu latest là đại lý thì không giữ collaborator_id
        $latestNewCollaboratorId = $this->normalizeAgencyCollaboratorId($latest->new_collaborator_id, $latest->new_full_name);
        $data['old_collaborator_id'] = $latestNewCollaboratorId;

        foreach ($this->ctvFieldMappings() as $field => [$oldCol, $newCol]) {
            $data[$oldCol] = $latest->{$newCol};
        }
        foreach ($this->agencyFieldMappings() as $field => [$oldCol, $newCol]) {
            $data[$oldCol] = $latest->{$newCol};
        }

        return $data;
    }

    private function setCtvAsAgency(EditCtvHistory $history): void
    {
        $history->new_collaborator_id = null;
        $history->new_full_name = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_phone = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_province = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_province_id = null;
        $history->new_district = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_district_id = null;
        $history->new_ward = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_ward_id = null;
        $history->new_address = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_sotaikhoan = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_chinhanh = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_cccd = Enum::AGENCY_INSTALL_CHECKBOX_LABEL;
        $history->new_ngaycap = null;
    }

    private function setCtvFromModel(EditCtvHistory $history, WarrantyCollaborator $collab): void
    {
        // Nếu (legacy) có CTV tên = "Đại lý lắp đặt" thì coi như đại lý, không lưu id
        if ($this->isAgencyLabel($collab->full_name ?? null)) {
            $this->setCtvAsAgency($history);
            return;
        }

        $history->new_collaborator_id = $collab->id;
        $history->new_full_name = $collab->full_name;
        $history->new_phone = $collab->phone;
        $history->new_province = $collab->province;
        $history->new_province_id = $collab->province_id;
        $history->new_district = $collab->district;
        $history->new_district_id = $collab->district_id;
        $history->new_ward = $collab->ward;
        $history->new_ward_id = $collab->ward_id;
        $history->new_address = $collab->address;
        $history->new_sotaikhoan = $collab->sotaikhoan;
        $history->new_chinhanh = $collab->chinhanh;
        $history->new_cccd = $collab->cccd;
        $history->new_ngaycap = $collab->ngaycap;
    }

    private function buildNewCtvSnapshot(?WarrantyCollaborator $collab): array
    {
        // null => đại lý lắp đặt
        if (!$collab) {
            return [
                'new_collaborator_id' => null,
                'new_full_name' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_phone' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_province' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_province_id' => null,
                'new_district' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_district_id' => null,
                'new_ward' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_ward_id' => null,
                'new_address' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_sotaikhoan' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_chinhanh' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_cccd' => Enum::AGENCY_INSTALL_CHECKBOX_LABEL,
                'new_ngaycap' => null,
            ];
        }

        // Legacy: nếu CTV có tên = "Đại lý lắp đặt" thì coi như đại lý
        if ($this->isAgencyLabel($collab->full_name ?? null)) {
            return $this->buildNewCtvSnapshot(null);
        }

        return [
            'new_collaborator_id' => $collab->id,
            'new_full_name' => $collab->full_name,
            'new_phone' => $collab->phone,
            'new_province' => $collab->province,
            'new_province_id' => $collab->province_id,
            'new_district' => $collab->district,
            'new_district_id' => $collab->district_id,
            'new_ward' => $collab->ward,
            'new_ward_id' => $collab->ward_id,
            'new_address' => $collab->address,
            'new_sotaikhoan' => $collab->sotaikhoan,
            'new_chinhanh' => $collab->chinhanh,
            'new_cccd' => $collab->cccd,
            'new_ngaycap' => $collab->ngaycap,
        ];
    }

    private function buildNewAgencySnapshot(?Agency $agency): array
    {
        if (!$agency) {
            return [
                'new_agency_name' => null,
                'new_agency_phone' => null,
                'new_agency_address' => null,
                'new_agency_paynumber' => null,
                'new_agency_branch' => null,
                'new_agency_cccd' => null,
                'new_agency_release_date' => null,
            ];
        }

        return [
            'new_agency_name' => $agency->name,
            'new_agency_phone' => $agency->phone,
            'new_agency_address' => $agency->address,
            'new_agency_paynumber' => $agency->sotaikhoan,
            'new_agency_branch' => $agency->chinhanh,
            'new_agency_cccd' => $agency->cccd,
            'new_agency_release_date' => $agency->ngaycap,
        ];
    }

    /**
     * Ghi 1 bản ghi log mới theo order_code (KHÔNG update bản ghi cũ).
     * old_* lấy từ new_* của bản ghi gần nhất.
     */
    public function syncCtvSnapshotByOrderCode(
        string $orderCode,
        ?WarrantyCollaborator $collab,
        ?int $installationOrdersId = null,
        string $actionType = 'update',
        ?string $comments = null
    ): ?EditCtvHistory
    {
        $latest = $this->latestHistoryByOrderCode($orderCode);
        $oldSnapshot = $this->buildOldSnapshotFromLatest($latest);
        $newSnapshot = $this->buildNewCtvSnapshot($collab);
        $newAgencyCarry = [];
        if ($latest) {
            // Carry forward agency snapshot so mỗi bản ghi vẫn giữ được thông tin đại lý hiện tại
            foreach ($this->agencyFieldMappings() as $field => [$oldCol, $newCol]) {
                $newAgencyCarry[$newCol] = $latest->{$newCol};
            }
        }

        // Nếu snapshot mới giống snapshot mới nhất => không tạo log (tránh spam)
        if ($latest) {
            $same = true;
            foreach (array_merge($newSnapshot, $newAgencyCarry) as $k => $v) {
                if ((string) ($latest->{$k} ?? '') !== (string) ($v ?? '')) {
                    $same = false;
                    break;
                }
            }
            if ($same) {
                return null;
            }
        }

        $data = array_merge($oldSnapshot, $newSnapshot, $newAgencyCarry, [
            'order_code' => $orderCode,
            'installation_orders_id' => $installationOrdersId,
            'action_type' => $actionType,
            'edited_by' => session('user', 'system'),
            'edited_at' => now(),
            'comments' => $comments,
        ]);

        return EditCtvHistory::create($data);
    }

    /**
     * Đồng bộ snapshot đại lý theo order_code: tạo 1 bản ghi mới nếu có thay đổi.
     */
    public function syncAgencySnapshotByOrderCode(
        string $orderCode,
        Agency $agency,
        ?int $installationOrdersId = null,
        string $actionType = 'update_agency',
        ?string $comments = null
    ): ?EditCtvHistory
    {
        $latest = $this->latestHistoryByOrderCode($orderCode);
        $oldSnapshot = $this->buildOldSnapshotFromLatest($latest);
        // Agency update implies trạng thái hiện tại là "Đại lý lắp đặt"
        $newSnapshot = array_merge(
            $this->buildNewCtvSnapshot(null),
            $this->buildNewAgencySnapshot($agency)
        );

        if ($latest) {
            $same = true;
            foreach ($newSnapshot as $k => $v) {
                if ((string) ($latest->{$k} ?? '') !== (string) ($v ?? '')) {
                    $same = false;
                    break;
                }
            }
            if ($same) {
                return null;
            }
        }

        $data = array_merge($oldSnapshot, $newSnapshot, [
            'order_code' => $orderCode,
            'installation_orders_id' => $installationOrdersId,
            'action_type' => $actionType,
            'edited_by' => session('user', 'system'),
            'edited_at' => now(),
            'comments' => $comments,
        ]);

        return EditCtvHistory::create($data);
    }

    /**
     * Ghi 1 bản ghi log cho thay đổi trạng thái / thao tác chung (không thay đổi snapshot).
     */
    public function appendActionHistory(string $orderCode, ?int $installationOrdersId, string $actionType, string $comments): EditCtvHistory
    {
        $latest = $this->latestHistoryByOrderCode($orderCode);
        $oldSnapshot = $this->buildOldSnapshotFromLatest($latest);

        // new snapshot = giữ nguyên new_* từ latest (nếu có) để record có đủ context
        $newSnapshot = [];
        if ($latest) {
            foreach ($this->ctvFieldMappings() as $field => [$oldCol, $newCol]) {
                $newSnapshot[$newCol] = $latest->{$newCol};
            }
            $newSnapshot['new_collaborator_id'] = $this->normalizeAgencyCollaboratorId($latest->new_collaborator_id, $latest->new_full_name);
            foreach ($this->agencyFieldMappings() as $field => [$oldCol, $newCol]) {
                $newSnapshot[$newCol] = $latest->{$newCol};
            }
        }

        return EditCtvHistory::create(array_merge($oldSnapshot, $newSnapshot, [
            'order_code' => $orderCode,
            'installation_orders_id' => $installationOrdersId,
            'action_type' => $actionType,
            'edited_by' => session('user', 'system'),
            'edited_at' => now(),
            'comments' => $comments,
        ]));
    }

    /**
     * Ghi lại lịch sử thay đổi cộng tác viên
     */
    public function logCollaboratorHistory($orderCode, $collaboratorId, $actionType, $fieldName = null, $oldValue = null, $newValue = null, $comments = null)
    {
        try {
            if ($orderCode) {
                $collab = $collaboratorId ? WarrantyCollaborator::find($collaboratorId) : null;
                $this->syncCtvSnapshotByOrderCode(
                    $orderCode,
                    $collab,
                    null,
                    $actionType,
                    $comments ?? 'Cập nhật CTV'
                );
                return;
            }

            // Fallback: không có order_code thì tạo bản ghi riêng (nếu schema cho phép)
            EditCtvHistory::create([
                'new_collaborator_id' => $collaboratorId,
                'action_type' => $actionType,
                'edited_by' => session('user', 'system'),
                'comments' => $comments,
                'edited_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi ghi lịch sử CTV: ' . $e->getMessage());
        }
    }

    /**
     * So sánh và ghi lại các thay đổi (gom thành một bản ghi) - Sử dụng updateOrCreate
     */
    public function logChanges($collaborator, $newData, $actionType = 'update', $orderCode = null)
    {
        $changes = [];
        
        // Danh sách các trường cần theo dõi với tên hiển thị
        $trackedFields = [
            'full_name' => 'Họ và tên',
            'phone' => 'Số điện thoại', 
            'province' => 'Tỉnh/Thành phố',
            'province_id' => 'ID Tỉnh',
            'district' => 'Quận/Huyện',
            'district_id' => 'ID Quận',
            'ward' => 'Phường/Xã',
            'ward_id' => 'ID Phường',
            'address' => 'Địa chỉ',
            'sotaikhoan' => 'Số tài khoản',
            'chinhanh' => 'Ngân hàng',
            'cccd' => 'CCCD',
            'ngaycap' => 'Ngày cấp'
        ];

        foreach ($trackedFields as $field => $fieldName) {
            $oldValue = $collaborator->$field ?? null;
            $newValue = $newData[$field] ?? null;
            
            // Chuyển đổi về string để so sánh
            $oldValue = is_null($oldValue) ? null : (string) $oldValue;
            $newValue = is_null($newValue) ? null : (string) $newValue;
            
            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $field,
                    'field_name' => $fieldName,
                    'old_value' => $oldValue,
                    'new_value' => $newValue
                ];
            }
        }

        // Nếu có thay đổi, ghi lại thành một bản ghi duy nhất
        if (!empty($changes)) {
            
                // Kiểm tra xem đã có bản ghi lịch sử chưa dựa trên order_code (không phụ thuộc collaborator_id)
            $existingHistory = null;
            if ($orderCode) {
                $existingHistory = EditCtvHistory::where('order_code', $orderCode)
                    ->orderBy('edited_at', 'desc')
                    ->first();
            }
            
            // Nếu không tìm thấy theo order_code, tìm theo new_collaborator_id (backward compatibility)
            if (!$existingHistory) {
                $existingHistory = EditCtvHistory::where('new_collaborator_id', $collaborator->id)
                    ->whereNull('order_code')
                    ->orderBy('edited_at', 'desc')
                    ->first();
            }
            
            // Chỉ cập nhật bản ghi hiện có - Logic ngăn xếp
            // Đẩy dữ liệu hiện tại (new) vào old, dữ liệu mới vào new
            
            $fieldMappings = [
                'full_name' => ['old_full_name', 'new_full_name'],
                'phone' => ['old_phone', 'new_phone'],
                'province' => ['old_province', 'new_province'],
                'province_id' => ['old_province_id', 'new_province_id'],
                'district' => ['old_district', 'new_district'],
                'district_id' => ['old_district_id', 'new_district_id'],
                'ward' => ['old_ward', 'new_ward'],
                'ward_id' => ['old_ward_id', 'new_ward_id'],
                'address' => ['old_address', 'new_address'],
                'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
                'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
                'cccd' => ['old_cccd', 'new_cccd'],
                'ngaycap' => ['old_ngaycap', 'new_ngaycap']
            ];

            if ($existingHistory) {
                // Cập nhật bản ghi hiện có
                // Logic ngăn xếp: Đẩy dữ liệu hiện tại vào old, dữ liệu mới vào new
                foreach ($changes as $change) {
                    $field = $change['field'];
                    $newValue = $change['new_value']; // Dữ liệu mới từ form
                    
                    if (isset($fieldMappings[$field])) {
                        // Đẩy dữ liệu hiện tại (new) vào old
                        $existingHistory->{$fieldMappings[$field][0]} = $existingHistory->{$fieldMappings[$field][1]};
                        // Lưu dữ liệu mới vào new
                        $existingHistory->{$fieldMappings[$field][1]} = $newValue;
                    }
                }
                
                // Cập nhật thông tin chung
                $existingHistory->new_collaborator_id = $collaborator->id; // Cập nhật new_collaborator_id mới
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                
                // Tạo comment dễ hiểu cho người dùng
                $changeDescriptions = [];
                foreach ($changes as $change) {
                    $oldText = $change['old_value'] ?: 'Trống';
                    $newText = $change['new_value'] ?: 'Trống';
                    $changeDescriptions[] = "{$change['field_name']}: '{$oldText}' → '{$newText}'";
                }
                $existingHistory->comments = 'Cập nhật ' . count($changes) . ' trường: ' . implode('; ', $changeDescriptions);
                $existingHistory->order_code = $orderCode;
                
                // Cập nhật JSON backup
                $existingHistory->old_value = json_encode($changes);
                
                try {
                    $existingHistory->save();
                } catch (\Exception $e) {
                    Log::error('Lỗi cập nhật lịch sử CTV: ' . $e->getMessage());
                }
            } else {
                // Tạo bản ghi mới nếu chưa có
                // Tạo dữ liệu để lưu vào các cột riêng biệt
                $historyData = [
                    'new_collaborator_id' => $collaborator->id,
                    'action_type' => $actionType,
                    'field_name' => 'multiple_fields',
                    'edited_by' => session('user', 'system'),
                    'edited_at' => now(),
                    'order_code' => $orderCode
                ];
                
                $changeDescriptions = [];
                foreach ($changes as $change) {
                    $oldText = $change['old_value'] ?: 'Trống';
                    $newText = $change['new_value'] ?: 'Trống';
                    $changeDescriptions[] = "{$change['field_name']}: '{$oldText}' → '{$newText}'";
                }
                $historyData['comments'] = 'Cập nhật ' . count($changes) . ' trường: ' . implode('; ', $changeDescriptions);

                // Lưu dữ liệu cũ và mới từ changes
                foreach ($changes as $change) {
                    $field = $change['field'];
                    $oldValue = $change['old_value']; // Dữ liệu cũ từ collaborator hiện tại
                    $newValue = $change['new_value']; // Dữ liệu mới từ form
                    
                    if (isset($fieldMappings[$field])) {
                        $historyData[$fieldMappings[$field][0]] = $oldValue; // old_value
                        $historyData[$fieldMappings[$field][1]] = $newValue; // new_value
                    }
                }

                // Lưu JSON để backward compatibility
                $historyData['old_value'] = json_encode($changes);
                
                try {
                    $saved = EditCtvHistory::create($historyData);
                } catch (\Exception $e) {
                    Log::error('Lỗi tạo lịch sử CTV: ' . $e->getMessage());
                }
            }
        }

        return $changes;
    }

    /**
     * So sánh và ghi lại các thay đổi (sử dụng updateOrCreate)
     */
    public function logChangesNew($collaborator, $newData, $actionType = 'update', $orderCode = null)
    {
        $changes = [];
        
        // Danh sách các trường cần theo dõi với tên hiển thị
        $trackedFields = [
            'full_name' => 'Họ và tên',
            'phone' => 'Số điện thoại', 
            'province' => 'Tỉnh/Thành phố',
            'province_id' => 'ID Tỉnh',
            'district' => 'Quận/Huyện',
            'district_id' => 'ID Quận',
            'ward' => 'Phường/Xã',
            'ward_id' => 'ID Phường',
            'address' => 'Địa chỉ',
            'sotaikhoan' => 'Số tài khoản',
            'chinhanh' => 'Ngân hàng',
            'cccd' => 'CCCD',
            'ngaycap' => 'Ngày cấp'
        ];

        foreach ($trackedFields as $field => $fieldName) {
            $oldValue = $collaborator->$field ?? null;
            $newValue = $newData[$field] ?? null;
            
            // Chuyển đổi về string để so sánh
            $oldValue = is_null($oldValue) ? null : (string) $oldValue;
            $newValue = is_null($newValue) ? null : (string) $newValue;
            
            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $field,
                    'field_name' => $fieldName,
                    'old_value' => $oldValue,
                    'new_value' => $newValue
                ];
            }
        }

        // Nếu có thay đổi, ghi lại thành một bản ghi duy nhất
        if (!empty($changes)) {
            
            // Tạo comment dễ hiểu cho người dùng
            $changeDescriptions = [];
            foreach ($changes as $change) {
                $oldText = $change['old_value'] ?: 'Trống';
                $newText = $change['new_value'] ?: 'Trống';
                $changeDescriptions[] = "{$change['field_name']}: '{$oldText}' → '{$newText}'";
            }
            
            $fieldMappings = [
                'full_name' => ['old_full_name', 'new_full_name'],
                'phone' => ['old_phone', 'new_phone'],
                'province' => ['old_province', 'new_province'],
                'province_id' => ['old_province_id', 'new_province_id'],
                'district' => ['old_district', 'new_district'],
                'district_id' => ['old_district_id', 'new_district_id'],
                'ward' => ['old_ward', 'new_ward'],
                'ward_id' => ['old_ward_id', 'new_ward_id'],
                'address' => ['old_address', 'new_address'],
                'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
                'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
                'cccd' => ['old_cccd', 'new_cccd'],
                'ngaycap' => ['old_ngaycap', 'new_ngaycap']
            ];

            // Chuẩn bị dữ liệu cơ bản
            $historyData = [
                'old_collaborator_id' => $collaborator->id, // CTV cũ (chính là CTV hiện tại)
                'new_collaborator_id' => $collaborator->id, // CTV mới (sẽ được cập nhật sau)
                'action_type' => $actionType,
                'edited_by' => session('user', 'system'),
                'edited_at' => now(),
                'order_code' => $orderCode,
                'comments' => 'Cập nhật ' . count($changes) . ' trường: ' . implode('; ', $changeDescriptions)
            ];

            // Lưu dữ liệu cũ và mới từ changes
            foreach ($changes as $change) {
                $field = $change['field'];
                $oldValue = $change['old_value']; // Dữ liệu cũ từ collaborator hiện tại
                $newValue = $change['new_value']; // Dữ liệu mới từ form
                
                if (isset($fieldMappings[$field])) {
                    $historyData[$fieldMappings[$field][0]] = $oldValue; // old_value
                    $historyData[$fieldMappings[$field][1]] = $newValue; // new_value
                }
            }

            // Sử dụng updateOrCreate với logic đặc biệt
            try {
                if ($orderCode) {
                    // Tìm bản ghi hiện có trước
                    $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();
                    
                    if ($existingHistory) {
                        // CẬP NHẬT: Logic stacking - Đẩy dữ liệu cũ (new_*) vào old_*, dữ liệu mới vào new_*
                        // 1. Đẩy thông tin CTV cũ vào old_collaborator_id
                        $existingHistory->old_collaborator_id = $existingHistory->new_collaborator_id;
                        $existingHistory->new_collaborator_id = $collaborator->id;
                        
                        // 2. CHỈ ĐẨY DỮ LIỆU CTV CŨ (new_*) vào old_* - KHÔNG ĐỘNG CHẠM ĐẾN ĐẠI LÝ
                        $ctvFieldMappings = [
                            'full_name' => ['old_full_name', 'new_full_name'],
                            'phone' => ['old_phone', 'new_phone'],
                            'province' => ['old_province', 'new_province'],
                            'province_id' => ['old_province_id', 'new_province_id'],
                            'district' => ['old_district', 'new_district'],
                            'district_id' => ['old_district_id', 'new_district_id'],
                            'ward' => ['old_ward', 'new_ward'],
                            'ward_id' => ['old_ward_id', 'new_ward_id'],
                            'address' => ['old_address', 'new_address'],
                            'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
                            'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
                            'cccd' => ['old_cccd', 'new_cccd'],
                            'ngaycap' => ['old_ngaycap', 'new_ngaycap']
                        ];
                        
                        foreach ($ctvFieldMappings as $field => $mapping) {
                            // CHỈ đẩy dữ liệu CTV hiện tại (new_*) vào old_*
                            $existingHistory->{$mapping[0]} = $existingHistory->{$mapping[1]};
                        }
                        
                        // KHÔNG ĐỘNG CHẠM ĐẾN THÔNG TIN ĐẠI LÝ - Giữ nguyên old_agency_* và new_agency_*
                        
                        // 3. Lưu dữ liệu CTV mới vào new_* từ changes (chỉ CTV, không động chạm đại lý)
                        foreach ($changes as $change) {
                            $field = $change['field'];
                            $newValue = $change['new_value']; // Dữ liệu mới từ form
                            
                            if (isset($ctvFieldMappings[$field])) {
                                $existingHistory->{$ctvFieldMappings[$field][1]} = $newValue;
                            }
                        }
                        
                        // 4. Lưu thông tin CTV cũ và mới vào các cột (chỉ cho các trường không có trong changes)
                        $existingHistory = $this->saveCollaboratorDetails($existingHistory, $existingHistory->old_collaborator_id, $collaborator, $changes);
                        
                        // 5. Cập nhật thông tin chung
                        $existingHistory->new_collaborator_id = $collaborator->id;
                        $existingHistory->edited_by = session('user', 'system');
                        $existingHistory->edited_at = now();
                        $existingHistory->comments = 'Cập nhật ' . count($changes) . ' trường: ' . implode('; ', $changeDescriptions);
                        
                        $existingHistory->save();
                        $history = $existingHistory;
                    } else {
                        // TẠO MỚI: Lưu dữ liệu cũ vào old_*, dữ liệu mới vào new_*
                        $history = EditCtvHistory::create($historyData);
                        
                        // Lưu thông tin CTV mới vào các cột
                        $history = $this->saveCollaboratorDetails($history, null, $collaborator);
                        
                    }
                } else {
                    // Backward compatibility: tìm theo new_collaborator_id nếu không có order_code
                    $existingHistory = EditCtvHistory::where('new_collaborator_id', $collaborator->id)
                        ->whereNull('order_code')
                        ->first();
                    
                    if ($existingHistory) {
                        // CẬP NHẬT: Logic stacking tương tự
                        $existingHistory->old_collaborator_id = $existingHistory->new_collaborator_id;
                        $existingHistory->new_collaborator_id = $collaborator->id;
                        
                        // CHỈ ĐẨY DỮ LIỆU CTV CŨ vào old_* - KHÔNG ĐỘNG CHẠM ĐẾN ĐẠI LÝ
                        $ctvFieldMappings = [
                            'full_name' => ['old_full_name', 'new_full_name'],
                            'phone' => ['old_phone', 'new_phone'],
                            'province' => ['old_province', 'new_province'],
                            'province_id' => ['old_province_id', 'new_province_id'],
                            'district' => ['old_district', 'new_district'],
                            'district_id' => ['old_district_id', 'new_district_id'],
                            'ward' => ['old_ward', 'new_ward'],
                            'ward_id' => ['old_ward_id', 'new_ward_id'],
                            'address' => ['old_address', 'new_address'],
                            'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
                            'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
                            'cccd' => ['old_cccd', 'new_cccd'],
                            'ngaycap' => ['old_ngaycap', 'new_ngaycap']
                        ];
                        
                        foreach ($ctvFieldMappings as $field => $mapping) {
                            // CHỈ đẩy dữ liệu CTV hiện tại (new_*) vào old_*
                            $existingHistory->{$mapping[0]} = $existingHistory->{$mapping[1]};
                        }
                        
                        // KHÔNG ĐỘNG CHẠM ĐẾN THÔNG TIN ĐẠI LÝ - Giữ nguyên old_agency_* và new_agency_*
                        
                        // Lưu dữ liệu CTV mới vào new_* (chỉ CTV, không động chạm đại lý)
                        foreach ($changes as $change) {
                            $field = $change['field'];
                            $newValue = $change['new_value'];
                            
                            if (isset($ctvFieldMappings[$field])) {
                                $existingHistory->{$ctvFieldMappings[$field][1]} = $newValue;
                            }
                        }
                        
                        $existingHistory = $this->saveCollaboratorDetails($existingHistory, $existingHistory->old_collaborator_id, $collaborator, $changes);
                        
                        $existingHistory->edited_by = session('user', 'system');
                        $existingHistory->edited_at = now();
                        $existingHistory->comments = 'Cập nhật ' . count($changes) . ' trường: ' . implode('; ', $changeDescriptions);
                        
                        $existingHistory->save();
                        $history = $existingHistory;
                        
                    } else {
                        // TẠO MỚI: Lưu dữ liệu cũ vào old_*, dữ liệu mới vào new_*
                        $history = EditCtvHistory::create($historyData);
                        
                        // Lưu thông tin CTV mới vào các cột
                        $history = $this->saveCollaboratorDetails($history, null, $collaborator);
                        
                    }
                }
                
            } catch (\Exception $e) {
                Log::error('Lỗi xử lý lịch sử CTV: ' . $e->getMessage());
            }
        }

        return $changes;
    }

    /**
     * Lưu thông tin CTV vào các cột old_* và new_*
     * @param object $history - Đối tượng lịch sử
     * @param int|null $oldCollaboratorId - ID CTV cũ
     * @param object $newCollaborator - Đối tượng CTV mới
     * @param array|null $changes - Mảng các trường đã thay đổi (optional)
     * @return object
     */
    public function saveCollaboratorDetails($history, $oldCollaboratorId = null, $newCollaborator, $changes = null)
    {
        // Định nghĩa mapping các trường
        $fieldMappings = [
            'full_name' => ['old_full_name', 'new_full_name'],
            'phone' => ['old_phone', 'new_phone'],
            'province' => ['old_province', 'new_province'],
            'province_id' => ['old_province_id', 'new_province_id'],
            'district' => ['old_district', 'new_district'],
            'district_id' => ['old_district_id', 'new_district_id'],
            'ward' => ['old_ward', 'new_ward'],
            'ward_id' => ['old_ward_id', 'new_ward_id'],
            'address' => ['old_address', 'new_address'],
            'sotaikhoan' => ['old_sotaikhoan', 'new_sotaikhoan'],
            'chinhanh' => ['old_chinhanh', 'new_chinhanh'],
            'cccd' => ['old_cccd', 'new_cccd'],
            'ngaycap' => ['old_ngaycap', 'new_ngaycap']
        ];

        // Lấy danh sách các trường đã thay đổi (nếu có)
        $changedFields = $changes ? array_column($changes, 'field') : [];

        // Lưu thông tin CTV cũ vào các cột old_*
        if ($oldCollaboratorId) {
            $oldCollaborator = WarrantyCollaborator::find($oldCollaboratorId);
            if ($oldCollaborator) {
                foreach ($fieldMappings as $field => $mapping) {
                    $oldColumn = $mapping[0]; // old_*
                    
                    // Chỉ lưu nếu trường này chưa được xử lý trong $changes
                    if (empty($changedFields) || !in_array($field, $changedFields)) {
                        $history->$oldColumn = $oldCollaborator->$field;
                    }
                }
            }
        }
        
        // Lưu thông tin CTV mới vào các cột new_*
        if ($newCollaborator) {
            foreach ($fieldMappings as $field => $mapping) {
                $newColumn = $mapping[1]; // new_*
                
                // Chỉ lưu nếu trường này chưa được xử lý trong $changes
                if (empty($changedFields) || !in_array($field, $changedFields)) {
                    $history->$newColumn = $newCollaborator->$field;
                }
            }
        }
        
        return $history;
    }

    /**
     * Lưu thông tin đại lý vào các cột old_* và new_*
     */
    public function saveAgencyDetails($history, $oldAgencyId = null, $newAgency)
    {
        // Lưu thông tin đại lý cũ vào các cột old_*
        if ($oldAgencyId) {
            $oldAgency = Agency::find($oldAgencyId);
            if ($oldAgency) {
                $history->old_agency_name = $oldAgency->name;
                $history->old_agency_phone = $oldAgency->phone;
                $history->old_agency_address = $oldAgency->address;
                $history->old_agency_paynumber = $oldAgency->sotaikhoan;
                $history->old_agency_branch = $oldAgency->chinhanh;
                $history->old_agency_cccd = $oldAgency->cccd;
                $history->old_agency_release_date = $oldAgency->ngaycap;
            }
        }
        
        // LƯU THÔNG TIN ĐẠI LÝ MỚI (chỉ cập nhật khi có thay đổi thực sự)
        if ($newAgency) {
            $agencyFields = [
                'name' => 'new_agency_name',
                'phone' => 'new_agency_phone', 
                'address' => 'new_agency_address',
                'sotaikhoan' => 'new_agency_paynumber',
                'chinhanh' => 'new_agency_branch',
                'cccd' => 'new_agency_cccd',
                'ngaycap' => 'new_agency_release_date'
            ];
            
            foreach ($agencyFields as $field => $column) {
                $oldValue = $history->$column;
                $newValue = $newAgency->$field;
                
                // Chuyển đổi về string để so sánh
                $oldValue = is_null($oldValue) ? null : (string) $oldValue;
                $newValue = is_null($newValue) ? null : (string) $newValue;
                
                // CHỈ CẬP NHẬT KHI CÓ THAY ĐỔI THỰC SỰ
                if ($oldValue !== $newValue) {
                    $history->$column = $newValue;
                }
            }
        }
        
        return $history;
    }

    /**
     * Clear CTV data khi chọn "Đại lý lắp đặt"
     */
    public function clearCollaborator($orderCode)
    {
        try {
            // Tối ưu: chỉ ghi log khi trước đó đang có CTV thật hoặc đang không phải trạng thái "Đại lý lắp đặt".
            // Nếu chưa có log nào (lần đầu) hoặc đã là "Đại lý lắp đặt" rồi thì bỏ qua để tránh sinh record rác.
            $latest = $this->latestHistoryByOrderCode($orderCode);
            if (!$latest) {
                return true;
            }

            $isAlreadyAgency = $this->isAgencyLabel($latest->new_full_name)
                && empty($this->normalizeAgencyCollaboratorId($latest->new_collaborator_id, $latest->new_full_name));

            if ($isAlreadyAgency) {
                return true;
            }

            // Append-only: tạo bản ghi mới, old_* lấy từ bản ghi gần nhất
            $this->syncCtvSnapshotByOrderCode(
                $orderCode,
                null,
                null,
                'switch_to_agency',
                'Chuyển sang "Đại lý lắp đặt" - Xóa dữ liệu CTV'
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi clear CTV data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ghi log khi chuyển từ "Đại lý lắp đặt" về CTV
     */
    public function switchToCtv($orderCode)
    {
        try {
            // Tối ưu: KHÔNG ghi log chỉ vì bấm "chuyển về CTV".
            // Log sẽ được tạo khi người dùng thực sự chọn CTV (syncCtvSnapshotByOrderCode)
            // hoặc có thay đổi trạng thái / dữ liệu thực tế.

            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi switch to CTV: ' . $e->getMessage());
            return false;
        }
    }
}
