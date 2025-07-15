<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddRefreshTokenToForm
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
	 */
	public function handle(Request $request, Closure $next): Response
	{

		if ($request->has('grant_type')) {
			$grantType = $request->input('grant_type');
			if ($grantType == 'refresh_token') {
				$userRefreshToken = $request->cookie('refresh_token');
				$data = $request->json()->all();
				$data['refresh_token'] = $userRefreshToken;
				$newRequest = new Request([], $data, $request->toArray(), [], [], $_SERVER, json_encode($data));
				return $next($newRequest);
			}
		}

		return $next($request);
	}
}
