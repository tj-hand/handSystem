<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use App\Models\MicrosoftConnection;
use App\Services\PermissionService;
use App\Models\UserAccountProperties;
use Illuminate\Support\Facades\Crypt;
use App\Services\SystemLogService as Que;

class AccountController extends Controller
{

	protected $currentAccount;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$this->currentAccount = $permissionService->UserGlobalProperties()->current_account;
	}

	public function show(Request $request)
	{
		if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.account.show.invalid_id', $request->id);
		if ($request->id != $this->currentAccount) return Que::passa(false, 'auth.account.show.account_not_match', $request->id);

		try {
			$account = Account::select('admin_accounts.id', 'name', 'description', 'is_active', 'tenant', 'client_id', 'client_secret')
				->join('custom_microsoft_connection', 'admin_accounts.id', '=', 'custom_microsoft_connection.account_id')
				->where('admin_accounts.id', $request->id)
				->first();

			if (!$account) return Que::passa(false, 'auth.account.show.error.account_not_found', $request->id);

			$account->tenant = Crypt::decrypt($account->tenant);
			$account->client_id = Crypt::decrypt($account->client_id);
			$account->client_secret = Crypt::decrypt($account->client_secret);
			return Que::passa(true, 'auth.account.show', '', $account, ['account'  => ['record' => $account]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.show ' . $request->id);
		}
	}

	public function upsert(Request $request)
	{
		$userGlobal = $this->permissionService->UserGlobalProperties();
		$userAccount = $this->permissionService->UserCurrentAccountProperties();

		if (!$userGlobal->is_superuser && !$userAccount->is_account_admin)
			return Que::passa(false, 'auth.account.upsert.unauthorized', $request->id);

		$isUpdate = $request->filled('id');

		try {
			DB::beginTransaction();

			if ($isUpdate) {
				if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.account.upsert.invalid_id_format', $request->id);
				if ($request->id !== $this->currentAccount) return Que::passa(false, 'auth.account.show.account_not_match', $request->id);

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
				$message = 'auth.account.created';
			}

			$this->saveMicrosoftConnection($account->id, $request);
			$account->tenant = $request->tenant;
			$account->client_id = $request->client_id;
			$account->client_secret = $request->client_secret;

			DB::commit();
			return Que::passa(true, $message, '', $account, ['account' => $account]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.account.upsert ' . ($request->id ?? 'new'));
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
		try {
			$users = (new AccountService($this->currentAccount))->users();
			return Que::passa(true, 'auth.account.users.page_view', '', null, ['users' => $users]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.users');
		}
	}
}
