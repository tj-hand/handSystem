<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class PBIRequest extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_pbi_requests';

	protected $fillable = [
		'id',
		'account_id',
		'client_id',
		'image_id',
		'workspace_id',
		'object_id',
		'export_id',
		'page_name',
		'pendente',
		'request_type',
		'status',
		'attempts',
		'max_attempts'
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
