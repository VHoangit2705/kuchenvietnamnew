<?php

namespace App\Models\products_new;

use App\Models\Kho\Product;
use Illuminate\Database\Eloquent\Model;

class ProductDetails extends Model
{
    protected $connection = 'mysql4';
    protected $table = 'product_details';

    protected $fillable = [
        'product_id',
        'description',
        'tech_specs',
        'features',
        'user_guide',
        'created_by',
    ];

    /**
     * Quan hệ với sản phẩm (mysql3).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
