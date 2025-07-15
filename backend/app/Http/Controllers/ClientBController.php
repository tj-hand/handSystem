<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// Import Models
use App\Models\User;
use App\Models\Client;
use App\Models\GrantConfig;
use App\Models\PBIWorkspace;
use App\Models\ScopedRelationship;
use App\Models\UserClientProperties;

// Import Services
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class ClientBController extends Controller
{
	private const ERROR_ACCESS_DENIED = 'access_denied';
	private const ERROR_INVALID_REQUEST = 'invalid_request';
	private const ERROR_RECORD_NOT_FOUND = 'record_not_found';

	private const GRANT_CONFIG_FIELDS = [
		'profile_users',
		'profile_objects',
		'group_users',
		'group_actions',
		'client_workspaces',
		'user_local_actions',
	];

	private ?Client $client = null;
	private string $errorType = '';
	private string $currentAccountId;
	private bool $isNewRecord = false;
	private string $errorMessage = '';
	private bool $hasValidAccess = false;
	private ?GrantConfig $grantConfig = null;

	public function __construct(
		private Request $request,
		private PermissionService $permissionService,
		private Que $que
	) {
		$this->currentAccountId = $this->permissionService::UserGlobalProperties()->current_account;
		$this->isNewRecord = $this->request->id === 'new';

		$this->initializeClient();
	}

	/**
	 * Display the specified client resource.
	 */
	public function show(): JsonResponse
	{
		if (!$this->hasValidAccess) return $this->handleAccessError('auth.client.show');
		$clientData = $this->client->only(['id', 'name', 'is_active']);
		$grantData = $this->grantConfig->only(self::GRANT_CONFIG_FIELDS);
		$record = array_merge($clientData, $grantData);
		return Que::passa(true, 'auth.client.show', '', $this->client, ['client' => ['record' => $record]]);
	}

	/**
	 * Create or update the specified client resource.
	 */
	public function upsert(): JsonResponse
	{
		if (!$this->hasValidAccess) return $this->handleAccessError('auth.client.upsert');

		try {
			return DB::transaction(function () {
				$this->updateClient();
				$this->updateGrantConfig();
				$action = $this->isNewRecord ? 'created' : 'updated';
				Que::passa(true, 'auth.client.' . $action, '', $this->client);
				return $this->show();
			});
		} catch (ValidationException $e) {
			return Que::passa(false, 'auth.client.update.invalid', $e->getMessage(), $this->client);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.client.update.error', $e->getMessage(), $this->client);
		}
	}

	/**
	 * Remove the specified client resource from storage.
	 */
	public function destroy(): JsonResponse
	{
		if (!$this->hasValidAccess) return $this->handleAccessError('auth.client.destroy');

		try {
			return DB::transaction(function () {
				$this->grantConfig->delete();
				$this->client->delete();
				return Que::passa(true, 'auth.client.deleted', '', $this->client);
			});
		} catch (Exception $e) {
			return Que::passa(false, 'auth.client.delete.error', $e->getMessage(), $this->client);
		}
	}

	/**
	 * Get users associated with the client.
	 */
	public function getAssociatedUsers(): JsonResponse
	{
		if (!$this->hasValidAccess) return $this->handleAccessError('auth.client.associated_users');

		$waitingUserIds = $this->getPendingUserIds();
		$availableUsers = $this->getAvailableUsers();
		$associatedUserIds = $this->getAuthorizedUserIds();

		$userList = $availableUsers->map(function ($user) use ($associatedUserIds, $waitingUserIds) {
			$user->active = $this->determineUserStatus($user->id, $associatedUserIds, $waitingUserIds);
			return $user;
		});

		return response()->json($userList);
	}

	/**
	 * Get workspaces associated with the client.
	 */
	public function getAssociatedWorkspaces(): JsonResponse
	{
		if (!$this->hasValidAccess) return $this->handleAccessError('auth.client.associated_workspaces');

		$waitingWorkspaceIds = $this->getPendingWorkspaceIds();
		$availableWorkspaces = $this->getAvailableWorkspaces();
		$associatedWorkspaceIds = $this->getAuthorizedWorkspaceIds();

		$workspaceList = $availableWorkspaces->map(function ($workspace) use ($associatedWorkspaceIds, $waitingWorkspaceIds) {
			$workspace->active = $this->determineWorkspaceStatus($workspace->id, $associatedWorkspaceIds, $waitingWorkspaceIds);
			return $workspace;
		});

		return response()->json($workspaceList);
	}

	/**
	 * Initialize client based on request (new or existing).
	 */
	private function initializeClient(): void
	{
		$this->isNewRecord ? $this->createNewClient() : $this->loadExistingClient();
		$this->validateAccess();
	}

	/**
	 * Create a new client instance.
	 */
	private function createNewClient(): void
	{
		$this->client = Client::create(['account_id' => $this->currentAccountId]);
		$this->grantConfig = GrantConfig::create(['object_type' => Client::class, 'object_id' => $this->client->id]);
	}

	/**
	 * Load existing client from database.
	 */
	private function loadExistingClient(): void
	{
		if (!Str::isUuid($this->request->id)) {
			$this->setError(self::ERROR_INVALID_REQUEST, 'Invalid UUID format');
			return;
		}

		$this->client = Client::find($this->request->id);
		if (!$this->client) {
			$this->setError(self::ERROR_RECORD_NOT_FOUND, 'Client not found');
			return;
		}

		$this->grantConfig = GrantConfig::where('object_type', Client::class)->where('object_id', $this->client->id)->first();
		if (!$this->grantConfig) $this->setError(self::ERROR_RECORD_NOT_FOUND, 'Grant configuration not found');
	}

	/**
	 * Set error type and message.
	 */
	private function setError(string $type, string $message): void
	{
		$this->errorType = $type;
		$this->errorMessage = $message;
	}

	/**
	 * Handle access errors with proper error type context.
	 */
	private function handleAccessError(string $action): JsonResponse
	{
		$logKey = match ($this->errorType) {
			self::ERROR_RECORD_NOT_FOUND => $action . '.not_found',
			self::ERROR_ACCESS_DENIED => $action . '.access_denied',
			self::ERROR_INVALID_REQUEST => $action . '.invalid_request',
			default => $action . '.error'
		};
		return Que::passa(false, $logKey, $this->errorMessage . ' (Request ID: ' . $this->request->id . ')', $this->client);
	}

	/**
	 * Check if error is of specific type.
	 */
	public function isInvalidRequest(): bool
	{
		return $this->errorType === self::ERROR_INVALID_REQUEST;
	}

	/**
	 * Check if error is record not found.
	 */
	public function isRecordNotFound(): bool
	{
		return $this->errorType === self::ERROR_RECORD_NOT_FOUND;
	}

	/**
	 * Check if error is access denied.
	 */
	public function isAccessDenied(): bool
	{
		return $this->errorType === self::ERROR_ACCESS_DENIED;
	}

	/**
	 * Get current error type.
	 */
	public function getErrorType(): string
	{
		return $this->errorType;
	}

	/**
	 * Validate user access to the client.
	 */
	private function validateAccess(): void
	{
		$this->hasValidAccess = empty($this->errorType) &&
			$this->client !== null &&
			$this->grantConfig !== null;
	}

	/**
	 * Update client with validated data.
	 */
	private function updateClient(): void
	{
		$validatedData = $this->validateClientData();
		$this->client->update($validatedData);
	}

	/**
	 * Update grant configuration with validated data.
	 */
	private function updateGrantConfig(): void
	{
		$validatedData = $this->validateGrantData();
		$this->grantConfig->update($validatedData);
	}

	/**
	 * Validate client input data.
	 */
	private function validateClientData(): array
	{
		$data = $this->request->only(['name', 'is_active', 'description']);
		$data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		return Validator::make($data, [
			'name' => 'required|string|max:255',
			'is_active' => 'required|boolean',
			'description' => 'nullable|string|max:1000',
		])->validate();
	}

	/**
	 * Validate grant configuration data.
	 */
	private function validateGrantData(): array
	{
		$data = $this->request->only(self::GRANT_CONFIG_FIELDS);

		foreach (self::GRANT_CONFIG_FIELDS as $field) {
			$data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		}

		$rules = array_fill_keys(self::GRANT_CONFIG_FIELDS, 'required|boolean');
		return Validator::make($data, $rules)->validate();
	}

	/**
	 * Get authorized user IDs for the client.
	 */
	private function getAuthorizedUserIds()
	{
		return UserClientProperties::join('users_global_properties', 'users_clients_properties.user_id', '=', 'users_global_properties.user_id')
			->where('users_clients_properties.client_id', $this->client->id)
			->where('users_clients_properties.authorized', true)
			->pluck('users_global_properties.id');
	}

	/**
	 * Get pending user IDs for the client.
	 */
	private function getPendingUserIds()
	{
		return UserClientProperties::join('users_global_properties', 'users_clients_properties.user_id', '=', 'users_global_properties.user_id')
			->where('users_clients_properties.client_id', $this->client->id)
			->where('users_clients_properties.requires_authorization', true)
			->where('users_clients_properties.authorized', false)
			->pluck('users_global_properties.id');
	}

	/**
	 * Get available users for the account.
	 */
	private function getAvailableUsers()
	{
		return User::select('users_global_properties.id', 'users.name AS title', 'users.email AS subtitle')
			->join('users_global_properties', 'users_global_properties.user_id', '=', 'users.id')
			->join('users_accounts_properties', 'users_accounts_properties.user_id', '=', 'users.id')
			->where('users_global_properties.is_superuser', false)
			->where('users_global_properties.is_blocked', false)
			->where('users_accounts_properties.account_id', $this->client->account_id)
			->where('users_accounts_properties.is_active_to_account', true)
			->get();
	}

	/**
	 * Determine user status (active, waiting, or false).
	 */
	private function determineUserStatus(int $userId, $associatedIds, $waitingIds): bool|string
	{
		if ($associatedIds->contains($userId)) return true;
		if ($waitingIds->contains($userId)) return 'waiting';
		return false;
	}

	/**
	 * Get authorized workspace IDs for the client.
	 */
	private function getAuthorizedWorkspaceIds()
	{
		return $this->getWorkspaceRelationships()->where('authorized', true)->pluck('object_id');
	}

	/**
	 * Get pending workspace IDs for the client.
	 */
	private function getPendingWorkspaceIds()
	{
		return $this->getWorkspaceRelationships()->where('requires_authorization', true)->where('authorized', false)->pluck('object_id');
	}

	/**
	 * Get workspace relationships base query.
	 */
	private function getWorkspaceRelationships()
	{
		return ScopedRelationship::where('belongs_to_type', Client::class)
			->where('belongs_to_id', $this->client->id)
			->where('scope_type', Client::class)
			->where('scope_id', $this->client->id)
			->where('object_type', PBIWorkspace::class);
	}

	/**
	 * Get available workspaces for the account.
	 */
	private function getAvailableWorkspaces()
	{
		return PBIWorkspace::select('id', 'local_name')
			->where('account_id', $this->currentAccountId)
			->where('is_active', true)
			->get();
	}

	/**
	 * Determine workspace status (active, waiting, or false).
	 */
	private function determineWorkspaceStatus(int $workspaceId, $associatedIds, $waitingIds): bool|string
	{
		if ($associatedIds->contains($workspaceId)) return true;
		if ($waitingIds->contains($workspaceId)) return 'waiting';
		return false;
	}
}
