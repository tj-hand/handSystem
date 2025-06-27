<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_devices';

	protected $fillable = [
		'id',
		'device_id',
		'device_name',
		'client_id',
		'signage_id',
		'previous_signage_id'
	];

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
