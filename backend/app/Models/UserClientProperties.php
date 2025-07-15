<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class UserClientProperties extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'users_clients_properties';

	protected $fillable = [
		'user_id',
		'client_id',
		'is_active_to_client',
		'home_page',
		'requires_authorization',
		'authorized',
		'authorized_by_id',
		'authorized_by_name',
		'authorization_timestamp'
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

	public function client()
	{
		return $this->belongsTo(Client::class, 'client_id', 'id');
	}
}
