<?php

namespace App;

enum Enum
{
    // Constants cho Agency Installation Flag
    // ID = 1 được dùng làm flag để đánh dấu đơn hàng được giao cho đại lý thay vì CTV
    const AGENCY_INSTALL_FLAG_ID = 1;
    
    // Tên hiển thị cho checkbox "Đại lý lắp đặt"
    const AGENCY_INSTALL_CHECKBOX_LABEL = 'Đại lý lắp đặt';

    public static function getCodes(): array
    {
        return ['2025050500'];
    }

    public static function getInstallAgency(): array 
    {
        return [
            self::AGENCY_INSTALL_CHECKBOX_LABEL => self::AGENCY_INSTALL_CHECKBOX_LABEL,
        ];
    }

    /**
     * Kiểm tra xem collaborator_id có phải là Agency Installation flag không
     * (tức là đơn hàng được giao cho đại lý thay vì CTV)
     */
    public static function isAgencyInstallFlag($collaboratorId): bool
    {
        return $collaboratorId == self::AGENCY_INSTALL_FLAG_ID;
    }

    /**
     * Kiểm tra xem collaborator_id có phải là CTV thật không
     */
    public static function isRealCollaborator($collaboratorId): bool
    {
        return $collaboratorId != self::AGENCY_INSTALL_FLAG_ID;
    }
}
