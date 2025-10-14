<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyRequestsHistory
 * 
 * @property int $id
 * @property int $warranty_id
 * @property string|null $serial_number
 * @property string|null $serial_thanmay
 * @property string|null $product
 * @property string|null $full_name
 * @property string|null $phone_number
 * @property string|null $address
 * @property string|null $staff_received
 * @property Carbon|null $received_date
 * @property Carbon|null $warranty_end
 * @property string $branch
 * @property Carbon $return_date
 * @property Carbon|null $shipment_date
 * @property string|null $initial_fault_condition
 * @property string|null $product_fault_condition
 * @property string|null $product_quantity_description
 * @property string|null $image_upload
 * @property string|null $video_upload
 * @property string|null $type
 * @property int|null $collaborator_id
 * @property string|null $collaborator_name
 * @property string|null $collaborator_phone
 * @property string|null $collaborator_address
 * @property int|null $save_img
 * @property int|null $save_video
 * @property Carbon $Ngaytao
 * @property string $status
 * @property int|null $view
 * @property int|null $print_request
 * @property int|null $request_by
 *
 * @package App\Models\KyThuat
 */
class WarrantyRequestsHistory extends Model
{
	protected $table = 'warranty_requests_history';
	public $timestamps = false;

	protected $casts = [
		'warranty_id' => 'int',
		'received_date' => 'datetime',
		'warranty_end' => 'datetime',
		'return_date' => 'datetime',
		'shipment_date' => 'datetime',
		'collaborator_id' => 'int',
		'save_img' => 'int',
		'save_video' => 'int',
		'Ngaytao' => 'datetime',
		'view' => 'int',
		'print_request' => 'int'
	];

	protected $fillable = [
		'warranty_id',
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
		'print_request',
		'request_by'
	];
}
