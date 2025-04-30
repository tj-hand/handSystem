<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopedRelationship extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_scoped_relationships';
}
