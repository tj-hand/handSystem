<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_clients';

	public function account()
	{
		return $this->belongsTo(Account::class, 'account_id', 'id');
	}

	public function getLogName(): string
	{
		return $this->name ?? 'Unnamed';
	}
}
