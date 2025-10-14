<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PrintOrder
 * 
 * @property int $id
 * @property string $product_name
 * @property string $zone
 * @property int $quantity
 * @property string $operator
 * @property string|null $status
 * @property Carbon $created_at
 *
 * @package App\Models\Kho
 */
class PrintOrder extends Model
{
	protected $table = 'print_orders';
	public $timestamps = false;

	protected $casts = [
		'quantity' => 'int'
	];

	protected $fillable = [
		'product_name',
		'zone',
		'quantity',
		'operator',
		'status'
	];
}
