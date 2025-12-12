<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Agency
 * 
 * @property int $id
 * @property string|null $name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $sotaikhoan
 * @property string|null $chinhanh
 * @property string|null $cccd
 * @property string|null $ngaycap
 * @property Carbon|null $created_ad
 * @property string|null $create_by
 *
 * @package App\Models\Kho
 */
class Agency extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'agency';
	public $timestamps = false;

	protected $casts = [
		'created_ad' => 'datetime'
	];

	protected $fillable = [
		'name',
		'address',
		'phone',
		'bank_name_agency',
		'sotaikhoan',
		'chinhanh',
		'cccd',
		'ngaycap',
		'created_ad',
		'create_by'
	];
	
	public function installationOrders()
	{
		return $this->hasMany(InstallationOrder::class, 'agency_phone', 'phone');
	}

	/**
	 * Quan hệ: Agency hasMany UserAgency
	 * Một đại lý có nhiều user đại lý
	 */
	public function userAgencies()
	{
		return $this->hasMany(UserAgency::class, 'agency_id', 'id');
	}
}
