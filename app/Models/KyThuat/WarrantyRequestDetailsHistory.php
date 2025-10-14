<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyRequestDetailsHistory
 * 
 * @property int $id
 * @property int|null $warranty_request_history_id
 * @property int|null $warranty_request_id
 * @property string|null $error_type
 * @property string|null $solution
 * @property string|null $replacement
 * @property float|null $replacement_price
 * @property int|null $quantity
 * @property float|null $unit_price
 * @property float|null $total
 * @property Carbon $Ngaytao
 * @property string|null $edit_by
 *
 * @package App\Models\KyThuat
 */
class WarrantyRequestDetailsHistory extends Model
{
	protected $table = 'warranty_request_details_history';
	public $timestamps = false;

	protected $casts = [
		'warranty_request_history_id' => 'int',
		'warranty_request_id' => 'int',
		'replacement_price' => 'float',
		'quantity' => 'int',
		'unit_price' => 'float',
		'total' => 'float',
		'Ngaytao' => 'datetime'
	];

	protected $fillable = [
		'warranty_request_history_id',
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
}
