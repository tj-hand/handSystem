<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Group;
use App\Models\Action;
use App\Models\Client;
use App\Models\Account;
use App\Mail\sendInvite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Carbon;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Mail;
use App\Services\SystemLogService as Que;
use Illuminate\Support\Facades\Validator;
use App\Services\ScopedRelationshipService;


class UserController extends Controller
{

	protected $userService;
	protected PermissionService $permissionService;

	public function __construct(UserService $userService, PermissionService $permissionService)
	{
		$this->userService = $userService;
		$this->permissionService = $permissionService;
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

	public function show(Request $request)
	{
		try {
			if (!Str::isUuid($request->input('id'))) return Que::passa(false, 'auth.user.show.invalid_id', $request->input('id'));
			if (!$this->permissionService::hasPermission('AccountUsers.auth.account_users.show'))
				return Que::passa(false, 'auth.account.users.show.error.unauthorized');

			$accountId = PermissionService::UserGlobalProperties()->current_account;
			$users = (new AccountService($accountId))->users();
			$exists = $users->contains('uuid', $request->input('id'));
			if (!$exists) return Que::passa(false, 'auth.user.show.unauthorized', $request->input('id'));
			$user = (new UserService())::getUser($request->input('id'));
			return $user
				? Que::passa(true, 'auth.user.show', '', null, ['user'  => ['record' => $user]])
				: Que::passa(false, 'auth.user.show.error.user_not_found', $request->input('id'));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.user.show ' . $request->input('id'));
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
		try {

			$validator = Validator::make($request->all(), ['email' => 'required|email']);
			if ($validator->fails()) return Que::Passa(false, 'auth.user.get_by_email.invalid_email',  $request->input('email'));

			$user = User::select('users.*', 'users_global_properties.*', 'users_global_properties.id as uuid')
				->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
				->where('users.email', $request->email)
				->first();

			$currentAccount = $this->permissionService::UserGlobalProperties()->current_account;

			if (!$user) return Que::passa(true, 'auth.user.get_by_email.user_not_found',  $request->input('email'), null, ['user' => null]);
			if ($user->is_blocked) return Que::passa(true, 'auth.user.get_by_email.user_is_blocked',  $request->input('email'), null, ['user' => 'blocked']);
			$existsInAccount = (new AccountService($currentAccount))->isUser($user->user_id);
			if ($existsInAccount) return Que::passa(true, 'auth.user.get_by_email.user_already_in_account',  $request->input('email'), null, ['user' => 'inAccount']);
			return Que::passa(true, 'auth.user.get_by_email.user_exists',  $request->input('email'), null, ['user' => 'exists']);
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

		$success = $this->userService->addUserToAccount($user->id);

		if ($success) {
			$user =  (new UserService())::getUser($user->userGlobalProperties->id);
			return Que::passa(true, 'auth.user.add_to_account', '', null, ['user' => ['record' => $user]]);
		} else {
			return Que::passa(false, 'generic.server_error', 'auth.user.add_to_account.error');
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
		$userId = $request->input('id');

		if ($userId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Client::class);
			return Que::passa(true, 'auth.user.associated_with_clients.listed', '', null, ['list' => $list]);
		}

		$user = UserGlobalProperties::find($userId);
		if (!$user) return Que::passa(false, 'auth.group.associated_with_clients.error.user_not_found', $userId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($user, Client::class);
		return Que::passa(true, 'auth.user.associated_with_clients.listed', '', null, ['list' => $list]);
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
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($user, Group::class);
		return Que::passa(true, 'auth.user.associated_with_groups.listed', '', null, ['list' => $list]);
	}

	public function associatedWithActions(Request $request)
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
}
