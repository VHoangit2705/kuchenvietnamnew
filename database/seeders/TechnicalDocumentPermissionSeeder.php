<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KyThuat\Permission;
use App\Models\KyThuat\Role;

class TechnicalDocumentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo các quyền
        $permissions = [
            [
                'name' => 'technical_document.view',
                'description' => 'Xem tài liệu kỹ thuật',
            ],
            [
                'name' => 'technical_document.manage',
                'description' => 'Quản lý tài liệu kỹ thuật (Thêm/Sửa/Xóa)',
            ],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        // 2. Gán quyền cho các Vai trò
        $viewPermission = Permission::where('name', 'technical_document.view')->first();
        $managePermission = Permission::where('name', 'technical_document.manage')->first();

        // 2.1 Admin: Luôn có toàn quyền mặc định
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching([$viewPermission->id, $managePermission->id]);
        }

        // 2.2 Vai trò "Tài liệu kỹ thuật": Đây là vai trò để tick quyền trong UI bạn gửi
        $techDocRole = Role::where('name', 'Tài liệu kỹ thuật')->first();
        if ($techDocRole) {
            // Khi tick vào "Tài liệu kỹ thuật", User sẽ có quyền Quản lý (Thêm/Sửa/Xóa)
            $techDocRole->permissions()->syncWithoutDetaching([$viewPermission->id, $managePermission->id]);
        }

        // 2.3 Kỹ thuật viên & Chăm sóc khách hàng: Chỉ có quyền xem mặc định (nếu không được tick thêm vai trò Tài liệu kỹ thuật)
        $rolesToView = ['Kỹ thuật viên', 'Chăm sóc khách hàng'];
        foreach ($rolesToView as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([$viewPermission->id]);
            }
        }
    }
}
