<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class GrantConfig extends Model
{
	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_grants_configs';

	protected $fillable = [
		'object_type',
		'object_id',
		'profile_users',
		'profile_objects',
		'group_users',
		'group_actions',
		'client_workspaces',
		'user_local_actions',
	];

	protected $casts = [
		'object_type' => 'string',
		'object_id' => 'string',
		'profile_users' => 'boolean',
		'profile_objects' => 'boolean',
		'group_users' => 'boolean',
		'group_actions' => 'boolean',
		'client_workspaces' => 'boolean',
		'user_local_actions' => 'boolean',
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
