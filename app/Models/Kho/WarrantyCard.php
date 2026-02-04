<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kho\SerialNumber;
use App\Models\Kho\ProductWarranty;

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

	/**
	 * Tính số lượng tem đã sử dụng (đã kích hoạt)
	 * Chỉ tính khi chưa có giá trị từ query (tránh N+1 query)
	 */
	public function getUsedCountAttribute()
	{
		// Nếu đã có giá trị từ query (được tính sẵn trong SELECT), dùng giá trị đó
		// Kiểm tra cả attributes và original vì Laravel có thể lưu ở cả hai nơi
		if (isset($this->attributes['used_count'])) {
			return (int) $this->attributes['used_count'];
		}
		if (isset($this->original['used_count'])) {
			return (int) $this->original['used_count'];
		}
		
		// Chỉ tính khi không có giá trị từ query (fallback)
		$serialNumbers = SerialNumber::where('manhaphang', $this->id)->pluck('sn')->toArray();
		if (empty($serialNumbers)) {
			return 0;
		}
		// Đếm số tem đã kích hoạt trong bảng product_warranties
		// Kiểm tra warranty_code có tồn tại trong sn của serial_numbers
		return ProductWarranty::whereIn('warranty_code', $serialNumbers)->count();
	}

	/**
	 * Tính số lượng tem còn lại
	 * Chỉ tính khi chưa có giá trị từ query (tránh N+1 query)
	 */
	public function getRemainingCountAttribute()
	{
		// Nếu đã có giá trị từ query (được tính sẵn trong SELECT), dùng giá trị đó
		// Kiểm tra cả attributes và original vì Laravel có thể lưu ở cả hai nơi
		if (isset($this->attributes['remaining_count'])) {
			return (int) $this->attributes['remaining_count'];
		}
		if (isset($this->original['remaining_count'])) {
			return (int) $this->original['remaining_count'];
		}
		
		// Chỉ tính khi không có giá trị từ query (fallback)
		return $this->quantity - $this->used_count;
	}

	/**
	 * Lấy số ngày đã trôi qua từ khi tạo phiếu
	 */
	public function getDaysPassedAttribute()
	{
		if (isset($this->attributes['days_passed'])) {
			return (int) $this->attributes['days_passed'];
		}
		if (isset($this->original['days_passed'])) {
			return (int) $this->original['days_passed'];
		}
		
		if (!$this->create_at) {
			return 0;
		}
		
		return max(1, (int) $this->create_at->diffInDays(now()));
	}

	/**
	 * Lấy tốc độ sử dụng tem (số tem/ngày)
	 */
	public function getUsageRateAttribute()
	{
		if (isset($this->attributes['usage_rate'])) {
			return (float) $this->attributes['usage_rate'];
		}
		if (isset($this->original['usage_rate'])) {
			return (float) $this->original['usage_rate'];
		}
		
		$daysPassed = $this->days_passed;
		if ($daysPassed <= 0) {
			return 0;
		}
		
		return $this->used_count / $daysPassed;
	}

	/**
	 * Lấy số ngày còn lại dự đoán dựa trên tốc độ sử dụng
	 */
	public function getDaysRemainingAttribute()
	{
		if (isset($this->attributes['days_remaining'])) {
			return $this->attributes['days_remaining'] !== null ? (float) $this->attributes['days_remaining'] : null;
		}
		if (isset($this->original['days_remaining'])) {
			return $this->original['days_remaining'] !== null ? (float) $this->original['days_remaining'] : null;
		}
		
		$usageRate = $this->usage_rate;
		if ($usageRate <= 0) {
			return null; // Chưa có dữ liệu để dự đoán
		}
		
		$remaining = $this->remaining_count;
		return $remaining / $usageRate;
	}

	/**
	 * Kiểm tra có cần cảnh báo không (còn < 5 ngày)
	 */
	public function getShouldWarnAttribute()
	{
		$daysRemaining = $this->days_remaining;
		if ($daysRemaining === null) {
			return false; // Chưa có dữ liệu để dự đoán
		}
		
		return $daysRemaining < 5 && $daysRemaining > 0;
	}
}
