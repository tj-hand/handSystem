<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use App\Models\Log as SystemLog;
use Illuminate\Support\Facades\Log;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class SystemLogService
{
	public static function Passa(bool $success, string $message, string $additionalInformation = '', ?Model $object = null, array $returnedData = [])
	{
		try {

			$log = new SystemLog();
			$log->success = $success;
			$log->request_ip = request()->ip() ?? '0.0.0.0';
			$log->log_message = is_string($message) ? $message : 'No message provided';
			$log->additional_information = is_string($additionalInformation) ? $additionalInformation : null;
			$log->object_type = $object ? get_class($object) : null;
			$log->object_name = $object && method_exists($object, 'getLogName') ? $object->getLogName() : null;

			if ($object && get_class($object) === User::class) {
				$user = User::find($object->id);
				$userGlobalProperties = UserGlobalProperties::where('user_id', $user->id)->first();
				$log->object_id = $userGlobalProperties->id ?? null;
			} else {
				$log->object_id = optional($object)->id ?? null;
			}

			if (Auth::check()) {
				$authUser = Auth::User();
				$userId = $authUser->id;
				$user = User::find($userId);
				$userGlobalProperties = $user->userGlobalProperties;
				$userAccountProperties = $user->userAccountProperties()->where('account_id', $userGlobalProperties->current_account)->first();
				$account = $userGlobalProperties->current_account ? Account::find($userGlobalProperties->current_account) : null;
				$client = $userAccountProperties->current_client ? Client::find($userAccountProperties->current_client) : null;
				$log->user_id = $user->id;
				$log->user_name = $user->name;
				$log->user_email = $user->email;
				$log->account_id = optional($account)->id;
				$log->account_name = optional($account)->name;
				$log->client_id = optional($client)->id;
				$log->client_name = optional($client)->name;
			}

			$log->save();
			$response = ['success' => $success, 'message' => $message];
			if (!empty($returnedData)) $response = array_merge($response, $returnedData);
			return $response;
		} catch (Exception $e) {
			Log::error('Failed to save log entry: ' . $e->getMessage());
			return response()->json(['message' => 'generic.server_error']);
		}
	}
}
