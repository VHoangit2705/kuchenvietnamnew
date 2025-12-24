<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use App\Enum;
use App\Models\KyThuat\WarrantyCollaborator;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InstallationOrder
 * 
 * @property int $id
 * @property string|null $order_code
 * @property string|null $customer_name
 * @property string|null $customer_phone
 * @property string|null $collaborator_address
 * @property string|null $product
 * @property int|null $province_id
 * @property int|null $district_id
 * @property int|null $ward_id
 * @property int|null $order_id
 * @property int|null $collaborator_id
 * @property int|null $install_cost
 * @property int|null $status
 * @property string|null $file_review
 * @property string|null $agency_name
 * @property string|null $agency_address
 * @property string|null $agency_phone
 * @property string|null $agency_payment
 *
 * @package App\Models\Kho
 */
class InstallationOrder extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'installation_orders';
	public $timestamps = false;

	protected $casts = [
		'province_id' => 'int',
		'district_id' => 'int',
		'ward_id' => 'int',
		'order_id' => 'int',
		'collaborator_id' => 'int',
		'install_cost' => 'int',
		'status_install' => 'int',
        'agency_id' => 'int',
	];

	protected $fillable = [
		'order_code',
		'full_name',
		'phone_number',
		'address',
		'product',
		'province_id',
		'district_id',
		'ward_id',
		'order_id',
		'collaborator_id',
		'install_cost',
		'status_install',
		'reviews_install',
		'agency_name',
		'agency_address',
		'agency_phone',
		'agency_payment',
		'type',
		'zone',
		'created_at',
		'successed_at',
		'dispatched_at',
		'paid_at',
		'agency_at',
        'agency_id',
	];

	public function getCollaboratorAttribute()
	{
		$collaboratorId = $this->collaborator_id;

		if (empty($collaboratorId) || Enum::isAgencyInstallFlag($collaboratorId)) {
			return null;
		}

		return WarrantyCollaborator::on('mysql')
			->find($collaboratorId);
	}
	
	public function agency()
	{
		return $this->belongsTo(Agency::class, 'agency_phone', 'phone');
	}
}
