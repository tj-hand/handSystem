<?php

namespace App\Services;

use App\Services\PBISPService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class PBIRequestService
{

	protected ?string $currentAccountId;
	protected PBISPService $PBISPService;
	protected PermissionService $permissionService;

	public function __construct(PBISPService $PBISPService, PermissionService $permissionService)
	{
		$this->PBISPService = $PBISPService;
		$this->permissionService = $permissionService;

		$userProperties = $this->permissionService->UserGlobalProperties();
		$this->currentAccountId = is_object($userProperties) ? $userProperties->current_account : null;
	}

	public function makeRequest(string $method = 'GET', string $endpoint, array $params = [], array $data = [])
	{

		$url = $this->replaceUrlParams($endpoint, $params);

		$accountId = $params['accountId'] ?? $this->currentAccountId;
		$this->currentAccountId = $accountId;

		$token = $this->PBISPService->getToken($this->currentAccountId);

		$response = Http::withHeaders([
			'Authorization' => 'Bearer ' . $token,
			'Content-Type' => 'application/json'
		])->{$method}($url, $data);

		if ($response->failed()) {
			return [
				'error' => $response->json(),
				'status' => $response->status()
			];
		}

		if (str_contains($response->header('Content-Type'), 'application/json')) return $response->json();

		return [
			'status' => 200,
			'file' => $response->body(),
			'content_type' => $response->header('Content-Type')
		];
	}

	private function replaceUrlParams(string $endpoint, array $params): string
	{
		foreach ($params as $key => $value) {
			$endpoint = str_replace("{{$key}}", $value, $endpoint);
		}
		return $endpoint;
	}
}
