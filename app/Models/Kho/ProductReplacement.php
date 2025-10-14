<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductReplacement
 * 
 * @property int $id
 * @property int $product_id
 * @property int $replacement_id
 * 
 * @property Product $product
 *
 * @package App\Models\Kho
 */
class ProductReplacement extends Model
{
	protected $table = 'product_replacements';
	public $timestamps = false;

	protected $casts = [
		'product_id' => 'int',
		'replacement_id' => 'int'
	];

	protected $fillable = [
		'product_id',
		'replacement_id'
	];

	public function product()
	{
		return $this->belongsTo(Product::class, 'replacement_id');
	}
}
