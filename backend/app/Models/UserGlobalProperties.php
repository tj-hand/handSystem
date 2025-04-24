<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGlobalProperties extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'users_global_properties';

	public function account()
	{
		return $this->belongsTo(Account::class, 'current_account');
	}
}
