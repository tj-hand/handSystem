<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddBearerTokenFromCookies
{
	public function handle(Request $request, Closure $next)
	{
		if ($request->cookie('access_token') && !$request->headers->has('Authorization')) {
			$token = $request->cookie('access_token');
			$request->headers->set('Authorization', 'Bearer ' . $token);
		}

		return $next($request);
	}
}
