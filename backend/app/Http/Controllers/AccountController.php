<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\SystemLogService as Que;

class AccountController extends Controller
{
	public function show(Request $request)
	{

		if (!Str::isUuid($request->id)) return Que::passa(false, 'auth.account.show.invalid_id', $request->id);

		try {
			$account = Account::select('id', 'name', 'description', 'is_active')->where('id', $request->id)->first();
			return $account
				? Que::passa(true, 'auth.account.show', '', $account, ['account'  => $account])
				: Que::passa(false, 'auth.account.show.error.account_not_found', $request->id);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.account.show', $request->id);
		}
	}
}
