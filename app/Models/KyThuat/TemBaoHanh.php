<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\KyThuat;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TemBaoHanh
 * 
 * @property int $id
 * @property string $ten_san_pham
 * @property string|null $serial
 * @property string|null $ma_pin
 * @property Carbon|null $ngay_nhap_kho
 * @property Carbon|null $ngay_kich_hoat
 * @property Carbon|null $han_bao_hanh
 * @property bool|null $trang_thai
 *
 * @package App\Models\KyThuat
 */
class TemBaoHanh extends Model
{
	protected $table = 'tem_bao_hanh';
	public $timestamps = false;

	protected $casts = [
		'ngay_nhap_kho' => 'datetime',
		'ngay_kich_hoat' => 'datetime',
		'han_bao_hanh' => 'datetime',
		'trang_thai' => 'bool'
	];

	protected $fillable = [
		'ten_san_pham',
		'serial',
		'ma_pin',
		'ngay_nhap_kho',
		'ngay_kich_hoat',
		'han_bao_hanh',
		'trang_thai'
	];
}
