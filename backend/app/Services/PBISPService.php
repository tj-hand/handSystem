<?php

namespace App\Services;

use App\Models\MicrosoftConnection;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PBISPService
{

	protected array $tokens = [];
	protected ?string $currentAccountId;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$userProperties = $this->permissionService->UserGlobalProperties();
		$this->currentAccountId = is_object($userProperties) ? $userProperties->current_account : null;
	}


	public function getToken(?string $accountId = null)
	{

		($accountId !== null) ? $this->currentAccountId = $accountId : $accountId = $this->currentAccountId;

		if (isset($this->tokens[$this->currentAccountId]) && !$this->isTokenExpired($this->currentAccountId)) {
			return $this->tokens[$this->currentAccountId];
		}

		$connectionData = $this->getMicrosoftConnectionData();
		if (!$connectionData) return false;

		$requestToken = $this->requestToken($connectionData);
		if (!$requestToken) return false;

		$this->tokens[$this->currentAccountId] = $requestToken;
		Cache::put("powerbi_token_{$this->currentAccountId}", $requestToken, now()->addMinutes(60));

		return $requestToken;
	}

	private function getMicrosoftConnectionData()
	{
		$microsoftData = MicrosoftConnection::where('account_id', $this->currentAccountId)->first();
		if (!$microsoftData) return false;

		$data = [
			'tenant' => $microsoftData->tenant,
			'client_id' => $microsoftData->client_id,
			'client_secret' => $microsoftData->client_secret
		];

		return $data;
	}

	private function requestToken($connectionData)
	{

		$serviePrincialTokenURL = str_replace('{tenant}', $connectionData['tenant'], config('powerbi.servicePrincipalToken'));
		$response = Http::asForm()->withHeaders([
			'Accept' => 'application/json'
		])->post(
			$serviePrincialTokenURL,
			[
				'resource'      => 'https://analysis.windows.net/powerbi/api',
				'client_id'     => $connectionData['client_id'],
				'client_secret' => $connectionData['client_secret'],
				'grant_type'    => 'client_credentials',
			]
		);
		if (!$response->successful()) return false;
		$responseBody = json_decode($response->getBody()->getContents(), true);
		return $responseBody['access_token'];
	}

	private function isTokenExpired(): bool
	{
		return !Cache::has("powerbi_token_{$this->currentAccountId}");
	}
}
