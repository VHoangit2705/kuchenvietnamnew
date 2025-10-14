<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

/**
 * Class District
 * 
 * @property int $district_id
 * @property int $province_id
 * @property string $name
 *
 * @package App\Models\KyThuat
 */
class District extends Model
{
	protected $table = 'district';
	protected $primaryKey = 'district_id';
	public $timestamps = false;

	protected $casts = [
		'province_id' => 'int'
	];

	protected $fillable = [
		'province_id',
		'name'
	];

	public static function getByProvinceID($provinceId)
    {
        return self::where('province_id', $provinceId)->get(['district_id', 'name']);
    }
    
    public function wards()
	{
		return $this->hasMany(Wards::class, 'district_id', 'district_id');
	}
}
