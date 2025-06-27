<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_signages_slides';

	protected $fillable = [
		'id',
		'image_time',
		'image_order',
		'repository_id',
		'signage_id'
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
