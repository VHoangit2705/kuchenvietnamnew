<?php

namespace App\Models\products_new;

use App\Models\Kho\Product;
use App\Models\products_new\User;
use Illuminate\Database\Eloquent\Model;

class ProductContentReview extends Model
{
    protected $connection = 'mysql4';
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('database.connections.mysql4.database') . '.product_content_reviews';
    }

    protected $fillable = [
        'product_id',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the product associated with the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the user who reviewed the content.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
