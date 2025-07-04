<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_logs';

	protected static function boot()
	{
		parent::boot();
		static::creating(function ($model) {
			if (!$model->id) {
				$model->id = (string) Str::uuid();
			}
		});
	}
}
