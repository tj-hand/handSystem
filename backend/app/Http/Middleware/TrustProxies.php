<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
	/**
	 * The trusted proxies for this application.
	 */
	protected $proxies = [
		// Trust all proxies (be cautious in production)
		'*',
		// Or specify Azure Container Apps IP ranges
		// '10.0.0.0/8',
		// '172.16.0.0/12',
		// '192.168.0.0/16'
	];

	/**
	 * The headers that should be used to detect proxies.
	 */
	protected $headers = Request::HEADER_X_FORWARDED_FOR |
		Request::HEADER_X_FORWARDED_HOST |
		Request::HEADER_X_FORWARDED_PORT |
		Request::HEADER_X_FORWARDED_PROTO |
		Request::HEADER_X_FORWARDED_AWS_ELB;
}
