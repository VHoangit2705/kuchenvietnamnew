<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderTracking
 * 
 * @property int $id
 * @property string $order_code
 * @property string $order_id
 * @property Carbon $created_date
 * @property string $sender
 * @property string $status
 * @property string $payment_status
 * @property Carbon|null $date_status
 *
 * @package App\Models\Kho
 */
class OrderTracking extends Model
{
	protected $table = 'order_tracking';
	public $timestamps = false;

	protected $casts = [
		'created_date' => 'datetime',
		'date_status' => 'datetime'
	];

	protected $fillable = [
		'order_code',
		'order_id',
		'created_date',
		'sender',
		'status',
		'payment_status',
		'date_status'
	];
}
