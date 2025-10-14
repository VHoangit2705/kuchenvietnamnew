<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Ward
 * 
 * @property int $wards_id
 * @property int $district_id
 * @property string $name
 *
 * @package App\Models\KyThuat
 */
class Wards extends Model
{
	protected $table = 'wards';
	protected $primaryKey = 'wards_id';
	public $timestamps = false;

	protected $casts = [
		'district_id' => 'int'
	];

	protected $fillable = [
		'district_id',
		'name'
	];

	public static function getByDistrictID($districtId)
    {
        return self::where('district_id', $districtId)->get(['wards_id', 'name']);
    }
}
