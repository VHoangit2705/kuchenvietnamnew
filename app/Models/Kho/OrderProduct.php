<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderProduct
 * 
 * @property int $id
 * @property int|null $order_id
 * @property string|null $product_name
 * @property int|null $quantity
 * @property int $excluding_VAT
 * @property string $VAT
 * @property int $VAT_price
 * @property int|null $price
 * @property int $price_difference
 * @property string $sub_address
 * @property bool|null $is_promotion
 * @property int $warranty_scan
 * 
 * @property Order|null $order
 * @property Collection|ProductWarranty[] $product_warranties
 *
 * @package App\Models\Kho
 */
class OrderProduct extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'order_products';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'quantity' => 'int',
		'excluding_VAT' => 'int',
		'VAT_price' => 'int',
		'price' => 'int',
		'price_difference' => 'int',
		'is_promotion' => 'bool',
		'warranty_scan' => 'int',
		'install'
	];

	protected $fillable = [
		'order_id',
		'product_name',
		'quantity',
		'excluding_VAT',
		'VAT',
		'VAT_price',
		'price',
		'price_difference',
		'sub_address',
		'is_promotion',
		'warranty_scan',
		'install'
	];

	public function order()
	{
		return $this->belongsTo(Order::class, 'order_id');
	}

	public function product_warranties()
	{
		return $this->hasMany(ProductWarranty::class);
	}
}
