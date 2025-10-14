<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductWarranty
 * 
 * @property int $id
 * @property int|null $order_product_id
 * @property string|null $warranty_code
 * @property Carbon $created_at
 * @property string $name
 * 
 * @property OrderProduct|null $order_product
 *
 * @package App\Models\Kho
 */
class ProductWarranty extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'product_warranties';
	public $timestamps = false;

	protected $casts = [
		'order_product_id' => 'int'
	];

	protected $fillable = [
		'order_product_id',
		'warranty_code',
		'name'
	];

	public function order_product()
	{
		return $this->belongsTo(OrderProduct::class, 'order_product_id');
	}
}
