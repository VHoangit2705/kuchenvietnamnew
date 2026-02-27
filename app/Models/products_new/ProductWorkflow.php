<?php

namespace App\Models\products_new;

use App\Models\Kho\Product;
use Illuminate\Database\Eloquent\Model;

class ProductWorkflow extends Model
{
    protected $connection = 'mysql4';
    protected $table = 'product_workflows';

    protected $fillable = [
        'product_id',
        'current_step',
        'department_assigned',
        'status',
        'reviewer_notes',
    ];

    /**
     * Quan hệ với sản phẩm (mysql3).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
