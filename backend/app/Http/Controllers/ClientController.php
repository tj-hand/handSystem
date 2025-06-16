<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Import Models
use App\Models\User;
use App\Models\Client;
use App\Models\Repository;
use App\Models\GrantConfig;
use App\Models\PBIWorkspace;
use App\Models\ScopedRelationship;
use App\Models\UserClientProperties;
use App\Models\UserGlobalProperties;

// Import Services
use App\Services\AccountService;
use App\Services\SignageService;
use App\Services\ProfileService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;
use App\Services\ScopedRelationshipService;

class ClientController extends Controller
{

	protected $record = [];
	protected $clientId = '';
	protected $client = null;
	protected $newRecord = false;
	protected $currentClientId = '';
	protected $currentAccountId = '';
	protected SignageService $signageService;
	protected ProfileService $profileService;
	protected AccountService $accountService;
	protected PermissionService $permissionService;

	public function __construct(
		Request $request,
		AccountService $accountService,
		PermissionService $permissionService,
		ProfileService $profileService,
		SignageService $signageService
	) {

		$this->record = $request->input('record');
		$this->clientId = data_get($request->input('record'), 'id', $request->input('id'));

		$this->accountService = $accountService;
		$this->profileService = $profileService;
		$this->signageService = $signageService;
		$this->permissionService = $permissionService;
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function show()
	{
		try {

			// Validate the operation
			$validationAndLoad = $this->validateAndLoadClient('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			$grants = GrantConfig::where('object_type', 'App\Models\Client')->where('object_id', $this->clientId)->first();
			$this->client['profile_users'] = $grants->profile_users;
			$this->client['profile_objects'] = $grants->profile_objects;
			$this->client['group_users'] = $grants->group_users;
			$this->client['group_actions'] = $grants->group_actions;
			$this->client['client_workspaces'] = $grants->client_workspaces;
			$this->client['user_local_actions'] = $grants->user_local_actions;

			return Que::passa(true, 'auth.client.show', '', $this->client, ['client'  => ['record' => $this->client]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.client.show ' . $this->clientId);
		}
	}

	public function upsert()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadClient('upsert');
		if ($validationAndLoad !== true) return $validationAndLoad;

		if ($this->newRecord) return $this->create();
		return $this->update();
	}

	private function create()
	{
		try {
			DB::beginTransaction();
			if (!$this->permissionService::hasPermission('Clients.auth.clients.add'))
				return Que::passa(false, 'auth.clients.add.error.unauthorized');

			$this->record['account_id'] = $this->currentAccountId;
			$this->client = Client::create($this->record);
			$this->clientId = $this->client->id;

			GrantConfig::create([
				'object_type' => 'App\Models\Client',
				'object_id' => $this->clientId,
				'group_users' => $this->record['group_users'],
				'group_actions' => $this->record['group_actions'],
				'client_workspaces' => $this->record['client_workspaces'],
				'user_local_actions' => $this->record['user_local_actions'],
			]);

			$this->client['profile_users'] = $this->record['profile_users'];
			$this->client['profile_objects'] = $this->record['profile_objects'];
			$this->client['group_users'] = $this->record['group_users'];
			$this->client['group_actions'] = $this->record['group_actions'];
			$this->client['client_workspaces'] = $this->record['client_workspaces'];
			$this->client['user_local_actions'] = $this->record['user_local_actions'];

			if (!empty($this->record['client.associated_users'])) $this->updateAssociatedUsers();
			if (!empty($this->record['client.associated_workspaces'])) $this->updateAssociatedWorkspaces();
			DB::commit();
			return Que::passa(true, 'auth.clients.created', '', $this->client, ['client'  => ['record' => $this->client]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.clients.create ' . $this->clientId);
		}
	}

	private function update()
	{
		if (!$this->permissionService::hasPermission('Clients.auth.clients.edit'))
			return Que::passa(false, 'auth.clients.edit.error.unauthorized');

		try {
			DB::beginTransaction();
			$this->client->name = $this->record['name'];
			$this->client->is_active = $this->record['is_active'];
			$this->client->description = $this->record['description'];
			$this->client->save();

			$grants = GrantConfig::where('object_type', 'App\Models\Client')->where('object_id', $this->clientId)->first();
			$grants->profile_users = $this->record['profile_users'];
			$grants->profile_objects = $this->record['profile_objects'];
			$grants->group_users = $this->record['group_users'];
			$grants->group_actions = $this->record['group_actions'];
			$grants->client_workspaces = $this->record['client_workspaces'];
			$grants->user_local_actions = $this->record['user_local_actions'];
			$grants->save();

			$this->client['group_users'] = $this->record['group_users'];
			$this->client['group_actions'] = $this->record['group_actions'];
			$this->client['client_workspaces'] = $this->record['client_workspaces'];
			$this->client['user_local_actions'] = $this->record['user_local_actions'];

			if (!empty($this->record['client.associated_users'])) $this->updateAssociatedUsers();
			if (!empty($this->record['client.associated_workspaces'])) $this->updateAssociatedWorkspaces();
			DB::commit();
			return Que::passa(true, 'auth.clients.updated', '', $this->client, ['client'  => ['record' => $this->client]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.clients.update ' . $this->clientId);
		}
	}

	private function updateAssociatedUsers()
	{
		foreach ($this->record['client.associated_users'] as $user) {
			$userGlobalProperties = UserGlobalProperties::find($user['id']);
			if ($userGlobalProperties) {
				$user['selected']
					? $this->attachUser($userGlobalProperties->user_id)
					: $this->deattachUser($userGlobalProperties->user_id);
			}
		}
	}

	private function updateAssociatedWorkspaces()
	{
		return app(ScopedRelationshipService::class)->syncScopedRelationships(
			parentEntity: $this->client,
			items: $this->record['client.associated_workspaces'],
			scopeType: 'App\Models\Client',
			relatedObjectType: 'App\Models\PBIWorkspace',
			grantField: 'client_workspaces'
		);
	}

	public function delete()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadClient('delete');
		if ($validationAndLoad !== true) return $validationAndLoad;

		// Check grant
		$grantOperation = $this->grantOperation('delete');
		if ($grantOperation !== true) return $grantOperation;

		try {
			DB::beginTransaction();
			GrantConfig::where('object_type', 'App\Models\Client')->where('object_id', $this->clientId)->delete();
			UserClientProperties::where('client_id', $this->clientId)->delete();
			ScopedRelationship::where('scope_type', 'App\Models\Client')->where('scope_id', $this->clientId)->delete();
			ScopedRelationship::where('belongs_to_type', 'App\Models\Client')->where('belongs_to_id', $this->clientId)->delete();
			$this->client->delete();
			DB::commit();
			return Que::passa(true, 'auth.client.deleted', '', $this->client);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.client.delete.error ' . $this->client);
		}
	}

	public function associatedUsers(Request $request)
	{
		if ($request->id == 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(UserGlobalProperties::class);
			return Que::passa(true, 'auth.client.associated_users.listed', '', null, ['list' => $list]);
		}

		// Validate the operation
		$validationAndLoad = $this->validateAndLoadClient('edit');
		if ($validationAndLoad !== true) return $validationAndLoad;


		$list = UserGlobalProperties::select(
			'users_global_properties.id',
			'users.name AS title',
			'users.email AS subtitle',
			DB::raw("
				CASE 
					WHEN users_clients_properties.requires_authorization = TRUE 
						AND (users_clients_properties.authorized IS NULL OR users_clients_properties.authorized = FALSE)
					THEN 'waiting'
					WHEN users_clients_properties.id IS NOT NULL 
					THEN '1'
					ELSE '0'
				END AS selected
			")
		)
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->join('users_accounts_properties', function ($join) {
				$join->on('users.id', '=', 'users_accounts_properties.user_id')
					->where('users_accounts_properties.account_id', '=', $this->currentAccountId);
			})
			->leftJoin('users_clients_properties', function ($join) {
				$join->on('users.id', '=', 'users_clients_properties.user_id')
					->where('users_clients_properties.client_id', '=', $this->clientId);
			})
			->where('users_global_properties.is_superuser', '=', FALSE)
			->where('users_global_properties.is_blocked', '=', FALSE)
			->where('users_accounts_properties.is_account_admin', '=', FALSE)
			->where('users_accounts_properties.is_active_to_account', '=', TRUE)
			->orderBy('users.name')
			->get()
			->map(function ($item) {
				$item->selected = $item->selected === 'waiting' ? 'waiting' : (bool) $item->selected;
				return $item;
			});



		return Que::passa(true, 'auth.client.associated_users.listed', '', $this->client, ['list' => $list]);
	}

	public function associatedWorkspaces(Request $request)
	{
		$clientId = $request->id;
		$client = Client::find($clientId);
		if (!$client) return Que::passa(false, 'auth.client.associated_with_workspaces.error.client_not_found', $clientId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($client, PBIWorkspace::class, Client::class);
		return Que::passa(true, 'auth.client.associated_with_workspaces.listed', '', null, ['list' => $list]);
	}

	private function attachUser($userId)
	{
		$grantOptions = GrantConfig::where('object_type', 'App\Models\Account')->where('object_id', $this->currentAccountId)->first();
		if (!$grantOptions) return Que::passa(false, 'auth.client.attach_user.error.grant_not_found');

		$userClientProperties = UserClientProperties::where('client_id', $this->clientId)->where('user_id', $userId)->first();
		if ($userClientProperties) return;

		if ($grantOptions->client_users) {
			return UserClientProperties::create([
				'user_id' => $userId,
				'client_id' => $this->clientId,
				'home_page' => 'Welcome',
				'requires_authorization' => true,
				'authorized' => false
			]);
		} else {
			return UserClientProperties::create([
				'user_id' => $userId,
				'client_id' => $this->clientId,
				'home_page' => 'Welcome',
				'requires_authorization' => false,
				'authorized' => true,
				'authorized_by_name' => 'System',
				'authorization_timestamp' => Carbon::now()
			]);
		}
	}

	private function deattachUser($userId)
	{
		return UserClientProperties::where('user_id', $userId)->where('client_id', $this->clientId)->delete();
	}

	private function validateAndLoadClient($operation)
	{
		// Handle new record
		if (is_array($this->record) && !array_key_exists('id', $this->record)) {
			$this->newRecord = true;
			return true;
		}

		// Validate UUID format
		if (!Str::isUuid($this->clientId)) return Que::passa(false, 'auth.client.' . $operation . '.invalid_id', $this->clientId);

		// Try to find the client
		$this->client = Client::find($this->clientId);
		if (!$this->client) return Que::passa(false, 'auth.client.' . $operation . '.not_found', $this->clientId);

		return true;
	}

	private function grantOperation($operation)
	{
		if (!$this->permissionService::hasPermission('Clients.auth.clients.' . $operation))
			return Que::passa(false, 'auth.clients.' . $operation . '.error.unauthorized');

		if (!$this->accountService->clients()->contains('id', $this->clientId))
			return Que::passa(false, 'auth.client.show.unauthorized', $this->clientId);

		return true;
	}

	public function localUsers()
	{
		if (!$this->permissionService::hasPermission('LocalUsers.auth.local_users.module'))
			return Que::passa(false, 'auth.client.users.list.error.unauthorized');

		$clientId = $this->permissionService::UserCurrentAccountProperties()->current_client;

		try {
			$users = User::select(
				'users.name',
				'users.email',
				'users_global_properties.id AS uuid',
				DB::raw('CASE WHEN users_clients_properties.authorized = true THEN false ELSE true END AS not_authorized')
			)
				->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
				->join('users_clients_properties', 'users.id', '=', 'users_clients_properties.user_id')
				->where('users_clients_properties.client_id',  $clientId)
				->orderBy('users.name')
				->get();
			return Que::passa(true, 'auth.client.users.list', '', null, ['users' => $users]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.client.users');
		}
	}

	public function profiles()
	{
		try {
			$profiles = $this->profileService->profiles();
			return Que::passa(true, 'auth.client.profiles.list', '', null, ['profiles' => $profiles]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.client.profiles.list.error');
		}
	}

	public function files()
	{
		$clientId = $this->permissionService->UserCurrentAccountProperties()->current_client;
		$files = Repository::where('client_id', $clientId)->orderBy('display_name')->get();
		return Que::Passa(true, 'auth.client.repository.list', '', null, ['filesIndex' => $files]);
	}

	public function signages()
	{
		try {
			$signages = $this->signageService->signages();
			return Que::passa(true, 'auth.client.signages.list', '', null, ['signages' => $signages]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.client.signages.list.error');
		}
	}
}
