<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService as Que;

class LoginService
{
	private $user;
	private $client;
	private $account;
	private $userGlobalProperties;
	private $userClientProperties;
	private $userAccountProperties;

	public function __construct()
	{
		$authUser = Auth::user();
		$userId = $authUser->id;
		$this->user = User::find($userId);

		if ($this->user) {
			$this->userGlobalProperties = $this->user->userGlobalProperties;
			$this->userAccountProperties = $this->user->userAccountProperties()
				->where('account_id', $this->userGlobalProperties->current_account)
				->first();
			$this->userClientProperties = $this->user->userClientProperties()
				->where('client_id', $this->userAccountProperties->current_client)
				->first();
		}

		$this->setAccount();
		$this->setClient();
	}

	public function handleLogin()
	{
		if (!$this->userGlobalProperties) return $this->accessDenied('generic.user_not_found', 'auth.login.error');
		if ($this->userGlobalProperties->is_blocked) return $this->accessDenied('auth.login.error.user_blocked');
		if ($this->userGlobalProperties->is_superuser && $this->checkScopeConsistency()) return $this->accessGranted();

		if (
			$this->userAccountProperties->is_account_admin
			&& $this->userAccountProperties?->is_active_to_account
			&& $this->account->is_active
			&& $this->checkScopeConsistency()
		) return $this->accessGranted();

		if (
			$this->account->is_active
			&& $this->client->is_active
			&& $this->userAccountProperties?->is_active_to_account
			&& $this->userClientProperties?->is_active_to_client
			&& $this->checkScopeConsistency()
		) return $this->accessGranted();

		if ($this->setValidScope()) return $this->accessGranted();

		return Que::Passa(false, 'auth.login.error.no_login_options');
	}

	private function checkScopeConsistency()
	{
		$accountId = $this->userGlobalProperties->current_account;
		$clientId = $this->userAccountProperties->current_client;
		$exists = Client::where('id', $clientId)->where('account_id', $accountId)->exists();
		return $exists;
	}

	private function setAccount()
	{
		$this->account = $this->userGlobalProperties->current_account
			? Account::find($this->userGlobalProperties->current_account)
			: null;
	}

	private function setClient()
	{
		$this->client = $this->userAccountProperties->current_client
			? Client::find($this->userAccountProperties->current_client)
			: null;
	}

	private function setValidScope()
	{
		if ($this->setAdminScope()) return true;
		if ($this->setBasicScope()) return true;
		return false;
	}

	private function setAdminScope()
	{
		try {
			$searchForAdminScenario = User::where('users.id', $this->user->id)
				->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
				->join('users_accounts_properties', 'users.id', '=', 'users_accounts_properties.user_id')
				->join('admin_accounts', 'users_accounts_properties.account_id', '=', 'admin_accounts.id')
				->where('users_accounts_properties.is_account_admin', true)
				->where('users_accounts_properties.is_active_to_account', true)
				->where('admin_accounts.is_active', true)
				->select('admin_account_id')
				->first();

			if (!$searchForAdminScenario) return false;

			$this->userGlobalProperties->current_account = $searchForAdminScenario->admin_account_id;
			$this->userGlobalProperties->save();
			$this->setAccount();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	private function setBasicScope()
	{
		try {
			$searchForBasicScenario = User::where('users.id', $this->user->id)
				->join('users_global_properties', 'users.id', '=', 'users_global_properties.user_id')
				->join('users_accounts_properties', 'users.id', '=', 'users_accounts_properties.user_id')
				->join('users_clients_properties', 'users.id', '=', 'users_clients_properties.user_id')
				->join('admin_accounts', 'users_accounts_properties.account_id', '=', 'admin_accounts.id')
				->join('admin_clients', 'users_clients_properties.client_id', '=', 'admin_clients.id')
				->where('users_accounts_properties.is_active_to_account', true)
				->where('users_clients_properties.is_active_to_client', true)
				->where('admin_accounts.is_active', true)
				->where('admin_clients.is_active', true)
				->first();

			if (!$searchForBasicScenario) return false;

			$this->userGlobalProperties->current_account = $searchForBasicScenario->account_id;
			$this->userAccountProperties->current_client = $searchForBasicScenario->client_id;
			$this->userGlobalProperties->save();
			$this->userAccountProperties->save();
			$this->setAccount();
			$this->setClient();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	private function accessDenied($message, $additionalInformation = null)
	{
		return Que::Passa(false, $message, $additionalInformation);
	}

	private function accessGranted()
	{
		$modules = PermissionService::modules();
		return Que::Passa(true, 'auth.login.success', '', null, [
			'profile' => $this->getProfile(),
			'scopes' => $this->getScopes(),
			'modules' => $modules
		]);
	}

	private function getProfile()
	{
		return [
			'user_id' => $this->user->id,
			'user_uuid' => $this->userGlobalProperties->id,
			'user_name' => $this->userGlobalProperties?->user_name,
			'user_lastname' => $this->userGlobalProperties?->user_lastname,
			'account_id' => $this->account?->id,
			'account_name' => $this->account?->name,
			'client_id' => $this->client?->id,
			'client_name' => $this->client?->name,
			'home_page' => $this->userClientProperties?->home_page
		];
	}

	private function getScopes()
	{
		$scopes = [];
		$accounts = $this->getAccounts();
		foreach ($accounts as $account) {
			$clients = $this->getClientsForAccount($account);
			$account->clients = $clients;
			$isAdmin = $this->userGlobalProperties->is_superuser || ($account->is_account_admin == true);
			if ($isAdmin || $clients->isNotEmpty()) $scopes[] = $account;
		}
		return $scopes;
	}

	private function getAccounts()
	{
		$query = Account::query()
			->select('admin_accounts.id', 'admin_accounts.name')
			->orderBy('admin_accounts.name');

		if (!$this->userGlobalProperties->is_superuser) {
			$query
				->join('users_accounts_properties', 'admin_accounts.id', '=', 'users_accounts_properties.account_id')
				->where('users_accounts_properties.user_id', $this->user->id)
				->where('users_accounts_properties.is_active_to_account', true)
				->where('admin_accounts.is_active', true)
				->addSelect('users_accounts_properties.is_account_admin');
		}

		return $query->get();
	}

	private function getClientsForAccount($account)
	{
		$query = Client::query()
			->select('admin_clients.id', 'admin_clients.name')
			->where('admin_clients.account_id', $account->id)
			->orderBy('admin_clients.name');

		if ($this->userGlobalProperties->is_superuser || !empty($account->is_account_admin)) return $query->get();

		return $query
			->join('users_clients_properties', 'admin_clients.id', '=', 'users_clients_properties.client_id')
			->where('users_clients_properties.user_id', $this->user->id)
			->where('users_clients_properties.is_active_to_client', true)
			->where('admin_clients.is_active', true)
			->get();
	}
}
