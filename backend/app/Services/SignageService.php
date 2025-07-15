<?php

namespace App\Services;

// Import Models
use App\Models\Signage;

// Import Services
use App\Services\PermissionService;


class SignageService
{

	protected string $currentClientId = '';

	public function __construct(
		protected PermissionService $permissionService
	) {
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function signages()
	{
		return Signage::where('client_id', $this->currentClientId)->orderBy('name')->get();
	}
}
