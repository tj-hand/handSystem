<?php

namespace App\Services;

use App\Models\User;
use App\Models\Group;
use App\Models\UserAccountProperties;
use Illuminate\Support\Collection;

class AccountService
{
	protected string $accountId;

	public function __construct(string $accountId)
	{
		$this->accountId = $accountId;
	}

	public function users(): Collection
	{
		return User::select('users.name', 'users.email', 'users_global_properties.id AS uuid')
			->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
			->join('users_accounts_properties', 'users.id', '=', 'users_accounts_properties.user_id')
			->where('users_accounts_properties.account_id',  $this->accountId)
			->orderBy('users.name')
			->get();
	}

	public function groups(): Collection
	{
		return Group::select('admin_groups.id', 'admin_groups.name')
			->join('admin_scoped_relationships', 'admin_groups.id', '=', 'admin_scoped_relationships.object_id')
			->where('object_type', 'App\Models\Group')
			->where('belongs_to_type', 'App\Models\Account')
			->where('scope_type', 'App\Models\Account')
			->where('belongs_to_id', $this->accountId)
			->where('scope_id', $this->accountId)
			->orderBy('admin_groups.name')
			->get();
	}

	public function isUser($id)
	{
		$userAccount = UserAccountProperties::where('user_id', $id)->where('account_id', $this->accountId)->first();
		return $userAccount ? true : false;
	}
}
