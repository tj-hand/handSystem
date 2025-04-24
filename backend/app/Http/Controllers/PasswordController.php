<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Mail\resetPasswordEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;

class PasswordController extends Controller
{

	private $user;
	private $errorResponse = null;
	private $additionalInformation = null;

	public function __construct(Request $request)
	{
		$validator = Validator::make($request->all(), ['email' => 'required|email']);

		if ($validator->fails()) {
			$this->errorResponse = 'generic.invalid_email';
			$this->additionalInformation = 'public.password.error ' . $request->email;
			return;
		}

		$this->user = User::where('email', $request->email)->first() ?? null;
		if (!$this->user) {
			$this->errorResponse = 'generic.user_not_found';
			$this->additionalInformation = 'public.password.error ' . $request->email;
		}
	}

	public function resetRequest()
	{
		if ($this->errorResponse) return Que::passa(false, $this->errorResponse, $this->additionalInformation);
		try {
			$token = Str::random(64);
			DB::table('password_reset_tokens')->where('email', $this->user->email)->delete();
			DB::table('password_reset_tokens')->insert(['email' => $this->user->email, 'token' => $token, 'created_at' => Carbon::now()]);
			$resetLink = env('URL_FRONTEND') . '/reset-password/' . $token;
			Mail::to($this->user->email)->send(new resetPasswordEmail($resetLink));
			return Que::Passa(true, 'public.forgot_password.success', '', $this->user);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'public.password.request', $this->user);
		}
	}

	public function reset(Request $request)
	{
		if ($this->errorResponse) return Que::passa(false, $this->errorResponse, $this->additionalInformation);

		$validator = Validator::make($request->all(), ['token' => 'required']);
		if (!$validator->fails()) $token = DB::table('password_reset_tokens')->where('email', $this->user->email)->where('token', $request->token)->first() ?? null;
		if ($validator->fails() || !$token) return Que::passa(false, 'public.password.error.invalid_token', '', $this->user);

		$validator = Validator::make($request->all(), ['password' => 'required|min:8|confirmed']);
		if ($validator->fails()) return Que::passa(false, 'public.password.error.invalid_reset_rules', '', $this->user);

		try {
			DB::transaction(function () use ($request) {
				$this->user->password = Hash::make($request->password);
				$this->user->save();
				DB::table('password_reset_tokens')->where('email', $this->user->email)->where('token', $request->token)->delete();
			});
			return Que::Passa(true, 'public.reset_password.success', ' ', $this->user);
		} catch (Exception $e) {
			return Que::Passa(false, 'generic.server_error', 'public.password.reset', $this->user);
		}
	}
}
