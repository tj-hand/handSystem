<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClientProperties extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'users_clients_properties';

	public function client()
	{
		return $this->belongsTo(Client::class, 'client_id', 'id');
	}
}
