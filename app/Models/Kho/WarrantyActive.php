<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class WarrantyActive
 * 
 * @property int $id
 * @property string|null $serial
 * @property string|null $product
 * @property string|null $product_image
 * @property string|null $full_name
 * @property string|null $phone_number
 * @property string|null $address
 * @property Carbon|null $shipment_date
 * @property Carbon|null $active_date
 * @property Carbon|null $warranty_end
 * @property int|null $view
 * @property string|null $zns_response
 *
 * @package App\Models\Kho
 */
class WarrantyActive extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'warranty_active';
	public $timestamps = false;

	protected $casts = [
		'shipment_date' => 'datetime',
		'active_date' => 'datetime',
		'warranty_end' => 'datetime',
		'view' => 'int'
	];

	protected $fillable = [
		'serial',
		'product',
		'product_image',
		'full_name',
		'phone_number',
		'address',
		'shipment_date',
		'active_date',
		'warranty_end',
		'view',
		'zns_response'
	];

	public function productWarranty()
	{
		return $this->belongsTo(ProductWarranty::class, 'serial', 'warranty_code');
	}

	public static function getProductInfo()
	{
		// return DB::connection('mysql3')->table('warranty_active as wa')
		// 	->join('product_warranties as pw', 'wa.serial', '=', 'pw.warranty_code')
		// 	->join('order_products as op', 'pw.order_product_id', '=', 'op.id')
		// 	->join('products as p', 'op.product_name', '=', 'p.product_name')
		// 	->select(
		// 		'wa.id',
		// 		'wa.shipment_date',
		// 		'p.product_name',
		// 		'p.month',
		// 		'p.image',
		// 		'p.view'
		// 		)
		// 	// ->skip(10000)
		// 	->take(5000)->get();
		
		return DB::connection('mysql3')->table('warranty_active as wa')
			->join('serial_numbers as sn', 'wa.serial', '=', 'sn.sn')
			->join('products as p', 'sn.product_name', '=', 'p.product_name')
			->select(
				'wa.id',
				'wa.shipment_date',
				'p.product_name',
				'p.month',
				'p.image',
				'p.view'
			)
			->where('wa.view', 0)->take(5000)
			->get();
	}
}
