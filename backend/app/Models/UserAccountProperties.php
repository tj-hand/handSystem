<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccountProperties extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'users_accounts_properties';

	public function account()
	{
		return $this->belongsTo(Account::class, 'account_id', 'id');
	}

	public function client()
	{
		return $this->belongsTo(Client::class, 'current_client');
	}
}
