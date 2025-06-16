<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class PBIImage extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_pbi_images';

	protected $fillable = [
		'id',
		'pbi_name',
		'pbi_displayname',
		'image_name',
		'image_time',
		'status',
		'reload_time',
		'workspace_id',
		'object_id',
		'user_id'
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
