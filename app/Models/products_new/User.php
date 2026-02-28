<?php

namespace App\Models\products_new;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql4';
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'is_active',
        'img',
    ];

    protected $hidden = ['password', 'remember_token'];

    // Quan hệ với bảng roles (N-N)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    // Hàm kiểm tra quyền năng động
    public function hasPermission($permissionName)
    {
        foreach ($this->roles as $role) {
            // Super Admin luôn được phép (Bỏ qua check các quyền lẻ)
            if ($role->name === 'super_admin') {
                return true;
            }

            foreach ($role->permissions as $permission) {
                if ($permission->name === $permissionName) {
                    return true;
                }
            }
        }
        return false;
    }
}
