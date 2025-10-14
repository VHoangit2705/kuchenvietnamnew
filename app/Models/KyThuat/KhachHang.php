<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;

/**
 * Class KhachHang
 * 
 * @property int $id
 * @property string $ho_ten
 * @property string|null $dia_chi
 * @property string|null $so_dien_thoai
 * @property string|null $serial_no
 *
 * @package App\Models\KyThuat
 */
class KhachHang extends Model
{
	protected $table = 'khach_hang';
	public $timestamps = false;

	protected $fillable = [
		'ho_ten',
		'dia_chi',
		'so_dien_thoai',
		'serial'
	];
}
