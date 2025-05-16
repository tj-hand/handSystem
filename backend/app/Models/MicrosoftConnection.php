<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EncryptedCast implements CastsAttributes
{
	public function get($model, string $key, $value, array $attributes)
	{
		return $value ? Crypt::decrypt($value) : null;
	}

	public function set($model, string $key, $value, array $attributes)
	{
		return $value ? Crypt::encrypt($value) : null;
	}
}

class MicrosoftConnection extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'custom_microsoft_connection';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'account_id',
		'tenant',
		'client_id',
		'client_secret',
	];

	protected $casts = [
		'tenant' => EncryptedCast::class,
		'client_id' => EncryptedCast::class,
		'client_secret' => EncryptedCast::class,
	];
}
