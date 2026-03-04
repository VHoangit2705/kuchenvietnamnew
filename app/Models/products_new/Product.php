<?php

namespace App\Models\products_new;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'product_name',
        'product_name_en',
        'slug',
        'view',
        'model',
        'name_droppii',
        'name',
        'status',
        'price',
        'price_retail',
        'month',
        'stock_vinh',
        'stock_hanoi',
        'stock_hcm',
        'print',
        'nhap_tay',
        'nhap_tay_hanoi',
        'nhap_tay_vinh',
        'nhap_tay_hcm',
        'khoa_tem',
        'khoa_tem_vinh',
        'check_seri',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function review()
    {
        return $this->hasOne(ProductBgdReview::class, 'product_id');
    }

    public function workflow()
    {
        return $this->hasOne(ProductWorkflow::class, 'product_id');
    }
}
