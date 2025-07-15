<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_accounts';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'name',
		'is_active',
		'description',
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

	public function getLogName(): string
	{
		return $this->name ?? 'Unnamed';
	}

	public function microsoftConnection()
	{
		return $this->hasOne(MicrosoftConnection::class, 'account_id')->select('account_id', 'tenant', 'client_id', 'client_secret');
	}
}
