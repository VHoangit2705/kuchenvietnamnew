<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\KyThuat\WarrantyCollaborator;

/**
 * Class Order
 * 
 * @property int $id
 * @property string|null $order_code1
 * @property string|null $order_code2
 * @property string|null $customer_name
 * @property string|null $customer_phone
 * @property string|null $customer_address
 * @property string|null $agency_name
 * @property string|null $agency_phone
 * @property int|null $discount_code
 * @property int|null $total_price
 * @property string $payment_method
 * @property Carbon $created_at
 * @property string $note
 * @property string $note_admin
 * @property string $status
 * @property string $status_tracking
 * @property string $staff
 * @property string $zone
 * @property string $type
 * @property string $shipping_unit
 * @property Carbon|null $lock_timestamp
 * @property int $send_camon
 * @property int $send_khbh
 * @property string $ip_rate
 * @property Carbon|null $notif_date
 * 
 * @property Collection|Feedback[] $feedback
 * @property Collection|OrderProduct[] $order_products
 *
 * @package App\Models\Kho
 */
class Order extends Model
{
    protected $connection = 'mysql3';
	protected $table = 'orders';
	public $timestamps = false;

	protected $casts = [
		'discount_code' => 'int',
		'total_price' => 'int',
		'lock_timestamp' => 'datetime',
		'send_camon' => 'int',
		'send_khbh' => 'int',
		'notif_date' => 'datetime',
		'check_return' => 'int'
	];

	protected $fillable = [
		'order_code1',
		'order_code2',
		'customer_name',
		'customer_phone',
		'customer_address',
		'province',
		'district',
		'wards',
		'agency_name',
		'agency_phone',
		'discount_code',
		'total_price',
		'payment_method',
		'note',
		'note_admin',
		'status',
		'status_tracking',
		'staff',
		'zone',
		'type',
		'shipping_unit',
		'lock_timestamp',
		'send_camon',
		'send_khbh',
		'ip_rate',
		'notif_date',
		'collaborator_id',
		'install_cost',
		'successed_at',
		'check_return'
	];

	public function feedback()
	{
		return $this->hasMany(Feedback::class);
	}

	public function order_products()
	{
		return $this->hasMany(OrderProduct::class);
	}
	
	public function getCollaboratorAttribute()
	{
		return WarrantyCollaborator::on('mysql') 
			->find($this->collaborator_id);
	}
}
