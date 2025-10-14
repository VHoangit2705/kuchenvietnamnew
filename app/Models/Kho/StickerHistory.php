<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StickerHistory
 * 
 * @property int $id
 * @property string $product_name
 * @property string $event_type
 * @property string|null $from_zone
 * @property string $to_zone
 * @property int $quantity
 * @property string $operator
 * @property Carbon $created_at
 *
 * @package App\Models\Kho
 */
class StickerHistory extends Model
{
	protected $table = 'sticker_history';
	public $timestamps = false;

	protected $casts = [
		'quantity' => 'int'
	];

	protected $fillable = [
		'product_name',
		'event_type',
		'from_zone',
		'to_zone',
		'quantity',
		'operator'
	];
}
