<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Kho;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id
 * @property string $full_name
 * @property string $sdt
 * @property string $password
 * @property string $position
 * @property string $img
 * @property string $cookie_value
 * 
 * @property Collection|Feedback[] $feedback
 *
 * @package App\Models\Kho
 */
class User extends Model
{
	protected $table = 'users';
	public $timestamps = false;

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'full_name',
		'sdt',
		'password',
		'position',
		'img',
		'cookie_value'
	];

	public function feedback()
	{
		return $this->hasMany(Feedback::class, 'delivery_person_id');
	}
}
