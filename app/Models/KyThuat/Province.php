<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Province
 * 
 * @property int $province_id
 * @property string $name
 *
 * @package App\Models\KyThuat
 */
class Province extends Model
{
	protected $table = 'province';
	protected $primaryKey = 'province_id';
	public $timestamps = false;

	protected $fillable = [
		'name'
	];
	
	public function districts()
	{
		return $this->hasMany(District::class, 'province_id', 'province_id');
	}
}
