<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Account;
use App\Models\ActionSet;
use App\Models\GroupMember;
use App\Models\ScopedRelationship;
use Illuminate\Support\Facades\Auth;
use App\Models\UserAccountProperties;

class PermissionService
{


	public static function user()
	{
		return Auth::user();
	}

	public static function UserGlobalProperties()
	{
		$userGlobalProperties = self::user()?->userGlobalProperties;
		return $userGlobalProperties ?? false;
	}

	public static function UserCurrentAccountProperties()
	{
		$userGlobalProperties = self::UserGlobalProperties();
		if (!$userGlobalProperties) return false;
		$userAccountProperties = self::user()->userAccountProperties->where('account_id', $userGlobalProperties->current_account)->first();
		return $userAccountProperties ?: false;
	}

	public static function UserCurrentClientProperties()
	{
		$UserCurrentAccountProperties = self::UserCurrentAccountProperties();
		if (!$UserCurrentAccountProperties) return false;
		$UserCurrentClientProperties = self::user()->userClientProperties->where('client_id', $UserCurrentAccountProperties->current_client)->first();
		return $UserCurrentClientProperties ?: false;
	}

	public static function isCurrentScopeValid()
	{
		$userGlobalProperties = self::userGlobalProperties();
		$userAccountProperties = self::userCurrentAccountProperties();
		if (!$userGlobalProperties || !$userAccountProperties) return false;

		$currentAccount = $userGlobalProperties->current_account;
		$currentClient = $userAccountProperties->current_client;

		if ($userGlobalProperties->is_superuser) return true;
		$is_valid_account = Account::where('id', $currentAccount)->where('is_active', true)->exists();
		if ($is_valid_account && $userAccountProperties->is_account_admin) return true;

		$clientBelongsToAccount = Client::where('id', $currentClient)
			->where('account_id', $currentAccount)
			->exists();
		if (!$clientBelongsToAccount) return false;

		if (!$userAccountProperties->is_active_to_account) return false;

		if (!$is_valid_account) return false;
		if ($userAccountProperties->is_account_admin) return true;

		$is_valid_client = Client::join('users_clients_properties', 'admin_clients.id', '=', 'users_clients_properties.client_id')
			->where('users_clients_properties.is_active_to_client', true)
			->where('admin_clients.id', $currentClient)
			->where('admin_clients.is_active', true)
			->exists();

		return $is_valid_client;
	}

	public static function UserAllAccounts()
	{
		$user = self::user();
		$userGlobalProperties = self::UserGlobalProperties();
		if (!$userGlobalProperties) return false;

		$query = UserAccountProperties::select('admin_accounts.id', 'admin_accounts.name', 'is_account_admin')
			->join('admin_accounts', 'users_accounts_properties.account_id', '=', 'admin_accounts.id')
			->where('users_accounts_properties.user_id', $user->id)
			->orderBy('admin_accounts.name');

		if (!$userGlobalProperties->is_superuser)
			$query->where('user_id', $user->id)
				->where('is_active_to_account', true)
				->where('admin_accounts.is_active', true);

		return $query->get();
	}

	public static function UserClientsForAccount($account)
	{
		$query = Client::select('admin_clients.id', 'admin_clients.name')
			->where('admin_clients.account_id', $account->id)
			->orderBy('admin_clients.name');

		if (self::UserGlobalProperties()->is_superuser || !empty($account->is_account_admin)) return $query->get();

		return $query
			->join('users_clients_properties', 'admin_clients.id', '=', 'users_clients_properties.client_id')
			->where('users_clients_properties.user_id', self::user()->id)
			->where('users_clients_properties.is_active_to_client', true)
			->where('admin_clients.is_active', true)
			->get();
	}

	public static function UserScopes()
	{
		$scopes = [];
		$accounts = self::UserAllAccounts();
		if (!$accounts) return false;
		foreach ($accounts as $account) {
			$clients = self::UserClientsForAccount($account);
			$account->clients = $clients;
			$isAdmin = self::userGlobalProperties()->is_superuser || ($account->is_account_admin == true);
			if ($isAdmin || $clients->isNotEmpty()) $scopes[] = $account;
		}
		return $scopes;
	}

	public static function setValidScope()
	{
		$scopes = self::UserScopes();
		if (!$scopes) return null;

		$user = self::UserGlobalProperties();
		$user->current_account = $scopes[0]['id'];
		$user->save();

		$account = self::UserCurrentAccountProperties();
		$account->current_client = $scopes[0]['clients'][0]['id'] ?? null;
		$account->save();

		return true;
	}

	public static function hasScope(string $type, string $id): bool
	{
		$scopes = self::UserScopes();

		if ($type === 'account') {
			return collect($scopes)->contains('id', $id);
		}

		if ($type === 'client') {
			return collect($scopes)
				->flatMap(fn($account) => $account['clients'] ?? [])
				->contains('id', $id);
		}

		return false;
	}

	public static function UserActions()
	{
		$user = self::UserGlobalProperties();
		if ($user->is_superuser) return self::superUserPermissions();
		return self::UserAccountGroups();
	}

	private static function superUserPermissions()
	{
		$superUserActions = [];
		$permissions =  ActionSet::select('id', 'name')->where('is_active', true)->orderBy('sort_order')->with('actions')->get();
		foreach ($permissions as $group) {
			$name = $group['name'];
			$actions = collect($group['actions'])->select('identifier', 'link_to', 'icon', 'is_visible', 'subgroup')->all();
			$superUserActions[$name] = $actions;
		}
		return $superUserActions;
	}

	private static function UserAccountGroups()
	{

		$user = self::UserGlobalProperties();
		$groups = ScopedRelationship::join('admin_groups', 'admin_scoped_relationships.belongs_to_id', '=', 'admin_groups.id')
			->where('object_type', 'App\Models\UserGlobalProperties')
			->where('belongs_to_type', 'App\Models\Group')
			->where('scope_type', 'App\Models\Account')
			->where('object_id', $user->id)
			->where('scope_id', $user->current_account)
			->pluck('belongs_to_id');
		return $groups;
	}

	private static function GroupMembers($groupId)
	{
		$members = GroupMember::whereIn('group_id', $groupId)->get();
		return $members;
	}
}
