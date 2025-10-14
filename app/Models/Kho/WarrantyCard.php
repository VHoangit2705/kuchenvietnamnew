<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarrantyCard
 * 
 * @property int $id
 * @property string|null $product
 * @property int|null $quantity
 * @property string|null $create_by
 * @property Carbon|null $create_at
 *
 * @package App\Models\Kho
 */
class WarrantyCard extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'warranty_card';
	public $timestamps = false;

	protected $casts = [
		'quantity' => 'int',
		'create_at' => 'datetime'
	];

	protected $fillable = [
		'product',
		'product_id',
		'view',
		'type',
		'quantity',
		'create_by',
		'create_at'
	];

	public static function GetList($view)
	{
		return self::where('view', $view)->orderByDesc('id');
	}
	public static function GetWarrantyCardByID($id)
	{
		return self::find($id);
	}
}
