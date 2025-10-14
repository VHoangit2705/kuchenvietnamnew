<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyRequestDetail
 * 
 * @property int $id
 * @property int $warranty_request_id
 * @property string|null $error_type
 * @property string|null $solution
 * @property string|null $replacement
 * @property int|null $quantity
 * @property float|null $unit_price
 * @property float|null $total
 * @property Carbon $Ngaytao
 * @property string $edit_by
 * 
 * @property WarrantyRequest $warranty_request
 *
 * @package App\Models\KyThuat
 */
class WarrantyRequestDetail extends Model
{
	protected $table = 'warranty_request_details';
	public $timestamps = false;

	protected $casts = [
		'warranty_request_id' => 'int',
		'quantity' => 'int',
		'unit_price' => 'float',
		'total' => 'float',
		'Ngaytao' => 'datetime'
	];

	protected $fillable = [
		'warranty_request_id',
		'error_type',
		'solution',
		'replacement',
		'replacement_price',
		'quantity',
		'unit_price',
		'total',
		'Ngaytao',
		'edit_by'
	];

	public function warranty_request()
	{
		return $this->belongsTo(WarrantyRequest::class);
	}
	public function warrantyRequest()
	{
		return $this->belongsTo(WarrantyRequest::class,'warranty_request_id', 'id');
	}

	public static function getDetailsByRequestId($requestId)
    {
        return self::where('warranty_request_id', $requestId)->get();
    }
}
