<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class UserGlobalProperties extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'users_global_properties';

	protected static function boot()
	{
		parent::boot();
		static::creating(function ($model) {
			if (!$model->id) {
				$model->id = (string) Str::uuid();
			}
		});
	}

	public function account()
	{
		return $this->belongsTo(Account::class, 'current_account');
	}
}
