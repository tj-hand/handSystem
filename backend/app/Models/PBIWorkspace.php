<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class PBIWorkspace extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_pbi_workspaces';

	protected $fillable = [
		'account_id',
		'microsoft_id',
		'microsoft_type',
		'microsoft_name',
		'local_name',
		'description',
		'is_active',
		'checked'
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
