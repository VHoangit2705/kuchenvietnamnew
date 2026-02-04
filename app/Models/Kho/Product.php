<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 * 
 * @property int $id
 * @property string $product_name
 * @property float $price
 * @property string $name
 * @property string $exp
 * @property int $month
 * @property string $model
 * @property int $stock_vinh
 * @property int $stock_hanoi
 * @property int $stock_hcm
 * @property int $print
 * @property int $nhap_tay
 * @property int $khoa_tem
 * @property string $Ma_ERP
 * @property int $view
 * @property int|null $category_id
 * @property int $check_seri
 * @property int|null $reminder_time
 * 
 * @property Collection|ProductReplacement[] $product_replacements
 * @property Collection|SerialNumber[] $serial_numbers
 *
 * @package App\Models\Kho
 */
class Product extends Model
{
	protected $connection = 'mysql3';
	protected $table = 'products';
	public $timestamps = false;

	protected $casts = [
		'price' => 'float',
		'month' => 'int',
		'stock_vinh' => 'int',
		'stock_hanoi' => 'int',
		'stock_hcm' => 'int',
		'print' => 'int',
		'nhap_tay' => 'int',
		'khoa_tem' => 'int',
		'view' => 'int',
		'check_seri' => 'int',
		'reminder_time' => 'int',
		'install'
	];

	protected $fillable = [
		'product_name',
		'price',
		'name',
		'exp',
		'month',
		'model',
		'stock_vinh',
		'stock_hanoi',
		'stock_hcm',
		'print',
		'nhap_tay',
		'khoa_tem',
		'Ma_ERP',
		'view',
		'category_id',
		'check_seri',
		'reminder_time',
		'install'
	];

	public function product_replacements()
	{
		return $this->hasMany(ProductReplacement::class, 'replacement_id');
	}

	public function serial_numbers()
	{
		return $this->hasMany(SerialNumber::class);
	}
	
	/**
	 * Relationship với categories qua bảng product_categories
	 */
	public function categories()
	{
		return $this->belongsToMany(
			Category::class,
			'product_categories',
			'product_id',
			'category_id'
		)->where('categories.website_id', 2);
	}

	/**
	 * Lấy category đầu tiên của sản phẩm
	 */
	public function getCategoryAttribute()
	{
		return $this->categories()->first();
	}

	public static function getListProduct($view)
	{
		return self::where('view',  $view) 
        ->get(['id', 'product_name', 'month', 'price', 'view']);
	}

	public static function getProductByName($productName)
	{
		return self::where('product_name', $productName)->first();
	}
	
	public static function getViewById($id){
		return self::where('id', $id)->value('view');
	}

	/**
	 * Lấy sản phẩm theo danh mục (ưu tiên category_id, không có thì qua bảng product_categories).
	 * Trả về collection với id, name (product_name), model.
	 */
	public static function getProductsByCategoryId(int $categoryId, int $view = 1)
	{
		$base = self::where('view', $view);

		$byCategoryId = (clone $base)->where('category_id', $categoryId)
			->get(['id', 'product_name as name', 'model']);

		if ($byCategoryId->isNotEmpty()) {
			return $byCategoryId;
		}

		return $base->whereHas('categories', function ($q) use ($categoryId) {
			$q->where('categories.id', $categoryId);
		})->get(['id', 'product_name as name', 'model']);
	}
}
