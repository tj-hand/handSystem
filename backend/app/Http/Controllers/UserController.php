<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Group;
use App\Models\Action;
use App\Models\Client;
use App\Models\Account;
use App\Mail\sendInvite;
use App\Models\GrantConfig;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Carbon;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Mail;
use App\Models\UserClientProperties;
use App\Models\UserAccountProperties;
use App\Services\SystemLogService as Que;
use Illuminate\Support\Facades\Validator;
use App\Services\ScopedRelationshipService;


class UserController extends Controller
{

	protected $record = [];
	protected $userId = '';
	protected $user = null;
	protected $newRecord = false;
	protected $currentAccountId = '';
	protected UserService $userService;
	protected AccountService $accountService;
	protected PermissionService $permissionService;

	public function __construct(Request $request, UserService $userService, AccountService $accountService, PermissionService $permissionService)
	{

		$this->record = $request->input('record');
		$this->userId = data_get($request->input('record'), 'id', $request->input('id'));

		$this->userService = $userService;
		$this->accountService = $accountService;
		$this->permissionService = $permissionService;
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
	}

	public function requestEnviroment()
	{
		$user = $this->permissionService->UserGlobalProperties();
		if (!$user) return Que::Passa(false, 'generic.user_not_found', 'auth.login.error');
		if ($user->is_blocked) return Que::Passa(false, 'auth.login.error.user_blocked');
		if ($this->permissionService->isCurrentScopeValid()) return $this->accessGranted($user);
		if ($this->permissionService->setValidScope()) return $this->accessGranted($user);
		return Que::Passa(false, 'auth.login.error.no_login_options');
	}

	private function accessGranted($userGlobalProperties)
	{
		$account = Account::find($userGlobalProperties->current_account);
		if (!$account) return Que::passa(false, 'auth.login.error.account_not_found');
		$client = Client::find($this->permissionService->UserCurrentAccountProperties()->current_client);

		$enviroment = [
			'user' => [
				'id' => $userGlobalProperties->user_id,
				'uuid' => $userGlobalProperties->id,
				'email' => $this->permissionService->user()->email,
				'name' => $userGlobalProperties->user_name,
				'lastname' => $userGlobalProperties->user_lastname,
				'superuser' => $userGlobalProperties->is_superuser,
				'account_admin' => $this->permissionService->UserCurrentAccountProperties()->is_account_admin
			],
			'current_scope' => [
				'account_id' => $account->id,
				'account_name' => $account->name,
				'client_id' => $client->id ?? null,
				'client_name' => $client->name ?? null,
				'home_page' => $this->permissionService->UserCurrentClientProperties()->home_page ?? null
			],
			'scopes' => $this->permissionService->UserScopes(),
			'permissions' => $this->permissionService->UserActions()
		];

		return Que::passa(true, 'auth.access_granted', '', null, ['enviroment' => $enviroment]);
	}

	public function updateScope(Request $request)
	{
		try {
			if (!Str::isUuid($request->id)) {
				return Que::passa(false, 'auth.user.scope.invalid_id', $request->selector . ' ' . $request->id);
			}

			if (!$this->permissionService::hasScope($request->selector, $request->id)) {
				return Que::passa(false, 'auth.user.scope.unauthorized', $request->selector . ' ' . $request->id);
			}

			return match ($request->selector) {
				'account' => $this->updateAccountScope($request),
				'client'  => $this->updateClientScope($request),
				default   => Que::passa(false, 'auth.user.scope.invalid_selector', $request->selector),
			};
		} catch (\Throwable $e) {
			return Que::passa(false, 'auth.user.scope.update.error', $request->selector);
		}
	}

	private function updateAccountScope(Request $request)
	{
		$account = Account::find($request->id);
		if ($account) {
			$user = $this->permissionService->UserGlobalProperties();
			$user->current_account = $request->id;
			$user->save();
			return Que::passa(true, 'auth.user.scope.account_updated', $request->selector, $account);
		}

		return Que::passa(false, 'auth.user.scope.account_not_found', $request->selector);
	}

	private function updateClientScope(Request $request)
	{
		$client = Client::find($request->id);
		if ($client) {
			$account = $this->permissionService->UserCurrentAccountProperties();
			$account->current_client = $request->id;
			$account->save();
			return Que::passa(true, 'auth.user.scope.client_updated', $request->selector, $client);
		}

		return Que::passa(false, 'auth.user.scope.client_not_found', $request->selector);
	}

	public function show()
	{
		try {

			// Validate the operation
			$validationAndLoad = $this->validateAndLoadUser('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;
			$userGlobalProperties = UserGlobalProperties::find($this->userId);

			return Que::passa(true, 'auth.user.show', '', $userGlobalProperties, ['user'  => ['record' => $this->user]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.user.show ' . $this->userId);
		}
	}

	public function upsert(Request $request)
	{
		$upsert = $this->userService->upsert($request);
		if (!$upsert['success']) return Que::passa(false, 'generic.server_error', 'auth.user.update.error');
		$user =  (new UserService())::getUser($upsert['id']);
		$message = $upsert['type'] == 'update' ? 'auth.user.updated' : 'auth.user.created';
		return Que::passa(true, $message, '', null, ['user' => ['record' => $user]]);
	}

	public function delete(Request $request)
	{
		$validator = Validator::make($request->all(), ['id' => 'uuid']);
		if ($validator->fails()) return Que::passa(false, 'auth.user.error.delete.error.invalid_id_type');

		if (!$this->permissionService::hasPermission('AccountUsers.auth.account_users.delete'))
			return Que::passa(false, 'auth.account.users.delete.error.unauthorized');

		return $this->userService->removeUserFromAccount($request->input('id'))
			? Que::passa(true, 'auth.user.deleted_from_account', '', null)
			: Que::passa(false, 'generic.server_error', 'auth.user.deleted_from_account.error');
	}

	public function exists(Request $request)
	{
		return $this->checkUserExists($request, 'account');
	}

	public function existsInClient(Request $request)
	{
		return $this->checkUserExists($request, 'client');
	}

	private function checkUserExists(Request $request, string $checkType = 'account')
	{
		try {
			// Email validation check
			$validator = Validator::make($request->all(), ['email' => 'required|email']);
			if ($validator->fails()) return Que::Passa(false, 'auth.user.get_by_email.invalid_email', $request->input('email'));

			// Get user with global properties
			$user = User::select('users.*', 'users_global_properties.*', 'users_global_properties.id as uuid')
				->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
				->where('users.email', $request->email)
				->first();

			// Check if user exists
			if (!$user) return Que::passa(true, 'auth.user.get_by_email.user_not_found', $request->input('email'), null, ['user' => null]);

			// Check if user is blocked
			if ($user->is_blocked) return Que::passa(true, 'auth.user.get_by_email.user_is_blocked', $request->input('email'), null, ['user' => 'blocked']);

			// Check if user exists in account
			$existsInAccount = UserAccountProperties::where('user_id', $user->user_id)->where('account_id', $this->currentAccountId)->first();
			if ($existsInAccount) return Que::passa(true, 'auth.user.get_by_email.user_already_in_account', $request->input('email'), null, ['user' => 'inAccount']);

			// Additional check for client if requested
			if ($checkType === 'client') {
				$clientId = $this->permissionService::UserCurrentAccountProperties()->current_client;
				$existsInClient = UserClientProperties::where('user_id', $user->user_id)->where('client_id', $clientId)->first();
				if ($existsInClient) return Que::passa(true, 'auth.user.get_by_email.user_already_in_client', $request->input('email'), null, ['user' => 'inClient']);
			}

			// If all checks pass, user exists but not in account/client
			return Que::passa(true, 'auth.user.get_by_email.user_exists', $request->input('email'), null, ['user' => 'exists']);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.user.get_by_email ' . $request->input('email'));
		}
	}

	public function addToAccount(Request $request)
	{
		$validator = Validator::make($request->all(), ['email' => 'required|email']);
		if ($validator->fails()) return Que::Passa(false, 'auth.user.get_by_email.invalid_email',  $request->input('email'));

		$user = User::where('email', $request->input('email'))->first();
		if (!$user) return Que::passa(false, 'auth.user.add_to_account.error.user_not_found');

		$isInAccount = UserAccountProperties::where('user_id', $user->id)->where('account_id', $this->currentAccountId)->first();
		if ($isInAccount) return;

		$success = $this->userService->addUserToAccount($user->id);

		if ($success) {
			$user =  (new UserService())::getUser($user->userGlobalProperties->id);
			return Que::passa(true, 'auth.user.add_to_account', '', null, ['user' => ['record' => $user]]);
		} else {
			return Que::passa(false, 'generic.server_error', 'auth.user.add_to_account.error');
		}
	}

	public function addToClient(Request $request)
	{
		$validator = Validator::make($request->all(), ['email' => 'required|email']);
		if ($validator->fails()) return Que::Passa(false, 'auth.user.get_by_email.invalid_email',  $request->input('email'));

		$user = User::where('email', $request->input('email'))->first();
		if (!$user) return Que::passa(false, 'auth.user.add_to_client.error.user_not_found');

		try {
			$this->userService->addUserToAccount($user->id);
			$this->userService->addUserToClient($user->id);
			return Que::passa(true, 'auth.user.add_to_client', '', null, ['user' => ['record' => $user]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.user.add_to_client.error');
		}
	}

	public function sendInvite(Request $request)
	{
		try {
			if (!Str::isUuid($request->input('id'))) return Que::passa(false, 'auth.user.show.invalid_id', $request->input('id'));
			if (!$this->permissionService::hasPermission('AccountUsers.auth.account_users.show'))
				return Que::passa(false, 'auth.account.users.show.error.unauthorized');

			$userGlobalProperties = UserGlobalProperties::find($request->input('id'));
			$user = $userGlobalProperties ? User::find($userGlobalProperties->user_id) : null;

			if ($user) {
				$token = Str::random(64);
				DB::table('password_reset_tokens')->where('email', $user->email)->delete();
				DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => $token, 'created_at' => Carbon::now()]);
				$resetLink = env('URL_FRONTEND') . '/reset-password/' . $token;
				Mail::to($user->email)->send(new sendInvite($user->name, $resetLink));
				return Que::passa(true, 'auth.user.sendInvite', '', $user);
			} else {
				return Que::passa(false, 'auth.user.send_invite.error.user_not_found', $request->input('id'));
			}
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.user.send_invite ' . $request->input('id'));
		}
	}

	public function associatedWithClients(Request $request)
	{
		if ($request->id == 'new') {
			$list = Client::select('id', 'name AS title')
				->where('account_id', $this->currentAccountId)
				->where('is_active', true)
				->orderBy('name')
				->get()
				->map(function ($item) {
					$item->selected = false;
					return $item;
				});
			return Que::passa(true, 'auth.user.associated_clients.listed', '', null, ['list' => $list]);
		}

		$validationAndLoad = $this->validateAndLoadUser('edit');
		if ($validationAndLoad !== true) return $validationAndLoad;

		$userGlobalProperty = UserGlobalProperties::find($this->userId);
		if (!$userGlobalProperty) return Que::passa(false, 'auth.user.not_found', '', null, []);

		$list = Client::select(
			'admin_clients.id',
			'admin_clients.name AS title',
			DB::raw("
					CASE 
						WHEN users_clients_properties.requires_authorization = true 
							AND (users_clients_properties.authorized IS NULL OR users_clients_properties.authorized = false)
						THEN 'waiting'
						WHEN users_clients_properties.id IS NOT NULL 
						THEN '1'
						ELSE '0'
					END AS selected
				")
		)
			->leftJoin('users_clients_properties', function ($join) use ($userGlobalProperty) {
				$join->on('admin_clients.id', '=', 'users_clients_properties.client_id')
					->where('users_clients_properties.user_id', '=', $userGlobalProperty->user_id);
			})
			->where('admin_clients.account_id', $this->currentAccountId)
			->where('admin_clients.is_active', true)
			->orderBy('admin_clients.name')
			->get()
			->map(function ($item) {
				if ($item->selected === 'waiting') {
					$item->selected = 'waiting';
				} else {
					$item->selected = (bool) $item->selected;
				}
				return $item;
			});

		return Que::passa(true, 'auth.user.associated_clients.listed', '', null, ['list' => $list]);
	}

	public function associatedWithGroups(Request $request)
	{
		$userId = $request->input('id');

		if ($userId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Group::class);
			return Que::passa(true, 'auth.user.associated_with_groups.listed', '', null, ['list' => $list]);
		}

		$user = UserGlobalProperties::find($userId);
		if (!$user) return Que::passa(false, 'auth.group.associated_with_groups.error.user_not_found', $userId);
		$list = app(ScopedRelationshipService::class)->makeReverseScopedListWithRelations($user, Group::class);
		return Que::passa(true, 'auth.user.associated_with_groups.listed', '', null, ['list' => $list]);
	}

	public function localAssociatedWithGroups(Request $request)
	{
		$userId = $request->input('id');

		if ($userId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Group::class, Client::class);
			return Que::passa(true, 'auth.user.associated_with_groups.listed', '', null, ['list' => $list]);
		}

		$user = UserGlobalProperties::find($userId);
		if (!$user) return Que::passa(false, 'auth.group.associated_with_groups.error.user_not_found', $userId);
		$list = app(ScopedRelationshipService::class)->makeReverseScopedListWithRelations($user, Group::class, Client::class);
		return Que::passa(true, 'auth.user.associated_with_groups.listed', '', null, ['list' => $list]);
	}

	public function associatedWithGlobalActions(Request $request)
	{
		$userId = $request->input('id');

		if ($userId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Action::class);
			return Que::passa(true, 'auth.user.associated_with_actions.listed', '', null, ['list' => $list]);
		}

		$user = UserGlobalProperties::find($userId);
		if (!$user) return Que::passa(false, 'auth.group.associated_with_actions.error.user_not_found', $userId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($user, Action::class);
		return Que::passa(true, 'auth.user.associated_with_actions.listed', '', null, ['list' => $list]);
	}

	public function associatedWithLocalActions(Request $request)
	{
		$userId = $request->input('id');

		if ($userId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Action::class);
			return Que::passa(true, 'auth.user.associated_with_actions.listed', '', null, ['list' => $list]);
		}

		$user = UserGlobalProperties::find($userId);
		if (!$user) return Que::passa(false, 'auth.group.associated_with_actions.error.user_not_found', $userId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($user, Action::class, Client::class);
		return Que::passa(true, 'auth.user.associated_with_actions.listed', '', null, ['list' => $list]);
	}

	private function validateAndLoadUser($operation)
	{
		// Handle new record
		if (is_array($this->record) && !array_key_exists('id', $this->record)) {
			$this->newRecord = true;
			return true;
		}

		// Validate UUID format
		if (!Str::isUuid($this->userId)) return Que::passa(false, 'auth.user.' . $operation . '.invalid_id', $this->userId);

		// Try to find the client
		$this->user = $this->userService::getUser($this->userId);
		if (!$this->user) return Que::passa(false, 'auth.user.' . $operation . '.not_found', $this->user);

		return true;
	}

	private function grantOperation($operation)
	{
		$global_permission = $this->permissionService::hasPermission('AccountUsers.auth.account_users.' . $operation);
		$local_permission = $this->permissionService::hasPermission('LocalUsers.auth.local_users.' . $operation);

		if (!$global_permission && !$local_permission) return Que::passa(false, 'auth.users.' . $operation . '.error.unauthorized');

		if (!$this->accountService->users()->contains('uuid', $this->userId))
			return Que::passa(false, 'auth.users.show.unauthorized', $this->userId);

		return true;
	}
}
