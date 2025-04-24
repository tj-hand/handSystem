<?php

namespace App\Http\Controllers;

use Exception;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;
use Illuminate\Http\Request as IlluminateRequest;

class AuthController extends Controller
{
	public function __construct() {}

	public function getTokensAsCookies(Request $request)
	{

		try {

			$validator = Validator::make($request->all(), [
				'username' => 'required|email',
				'password' => 'required|string|min:8',
			]);

			if ($validator->fails()) return Que::Passa(false, 'public.token.error.invalid_credentials_format', 'username_' . $request->username);

			$tokenRequest = IlluminateRequest::create('/oauth/token', 'POST', [
				'grant_type' => 'password',
				'client_id' => config('services.passport.client_id'),
				'client_secret' => config('services.passport.client_secret'),
				'username' => $request->username,
				'password' => $request->password,
				'scope' => '',
			]);
			$response = app()->handle($tokenRequest);
			$data = json_decode($response->getContent(), true);

			if (isset($data['access_token']) && isset($data['refresh_token'])) {
				Que::passa(true, 'auth.token.succes', 'username_' . $request->username);
				return response()
					->json(['success' => true, 'message' => 'Authentication successful'])
					->cookie('access_token', $data['access_token'], 60, null, null, true, true, false, 'strict')
					->cookie('refresh_token', $data['refresh_token'], 1440, null, null, true, true, false, 'strict');
			}

			return Que::passa(false, 'public.token.error.invalid_credentials_data', 'username_' . $request->username);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.token.username_' . $request->username);
		}
	}

	public function revokeTokens()
	{
		try {
			$authUser = Auth::user();
			if ($authUser->id) {
				$userTokens = Token::where('user_id', $authUser->id)->get();
				foreach ($userTokens as $token) {
					$token->revoke();
				}
				Que::Passa(true, 'auth.tokens.revoked', '', $authUser);
				return response()
					->json(['success' => true, 'message' => 'Revoked Tokens'])
					->cookie('access_token', '', -1, null, null, true, true, false, 'strict')
					->cookie('refresh_token', '', -1, null, null, true, true, false, 'strict');
			} else {
				return Que::Passa(false, 'auth.tokens.revoke.error', '', $authUser);
			}
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.tokens.revoke');
		}
	}
}
