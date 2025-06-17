<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Log;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class LogController extends Controller
{

	protected $permissionService;


	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;

		if (!$this->permissionService->UserGlobalProperties()->is_superuser) {
			abort(403, 'Unauthorized - Superuser access only');
		}
	}

	public function messages()
	{
		try {
			$messages = Log::distinct()->pluck('log_message');
			return Que::passa(true, 'auth.log.unique_messages', '', null, ['options' => $messages]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_messages.error');
		}
	}

	public function usernames()
	{
		try {
			$usernames = Log::distinct()->pluck('user_name');
			return Que::passa(true, 'auth.log.unique_usernames', '', null, ['options' => $usernames]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_usernames.error');
		}
	}

	public function emails()
	{
		try {
			$usersEmails = Log::distinct()->pluck('user_email');
			return Que::passa(true, 'auth.log.unique_user_email', '', null, ['options' => $usersEmails]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_user_email.error');
		}
	}

	public function accounts()
	{
		try {
			$accounts = Log::distinct()->pluck('account_name');
			return Que::passa(true, 'auth.log.unique_account_names', '', null, ['options' => $accounts]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_account_names.error');
		}
	}

	public function clients()
	{
		try {
			$clients = Log::distinct()->pluck('client_name');
			return Que::passa(true, 'auth.log.unique_client_name', '', null, ['options' => $clients]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_client_name.error');
		}
	}

	public function objectTypes()
	{
		try {
			$objectsType = Log::distinct()->pluck('object_type');
			return Que::passa(true, 'auth.log.unique_objects_types', '', null, ['options' => $objectsType]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_objects_types.error');
		}
	}

	public function objectNames()
	{
		try {
			$objectsName = Log::distinct()->pluck('object_name');
			return Que::passa(true, 'auth.log.unique_objects_names', '', null, ['options' => $objectsName]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.unique_objects_names.error');
		}
	}
	public function getData(Request $request)
	{
		try {
			$query = Log::query();

			if ($request->filled('start_date')) {
				$startDate = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
				$query->whereDate('created_at', '>=', $startDate);
			}

			if ($request->filled('end_date')) {
				$endDate = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
				$query->whereDate('created_at', '<=', $endDate);
			}

			if ($request->input('action_type') === 'success') $query->where('success', true);
			if ($request->input('action_type') === 'error') $query->where('success', false);
			if ($request->filled('ip_address')) $query->where('request_ip', $request->ip_address);
			if ($request->filled('message')) $query->where('log_message', $request->message);
			if ($request->filled('username')) $query->where('user_name', $request->username);
			if ($request->filled('email')) $query->where('user_email', $request->email);
			if ($request->filled('account')) $query->where('account_name', $request->account);
			if ($request->filled('client')) $query->where('client_name', $request->client);
			if ($request->filled('object_type')) $query->where('object_type', $request->object_type);
			if ($request->filled('object_name')) $query->where('object_name', $request->object_name);


			$logs = $query->limit(1000)->get();
			return Que::passa(true, 'auth.log.get_data.success', '', null, ['logs' => $logs]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.log.get_data.error');
		}
	}
}
