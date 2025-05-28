<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_clients';

	protected $fillable = [
		'account_id',
		'name',
		'is_active',
		'description'
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

	public function account()
	{
		return $this->belongsTo(Account::class, 'account_id', 'id');
	}

	public function getLogName(): string
	{
		return $this->name ?? 'Unnamed';
	}
}
