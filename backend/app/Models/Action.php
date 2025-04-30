<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{

	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'string';
	protected $table = 'admin_actions';

	public function actionSet()
	{
		return $this->belongsTo(ActionSet::class, 'admin_set_action_id', 'id');
	}

	public static function fromSet(string $setName)
	{
		return self::whereHas('actionSet', fn($q) => $q->where('name', $setName))
			->select('admin_set_action_id', 'identifier', 'link_to', 'icon', 'subgroup')
			->orderBy('sort_order');
	}
}
