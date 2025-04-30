<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Client;
use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService as Que;

class UserController extends Controller
{

	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
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
		$authUser = Auth::user();
		$account = Account::find($userGlobalProperties->current_account);
		if (!$account) return Que::passa(false, 'auth.login.error.account_not_found');
		$client = Client::find($this->permissionService->UserCurrentAccountProperties()->current_client);

		$enviroment = [
			'user' => [
				'id' => $authUser->id,
				'uuid' => $userGlobalProperties->id,
				'name' => $userGlobalProperties->user_name,
				'lastname' => $userGlobalProperties->user_lastname
			],
			'current_scope' => [
				'account_id' => $account->id,
				'account_name' => $account->name,
				'client_id' => $client->id ?? null,
				'client_name' => $client->name ?? null,
				'home_page' => $this->permissionService->UserCurrentClientProperties()->home_page
			],
			'scopes' => $this->permissionService->UserScopes(),
			'permissions' => $this->permissionService->UserActions()
		];

		return Que::passa(true, 'auth.access_granted', '', null, ['enviroment' => $enviroment]);
	}

	public function updateScope(Request $request)
	{

		try {

			if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.user.scope.invalid_id', $request->selector . ' ' . $request->id);

			switch ($request->selector) {
				case 'account':
					return $this->updateAccountScope($request);
				case 'client':
					return $this->updateClientScope($request);
				default:
					return Que::passa(false, 'auth.user.scope.invalid_selector', $request->selector);
			}
		} catch (Exception $e) {
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
			return Que::passa(true, 'auth.user.scope.updated', $request->selector, $account);
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
			return Que::passa(true, 'auth.user.scope.updated', $request->selector, $client);
		}

		return Que::passa(false, 'auth.user.scope.client_not_found', $request->selector);
	}
}
