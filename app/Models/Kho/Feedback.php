<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Feedback
 * 
 * @property int $id
 * @property int $order_id
 * @property int $delivery_person_id
 * @property int $rating
 * @property string|null $comment
 * @property string $tc1
 * @property string $tc2
 * @property string $tc3
 * @property string $tc4
 * @property string $tc5
 * @property Carbon $feedback_time
 * @property string $ip
 * @property string $mac
 * 
 * @property Order $order
 * @property User $user
 *
 * @package App\Models\Kho
 */
class Feedback extends Model
{
	protected $table = 'feedbacks';
	public $timestamps = false;

	protected $casts = [
		'order_id' => 'int',
		'delivery_person_id' => 'int',
		'rating' => 'int',
		'feedback_time' => 'datetime'
	];

	protected $fillable = [
		'order_id',
		'delivery_person_id',
		'rating',
		'comment',
		'tc1',
		'tc2',
		'tc3',
		'tc4',
		'tc5',
		'feedback_time',
		'ip',
		'mac'
	];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'delivery_person_id');
	}
}
