<?php

namespace App\Models\products_new;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    // Quan hệ với bảng permissions (N-N)
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    // Quan hệ ngược lại với User
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }
}
