<?php

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'categories';
    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'website_id',
        'name_vi',
        'name_en',
        'slug',
        'image',
        'description',
        'sort_order',
        'status',
        'created_at',
        'updated_at',
    ];

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }
}

