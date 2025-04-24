<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_accounts';

	public function getLogName(): string
	{
		return $this->name ?? 'Unnamed';
	}
}
