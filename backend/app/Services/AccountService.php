<?php

namespace App\Services;

use App\Models\User;
use App\Models\Group;
use App\Models\Client;
use App\Models\PBIWorkspace;
use Illuminate\Support\Collection;
use App\Services\PermissionService;

class AccountService
{
	protected null|string $currentClientId;
	protected string $currentAccountId;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function users(): Collection
	{
		return User::select('users.name', 'users.email', 'users_global_properties.id AS uuid')
			->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
			->join('users_accounts_properties', 'users.id', '=', 'users_accounts_properties.user_id')
			->where('users_accounts_properties.account_id',  $this->currentAccountId)
			->orderBy('users.name')
			->get();
	}

	public function groups(): Collection
	{
		return Group::select('admin_groups.id', 'admin_groups.name')
			->join('admin_scoped_relationships', 'admin_groups.id', '=', 'admin_scoped_relationships.object_id')
			->where('object_type', 'App\Models\Group')
			->where('scope_type', 'App\Models\Account')
			->where('group_type', 'permissions_group')
			->where(function ($query) {
				$query->where(function ($q) {
					$q->where('belongs_to_type', 'App\Models\Account')
						->where('belongs_to_id', $this->currentAccountId)
						->where('scope_id', $this->currentAccountId);
				})
					->orWhere(function ($q) {
						$q->where('belongs_to_type', 'App\Models\Client')
							->where('belongs_to_id', $this->currentClientId)
							->where('scope_id', $this->currentAccountId);
					});
			})
			->orderBy('admin_groups.name')
			->get();
	}

	public function clients(): Collection
	{
		return Client::select('id', 'name')->where('account_id', $this->currentAccountId)->orderBy('name')->get();
	}

	public function workspaces(): Collection
	{
		return PBIWorkspace::select('id', 'local_name')->where('account_id', $this->currentAccountId)->orderBy('local_name')->get();
	}
}
