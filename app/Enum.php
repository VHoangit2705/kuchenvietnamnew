<?php

namespace App;

enum Enum
{
    // Tên hiển thị cho checkbox "Đại lý lắp đặt"
    // Lưu ý: KHÔNG sử dụng ID cố định (ví dụ: 1) để làm flag đại lý nữa.
    // Việc đánh dấu đại lý lắp đặt đã có cơ chế riêng (RequestAgency / Agency...).
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
}
