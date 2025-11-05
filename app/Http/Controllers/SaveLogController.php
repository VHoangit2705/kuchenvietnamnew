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
     * Ghi lại lịch sử thay đổi cộng tác viên
     */
    public function logCollaboratorHistory($orderCode, $collaboratorId, $actionType, $fieldName = null, $oldValue = null, $newValue = null, $comments = null)
    {
        try {
            EditCtvHistory::create([
                'order_code' => $orderCode,
                'new_collaborator_id' => $collaboratorId,
                'action_type' => $actionType,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $newValue,
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
            // Tìm bản ghi hiện có để cập nhật
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
                    // Nếu đã là "Đại lý lắp đặt" rồi, đẩy giá trị "Đại lý lắp đặt" hiện tại vào old_*
                    // Đây là trường hợp chuyển từ CTV về "Đại lý lắp đặt" lần thứ 2
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
                }
                
                // Set new_* về flag "Đại lý lắp đặt"
                $existingHistory->new_collaborator_id = Enum::AGENCY_INSTALL_FLAG_ID; // Set flag khi chọn đại lý
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
                
                $existingHistory->action_type = 'switch_to_agency';
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                $existingHistory->comments = 'Chuyển sang "Đại lý lắp đặt" - Xóa dữ liệu CTV';
                
                $existingHistory->save();
            } else {
                // Tạo bản ghi mới
                $this->logCollaboratorHistory(
                    $orderCode,
                    null,
                    'switch_to_agency',
                    null,
                    null,
                    null,
                    'Chuyển sang "Đại lý lắp đặt" - Xóa dữ liệu CTV'
                );
            }

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
            // Tìm bản ghi hiện có để cập nhật
            $existingHistory = EditCtvHistory::where('order_code', $orderCode)->first();
            
            if ($existingHistory) {
                // Kiểm tra xem dữ liệu hiện tại có phải là flag "Đại lý lắp đặt" không
                $isAgency = $existingHistory->new_full_name == Enum::AGENCY_INSTALL_CHECKBOX_LABEL && 
                           $existingHistory->new_collaborator_id == Enum::AGENCY_INSTALL_FLAG_ID;
                
                if ($isAgency) {
                    // Nếu đang là "Đại lý lắp đặt", đẩy dữ liệu "Đại lý lắp đặt" vào old_*
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
                    // Nếu đang là CTV thật, đẩy dữ liệu CTV hiện tại vào old_*
                    // Đây là trường hợp chuyển từ "Đại lý lắp đặt" về CTV lần thứ 2
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
                }
                
                // LƯU THÔNG TIN ĐẠI LÝ HIỆN TẠI VÀO OLD_* TRƯỚC KHI CHUYỂN VỀ CTV
                // Đẩy thông tin đại lý hiện tại (new_agency_*) vào old_agency_*
                $existingHistory->old_agency_name = $existingHistory->new_agency_name;
                $existingHistory->old_agency_phone = $existingHistory->new_agency_phone;
                $existingHistory->old_agency_address = $existingHistory->new_agency_address;
                $existingHistory->old_agency_paynumber = $existingHistory->new_agency_paynumber;
                $existingHistory->old_agency_branch = $existingHistory->new_agency_branch;
                $existingHistory->old_agency_cccd = $existingHistory->new_agency_cccd;
                $existingHistory->old_agency_release_date = $existingHistory->new_agency_release_date;
                
                // Set thông tin đại lý mới về null (vì chuyển về CTV)
                $existingHistory->new_agency_name = null;
                $existingHistory->new_agency_phone = null;
                $existingHistory->new_agency_address = null;
                $existingHistory->new_agency_paynumber = null;
                $existingHistory->new_agency_branch = null;
                $existingHistory->new_agency_cccd = null;
                $existingHistory->new_agency_release_date = null;
                
                // Cập nhật bản ghi hiện có - khôi phục dữ liệu CTV từ old_* về new_*
                $existingHistory->new_collaborator_id = $existingHistory->old_collaborator_id;
                
                // Khôi phục dữ liệu CTV từ old_* về new_*
                $existingHistory->new_full_name = $existingHistory->old_full_name;
                $existingHistory->new_phone = $existingHistory->old_phone;
                $existingHistory->new_province = $existingHistory->old_province;
                $existingHistory->new_province_id = $existingHistory->old_province_id;
                $existingHistory->new_district = $existingHistory->old_district;
                $existingHistory->new_district_id = $existingHistory->old_district_id;
                $existingHistory->new_ward = $existingHistory->old_ward;
                $existingHistory->new_ward_id = $existingHistory->old_ward_id;
                $existingHistory->new_address = $existingHistory->old_address;
                $existingHistory->new_sotaikhoan = $existingHistory->old_sotaikhoan;
                $existingHistory->new_chinhanh = $existingHistory->old_chinhanh;
                $existingHistory->new_cccd = $existingHistory->old_cccd;
                $existingHistory->new_ngaycap = $existingHistory->old_ngaycap;
                
                $existingHistory->action_type = 'switch_to_ctv';
                $existingHistory->edited_by = session('user', 'system');
                $existingHistory->edited_at = now();
                $existingHistory->comments = 'Chuyển từ "Đại lý lắp đặt" về CTV - Lưu lịch sử đại lý';
                
                $existingHistory->save();
            } else {
                // Tạo bản ghi mới nếu chưa có
                $this->logCollaboratorHistory(
                    $orderCode,
                    null,
                    'switch_to_ctv',
                    null,
                    null,
                    null,
                    'Chuyển từ "Đại lý lắp đặt" về CTV'
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi switch to CTV: ' . $e->getMessage());
            return false;
        }
    }
}
