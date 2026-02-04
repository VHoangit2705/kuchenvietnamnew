<?php

namespace App;

enum Enum
{
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

}