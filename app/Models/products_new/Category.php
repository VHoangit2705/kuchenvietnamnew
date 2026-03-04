<?php

namespace App\Models\products_new;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'categories';

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
