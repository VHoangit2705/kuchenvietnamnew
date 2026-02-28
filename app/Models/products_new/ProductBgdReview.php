<?php

namespace App\Models\products_new;

use Illuminate\Database\Eloquent\Model;

class ProductBgdReview extends Model
{
    protected $connection = 'mysql4';
    protected $table = 'product_bgd_reviews';

    protected $fillable = [
        'product_id',
        'approved_product_name',
        'note_for_training',
        'note_for_marketing',
        'co_cq_files',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'co_cq_files' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the product associated with the review.
     * Note: Product is in mysql3 database.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
