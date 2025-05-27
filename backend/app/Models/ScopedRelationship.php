<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopedRelationship extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_scoped_relationships';

	protected $fillable = [
		'object_type',
		'object_id',
		'belongs_to_type',
		'belongs_to_id',
		'scope_type',
		'scope_id',
		'requires_authorization',
		'authorized',
		'authorized_by_id',
		'authorized_by_name',
		'authorization_timestamp'
	];
}
