<?php

namespace App\Services;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Hash;
use App\Models\UserAccountProperties;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;

class UserService
{
	public function upsert(Request $request)
	{
		$data = $request->input('record', []);
		$scope = $request->input('scope', null);



		$identificationValidation = $this->validateIdentification($data);
		if (!$identificationValidation['success']) {
			return $identificationValidation['response'];
		}

		$operationType = $this->determineOperationType($data);

		$emailValidation = $this->validateEmail($data);
		if (!$emailValidation['success']) {
			return $emailValidation['response'];
		}

		return $operationType === 'update'
			? $this->updateUser($data)
			: $this->createUser($data, $scope);
	}

	private function validateIdentification(array $data): array
	{
		$validator = Validator::make($data, [
			'id' => 'nullable|integer',
			'uuid' => 'nullable|uuid',
		]);

		if ($validator->fails()) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_identification')
			];
		}

		return ['success' => true];
	}

	private function determineOperationType(array $data): string
	{
		return !empty($data['id']) && !empty($data['uuid']) ? 'update' : 'create';
	}

	private function validateEmail(array $data): array
	{
		$validator = Validator::make($data, [
			'email' => 'required|email',
		]);

		if ($validator->fails()) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_email')
			];
		}

		return ['success' => true];
	}

	private function updateUser(array $data)
	{

		$is_superuser = PermissionService::UserGlobalProperties()->is_superuser;
		$currentAccountId = PermissionService::UserGlobalProperties()->current_account;

		if (!PermissionService::hasPermission('AccountUsers.auth.account_users.edit'))
			return Que::passa(false, 'auth.account.users.edit.error.unauthorized');

		$validationResults = $this->validateUserUpdateDetails($data);
		if (!$validationResults['success']) {
			return $validationResults['response'];
		}

		$user = User::find($data['id']);
		$userGlobalProperties = UserGlobalProperties::find($data['uuid']);

		$validationChecks = $this->performUpdateValidationChecks($data, $user, $userGlobalProperties);
		if (!$validationChecks['success']) {
			return $validationChecks['response'];
		}

		try {
			DB::beginTransaction();

			$user->name = $data['user_name'] . ' ' . $data['user_lastname'];
			if (!empty($data['password'])) {
				$user->password = bcrypt($data['password']);
			}
			$user->save();

			$userGlobalProperties->user_name = $data['user_name'];
			$userGlobalProperties->user_lastname = $data['user_lastname'];
			if ($is_superuser) {
				if (isset($data['is_superuser'])) $userGlobalProperties->is_superuser = $data['is_superuser'];
				if (isset($data['is_blocked'])) $userGlobalProperties->is_blocked = $data['is_blocked'];
			}
			$userGlobalProperties->save();

			$userAccountProperties = UserAccountProperties::where('user_id', $data['id'])->where('account_id', $currentAccountId)->first();
			if (isset($data['is_active_to_account'])) $userAccountProperties->is_active_to_account = $data['is_active_to_account'];
			if ($is_superuser && isset($data['is_account_admin'])) $userAccountProperties->is_account_admin = $data['is_account_admin'];
			$userAccountProperties->save();

			DB::commit();

			return ['success' => 'true', 'type' => 'update', 'id' => $userGlobalProperties->id];
		} catch (Exception $e) {
			DB::rollBack();
			return ['success' => 'false'];
		}
	}

	private function validateUserUpdateDetails(array $data): array
	{
		$nameValidator = Validator::make($data, [
			'user_name' => 'required|min:2',
			'user_lastname' => 'required|min:2',
		]);

		if ($nameValidator->fails()) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_user_name')
			];
		}

		$passwordValidator = Validator::make($data, [
			'password' => 'nullable|string|min:8',
		]);

		if ($passwordValidator->fails()) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_password')
			];
		}

		return ['success' => true];
	}

	private function performUpdateValidationChecks(array $data, $user, $userGlobalProperties): array
	{
		if (!$user || !$userGlobalProperties) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_operation_type')
			];
		}

		if ($data['email'] != $user->email) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_user_email')
			];
		}

		if ($userGlobalProperties->user_id != $user->id) {
			return [
				'success' => false,
				'response' => Que::passa(false, 'auth.user.error.upsert.error.invalid_data_request')
			];
		}

		return ['success' => true];
	}

	private function createUser(array $data, $scope = null)
	{

		if (!PermissionService::hasPermission('AccountUsers.auth.account_users.add'))
			return Que::passa(false, 'auth.account.users.add.error.unauthorized');

		$validationResults = $this->validateUserUpdateDetails($data);
		if (!$validationResults['success']) {
			return $validationResults['response'];
		}

		$emailValidation = $this->validateEmail($data);
		if (!$emailValidation['success']) {
			return $emailValidation['response'];
		}

		$is_superuser = PermissionService::UserGlobalProperties()->is_superuser;

		DB::beginTransaction();
		try {

			$password = isset($data['password']) && !empty($data['password'])
				? $data['password']
				: bin2hex(random_bytes(8));

			$user = new User();
			$user->name = $data['user_name'] . ' ' . $data['user_lastname'];
			$user->email = $data['email'];
			$user->password = Hash::make($password);
			$user->save();


			$currentAccountId = PermissionService::UserGlobalProperties()->current_account;
			$is_account_admin = false;

			$userGlobalProperties = new UserGlobalProperties();
			$userGlobalProperties->user_id = $user->id;
			$userGlobalProperties->user_name = $data['user_name'];
			$userGlobalProperties->user_lastname = $data['user_lastname'];
			$userGlobalProperties->current_account = $currentAccountId;
			if ($is_superuser) {
				$is_account_admin = $data['is_account_admin'];
				$userGlobalProperties->is_superuser = $data['is_superuser'] ?? false;
				$userGlobalProperties->is_blocked = $data['is_blocked'] ?? false;
			}
			$userGlobalProperties->save();

			if ($scope == 'account') $this->addUserToAccount($user->id, $is_account_admin);

			DB::commit();

			return ['success' => 'true', 'type' => 'create', 'id' => $userGlobalProperties->id];
		} catch (Exception $e) {
			DB::rollBack();
			return ['success' => 'false'];
		}
	}

	public function addUserToAccount($userId, $is_account_admin = false)
	{
		try {
			$is_superuser = PermissionService::UserGlobalProperties()->is_superuser;
			$currentAccountId = PermissionService::UserGlobalProperties()->current_account;
			$accountProperties = new UserAccountProperties();
			$accountProperties->user_id = $userId;
			$accountProperties->account_id = $currentAccountId;
			$accountProperties->is_active_to_account = true;
			$accountProperties->is_account_admin = false;
			$accountProperties->save();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function removeUserFromAccount($userId)
	{
		try {
			$currentAccountId = PermissionService::UserGlobalProperties()->current_account;
			$user = UserGlobalProperties::find($userId);
			if (!$user) return false;
			UserAccountProperties::where('user_id', $user->user_id)->where('account_id', $currentAccountId)->delete();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public static function getUser($id)
	{
		$accountId = PermissionService::UserCurrentAccountProperties()->account_id;
		return UserGlobalProperties::select('users.id', 'users_global_properties.id AS uuid', 'name', 'user_name', 'user_lastname', 'email', 'is_superuser', 'is_blocked', 'is_active_to_account', 'is_account_admin')
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->join('users_accounts_properties', 'users_accounts_properties.user_id', '=', 'users.id')
			->where('users_global_properties.id', $id)
			->where('users_accounts_properties.account_id', $accountId)
			->first();
	}
}
