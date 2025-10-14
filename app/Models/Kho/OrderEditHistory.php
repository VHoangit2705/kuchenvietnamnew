<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderEditHistory
 * 
 * @property int $id
 * @property int $order_id
 * @property string $action_type
 * @property int|null $product_id
 * @property string|null $product_name
 * @property int|null $quantity
 * @property int|null $price
 * @property string $edited_by
 * @property string|null $comments
 * @property Carbon $edited_at
 *
 * @package App\Models\Kho
 */
class OrderEditHistory extends Model
{
	protected $table = 'order_edit_history';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'product_id' => 'int',
		'quantity' => 'int',
		'price' => 'int',
		'edited_at' => 'datetime'
	];

	protected $fillable = [
		'order_id',
		'action_type',
		'product_id',
		'product_name',
		'quantity',
		'price',
		'edited_by',
		'comments',
		'edited_at'
	];
}
