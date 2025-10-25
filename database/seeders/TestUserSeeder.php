<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KyThuat\User; // Đảm bảo đúng namespace tới model User
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa user cũ đi nếu có (để tránh trùng)
        User::where('full_name', 'Admin Test')->delete();

        // Tạo user mới
        User::create([
            'full_name' => 'Admin Test',
            'password' => md5('123456'), // Dùng md5 GIỐNG HỆT như trong loginController
            'position' => 'Admin',
            'zone' => 'KTV'
        ]);
    }
}