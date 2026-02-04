<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;
use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\WarrantyRepairJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyRequest
 * 
 * @property int $id
 * @property string|null $serial_number
 * @property string|null $serial_thanmay
 * @property string|null $product
 * @property string|null $full_name
 * @property string|null $phone_number
 * @property string|null $address
 * @property string|null $staff_received
 * @property Carbon|null $received_date
 * @property string|null $branch
 * @property Carbon|null $return_date
 * @property Carbon|null $shipment_date
 * @property string|null $initial_fault_condition
 * @property string|null $product_fault_condition
 * @property string|null $product_quantity_description
 * @property string|null $image_upload
 * @property string|null $video_upload
 * @property string|null $type
 * @property int|null $save_img
 * @property int|null $save_video
 * @property Carbon $Ngaytao
 * @property string|null $status
 * @property int|null $view
 * 
 * @property Collection|WarrantyRequestDetail[] $warranty_request_details
 *
 * @package App\Models\KyThuat
 */
class WarrantyRequest extends Model
{
	protected $table = 'warranty_requests';
	public $timestamps = false;

	protected $casts = [
		'received_date' => 'datetime',
		'return_date' => 'datetime',
		'shipment_date' => 'datetime',
		'save_img' => 'int',
		'save_video' => 'int',
		'Ngaytao' => 'datetime',
		'view' => 'int'
	];

	protected $fillable = [
		'serial_number',
		'serial_thanmay',
		'product',
		'full_name',
		'phone_number',
		'address',
		'staff_received',
		'received_date',
		'warranty_end',
		'branch',
		'return_date',
		'shipment_date',
		'initial_fault_condition',
		'product_fault_condition',
		'product_quantity_description',
		'image_upload',
		'video_upload',
		'type',
		'collaborator_id',
		'collaborator_name',
		'collaborator_phone',
		'collaborator_address',
		'save_img',
		'save_video',
		'Ngaytao',
		'status',
		'view',
		'province_id',
		'district_id',
		'ward_id',
		'install_cost',
		'status_install',
		'reviews_install',
		'agency_name',
		'agency_phone',
		'successed_at'
	];

	public function warranty_request_details()
	{
		return $this->hasMany(WarrantyRequestDetail::class);
	}

	public function details()
	{
		return $this->hasMany(WarrantyRequestDetail::class, 'warranty_request_id', 'id');
	}

	public static function getListWarranty($conditions, $productInput)
	{
		return self::query()
			->join('warranty_request_details', 'warranty_requests.id', '=', 'warranty_request_details.warranty_request_id')
			->select(
				'warranty_requests.id',
				'warranty_requests.serial_number',
				'warranty_requests.serial_thanmay',
				'warranty_requests.product',
				'warranty_requests.branch',
				'warranty_requests.full_name',
				'warranty_requests.phone_number',
				'warranty_requests.staff_received',
				'warranty_requests.received_date',
				'warranty_requests.warranty_end',
				'warranty_requests.shipment_date',
				'warranty_requests.initial_fault_condition',
				'warranty_request_details.replacement',
				'warranty_request_details.solution',
				'warranty_request_details.total',
				'warranty_request_details.replacement_price',
				'warranty_request_details.quantity'
			)
			->where($conditions)
			->when($productInput, function ($query, $productInput) {
				$query->where(function ($q) use ($productInput) {
					$q->where('warranty_requests.serial_number', 'LIKE', '%' . $productInput . '%')
					  ->orWhere('warranty_requests.product', 'LIKE', '%' . $productInput . '%');
				});
			})
			->orderBy('warranty_requests.received_date', 'desc')
			->get();
	}
	
	public function collaborator()
	{
		return $this->belongsTo(WarrantyCollaborator::class, 'collaborator_id');
	}
	
	
	public function repairJobs()
	{
		return $this->hasMany(WarrantyRepairJob::class, 'warranty_request_id', 'id');
	}
}
