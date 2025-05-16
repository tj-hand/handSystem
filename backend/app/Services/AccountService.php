<?php

namespace App\Services;

use App\Models\User;
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

	public function isUser($id)
	{
		$userAccount = UserAccountProperties::where('user_id', $id)->where('account_id', $this->accountId)->first();
		return $userAccount ? true : false;
	}
}
