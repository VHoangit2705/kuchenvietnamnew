<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SerialNumber
 * 
 * @property int $id
 * @property string $sn
 * @property int $product_id
 * @property string $product_name
 * @property int $manhaphang
 * @property Carbon|null $created_at
 * 
 * @property Product $product
 *
 * @package App\Models\Kho
 */
class SerialNumber extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'serial_numbers';
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'manhaphang' => 'int'
	];

	protected $fillable = [
		'sn',
		'product_id',
		'product_name',
		'manhaphang'
	];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
