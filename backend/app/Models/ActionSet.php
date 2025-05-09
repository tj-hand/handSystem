<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionSet extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_actions_sets';

	public function actions()
	{
		return $this->hasMany(Action::class, 'admin_set_action_id', 'id')->select('admin_set_action_id', 'identifier', 'link_to', 'icon', 'is_visible', 'subgroup');
	}
}
