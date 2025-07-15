<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

// Import Models
use App\Models\Group;
use App\Models\Client;
use App\Models\Account;
use App\Models\GrantConfig;
use App\Models\ScopedRelationship;
use App\Models\MicrosoftConnection;
use App\Models\UserAccountProperties;

// Import Services
use App\Services\AccountService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;


class AccountController extends Controller
{

	protected $currentAccountId;
	protected AccountService $accountService;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService, AccountService $accountService)
	{
		$this->accountService = $accountService;
		$this->permissionService = $permissionService;
		$this->currentAccountId = $permissionService->UserGlobalProperties()->current_account;
	}

	public function show(Request $request)
	{
		if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.account.show.invalid_id', $request->id);
		if ($request->id != $this->currentAccountId) return Que::passa(false, 'auth.account.show.account_not_match', $request->id);

		$userGlobal = $this->permissionService->UserGlobalProperties();
		$userAccount = $this->permissionService->UsercurrentAccountProperties();
		$grants = GrantConfig::where('object_type', 'App\Models\Account')->where('object_id', $userGlobal->current_account)->first();

		if (!$userGlobal->is_superuser && !$userAccount->is_account_admin)
			return Que::passa(false, 'auth.account.upsert.unauthorized', $request->id);

		try {
			$account = Account::select('admin_accounts.id', 'name', 'description', 'is_active', 'tenant', 'client_id', 'client_secret')
				->join('custom_microsoft_connection', 'admin_accounts.id', '=', 'custom_microsoft_connection.account_id')
				->where('admin_accounts.id', $request->id)
				->first();

			if (!$account) return Que::passa(false, 'auth.account.show.error.account_not_found', $request->id);

			if ($account->tenant) $account->tenant = Crypt::decrypt($account->tenant);
			if ($account->client_id) $account->client_id = Crypt::decrypt($account->client_id);
			if ($account->client_secret) $account->client_secret = Crypt::decrypt($account->client_secret);

			$account->client_users = $grants->client_users;
			$account->user_global_actions = $grants->user_global_actions;;

			return Que::passa(true, 'auth.account.show', '', $account, ['account'  => ['record' => $account]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.show ' . $request->id);
		}
	}

	public function upsert(Request $request)
	{
		$userGlobal = $this->permissionService->UserGlobalProperties();
		$userAccount = $this->permissionService->UsercurrentAccountProperties();

		if (!$userGlobal->is_superuser && !$userAccount->is_account_admin)
			return Que::passa(false, 'auth.account.upsert.unauthorized', $request->id);

		$isUpdate = $request->filled('id');

		try {
			DB::beginTransaction();

			if ($isUpdate) {
				if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.account.upsert.invalid_id_format', $request->id);
				if ($request->id !== $this->currentAccountId) return Que::passa(false, 'auth.account.show.account_not_match', $request->id);

				$account = Account::find($request->id);
				if (!$account) return Que::passa(false, 'auth.account.upsert.account_not_found', $request->id);

				$account->update([
					'name' => $request->name,
					'description' => $request->description,
					'is_active' => $request->is_active,
				]);
				$message = 'auth.account.updated';
			} else {

				if (!$userGlobal->is_superuser) return Que::passa(false, 'auth.account.upsert.unauthorized', null);

				$account = Account::create([
					'name' => $request->name,
					'description' => $request->description,
					'is_active' => $request->is_active,
				]);

				$this->addUserToNewAccount($account->id);

				GrantConfig::create([
					'object_type' => 'App\Models\Account',
					'object_id' => $account->id
				]);

				$userGlobal->current_account = $account->id;
				$userGlobal->save();
				$message = 'auth.account.created';
			}

			$this->saveMicrosoftConnection($account->id, $request);
			$account->tenant = $request->tenant;
			$account->client_id = $request->client_id;
			$account->client_secret = $request->client_secret;

			$grants = GrantConfig::where('object_type', 'App\Models\Account')->where('object_id', $userGlobal->current_account)->first();
			$grants->client_users = $request->client_users;
			$grants->user_global_actions = $request->user_global_actions;
			$grants->save();

			DB::commit();
			return Que::passa(true, $message, '', $account, ['account' => $account]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.account.upsert ' . ($request->id ?? 'new'));
		}
	}

	public function delete(Request $request)
	{
		$id = $request->input('id');
		if (!Str::isUuid($id)) return Que::passa(false, 'auth.account.delete.invalid_id', $id);
		if ($id != $this->currentAccountId) return Que::passa(false, 'auth.account.delete.account_not_match', $id);
		$account = Account::find($id);
		if (!$account) return Que::passa(false, 'auth.account.delete.account_not_found', $id);

		$userGlobal = $this->permissionService->UserGlobalProperties();
		if (!$userGlobal->is_superuser) return Que::passa(false, 'auth.account.delete.unauthorized', $id);

		try {
			DB::beginTransaction();
			Client::where('account_id', $request->input('id'))->delete();
			MicrosoftConnection::where('account_id', $request->input('id'))->delete();
			UserAccountProperties::where('account_id', $request->input('id'))->delete();

			$belongsRelationships = ScopedRelationship::where('object_type', 'App\Models\Group')
				->where('belongs_to_type', 'App\Models\Account')->where('belongs_to_id', $id)->get();

			foreach ($belongsRelationships as $groupRelationship) {
				ScopedRelationship::where('belongs_to_type', 'App\Models\Group')
					->where('belongs_to_id', $groupRelationship->object_id)
					->where('object_type', 'App\Models\Action')
					->delete();
				ScopedRelationship::where('belongs_to_type', 'App\Models\Group')
					->where('belongs_to_id', $groupRelationship->object_id)
					->where('object_type', 'App\Models\UserGlobalProperties')
					->delete();
				Group::find($groupRelationship->object_id)->delete();
				$groupRelationship->delete();
			}

			$account->delete();
			DB::commit();
			$this->permissionService::setValidScope();
			return Que::passa(true, 'auth.account.deleted');
		} catch (Exception $e) {
			DB::rollBack();
			Que::passa(false, 'generic.server_error', 'auth.account.delete ' . $id);
		}
	}

	private function saveMicrosoftConnection(string $accountId, Request $request): void
	{
		$connection = MicrosoftConnection::where('account_id', $accountId)->first();

		if ($connection) {
			$connection->update([
				'tenant' => $request->tenant,
				'client_id' => $request->client_id,
				'client_secret' => $request->client_secret,
			]);
		} else {
			MicrosoftConnection::create([
				'account_id' => $accountId,
				'tenant' => $request->tenant,
				'client_id' => $request->client_id,
				'client_secret' => $request->client_secret,
			]);
		}
	}

	private function addUserToNewAccount($accountId)
	{
		$userId = $this->permissionService->user()->id;
		$accountProperties = new UserAccountProperties();
		$accountProperties->user_id = $userId;
		$accountProperties->account_id = $accountId;
		$accountProperties->is_active_to_account = true;
		$accountProperties->is_account_admin = false;
		$accountProperties->save();
	}

	public function users()
	{
		if (!$this->permissionService::hasPermission('AccountUsers.auth.account_users.module'))
			return Que::passa(false, 'auth.account.users.list.error.unauthorized');
		try {
			$users = $this->accountService->users();
			return Que::passa(true, 'auth.account.users.list', '', null, ['users' => $users]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.users');
		}
	}

	public function groups()
	{
		if (!$this->permissionService::hasPermission('Groups.auth.groups.module'))
			return Que::passa(false, 'auth.account.groups.list.error.unauthorized');
		try {
			$groups = $this->accountService->groups();
			return Que::passa(true, 'auth.account.groups.list', '', null, ['groups' => $groups]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.groups');
		}
	}

	public function clients()
	{
		if (!$this->permissionService::hasPermission('Clients.auth.clients.module'))
			return Que::passa(false, 'auth.account.clients.list.error.unauthorized');
		try {
			$clients = $this->accountService->clients();
			return Que::passa(true, 'auth.account.clients.list', '', null, ['clients' => $clients]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.clients');
		}
	}

	public function workspaces()
	{
		if (!$this->permissionService::hasPermission('Workspaces.auth.workspaces.module'))
			return Que::passa(false, 'auth.account.workspace.list.error.unauthorized');
		try {
			$workspaces = $this->accountService->workspaces();
			return Que::passa(true, 'auth.account.workspaces.list', '', null, ['workspaces' => $workspaces]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.workspaces');
		}
	}
}
