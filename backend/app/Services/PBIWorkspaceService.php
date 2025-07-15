<?php

namespace App\Services;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Import Models
use App\Models\Client;
use App\Models\PBIObject;
use App\Models\PBIWorkspace;

// Import Services
use App\Services\AccountService;
use App\Services\PBIRequestService;
use App\Services\SystemLogService as Que;

class PBIWorkspaceService
{
	protected string $workspaceId = '';
	protected string $currentAccountId;
	protected ?PBIWorkspace $workspace = null;

	public function __construct(
		protected AccountService $accountService,
		protected PermissionService $permissionService,
		protected PBIRequestService $pbiRequestService
	) {
		$this->currentAccountId = $this->permissionService->UserGlobalProperties()->current_account;
	}

	public function syncWorkspaces()
	{
		try {
			PBIWorkspace::where('checked', 1)->where('account_id', $this->currentAccountId)->update(['checked' => 0]);
			$workspaces = $this->getWorkspaces();

			foreach ($workspaces['value'] as $workspace) {
				$db_workspace = PBIWorkspace::where('microsoft_id', $workspace['id'])->where('account_id', $this->currentAccountId)->first();
				if (!$db_workspace) {
					$new_workspace = new PBIWorkspace;
					$new_workspace->account_id = $this->currentAccountId;
					$new_workspace->microsoft_id = $workspace['id'];
					$new_workspace->microsoft_name = $workspace['name'];
					$new_workspace->microsoft_type = $workspace['type'];
					$new_workspace->local_name = $workspace['name'];
					$new_workspace->is_active = 1;
					$new_workspace->checked = 1;
					$new_workspace->save();
				} else {
					$db_workspace->local_name = $workspace['name'];
					$db_workspace->checked = 1;
					$db_workspace->save();
				}
			}

			PBIWorkspace::where('checked', false)->where('account_id', $this->currentAccountId)->delete();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	private function getWorkspaces()
	{
		$endpoint = config('powerbi.getWorkspaces');
		return $this->pbiRequestService->makeRequest('GET', $endpoint);
	}

	public function show($request)
	{
		try {

			$this->workspaceId = $request->id;
			// Validate the operation
			$validationAndLoad = $this->validateAndLoadWorkspace('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			return Que::passa(true, 'auth.workspace.show', '', $this->workspace, ['workspace'  => ['record' => $this->workspace]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.workspace.show ' . $this->workspace);
		}
	}

	public function upsert($request)
	{
		if (!$this->permissionService::hasPermission('Workspaces.auth.workspaces.edit'))
			return Que::passa(false, 'auth.workspaces.edit.error.unauthorized');

		try {
			DB::beginTransaction();
			$this->workspaceId = $request->record['id'];
			// Validate the operation
			$validationAndLoad = $this->validateAndLoadWorkspace('edit');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$this->workspace->local_name = $request->record['local_name'];
			$this->workspace->is_active = $request->record['is_active'];
			$this->workspace->description = $request->record['description'];
			$this->workspace->save();

			if (!empty($request->record['powerbi.workspace.associated_clients'])) $this->updateAssociatedClients($request->record['powerbi.workspace.associated_clients']);
			DB::commit();
			return Que::passa(true, 'auth.workspaces.updated', '', $this->workspace, ['workspace'  => ['record' => $this->workspace]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.workspaces.update ' . $this->workspaceId);
		}
	}

	private function updateAssociatedClients($items)
	{
		return app(ScopedRelationshipService::class)->syncReverseScopedRelationships(
			childEntity: $this->workspace,
			items: $items,
			scopeType: 'App\Models\Client',
			parentObjectType: 'App\Models\Client',
			grantField: 'client_workspaces'
		);
	}

	public function delete($request)
	{
		$this->workspaceId = $request->id;
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadWorkspace('delete');
		if ($validationAndLoad !== true) return $validationAndLoad;

		// Check grant
		$grantOperation = $this->grantOperation('delete');
		if ($grantOperation !== true) return $grantOperation;

		try {
			DB::beginTransaction();
			PBIObject::where('workspace_id', $this->workspaceId)->delete();
			$this->workspace->delete();
			DB::commit();
			return Que::passa(true, 'auth.client.deleted', '', $this->workspace);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.workspace.delete.error ' . $this->workspace);
		}
	}

	public function associatedClients($request)
	{
		$this->workspaceId = $request->id;
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadWorkspace('show');
		if ($validationAndLoad !== true) return $validationAndLoad;

		// Check grant
		$grantOperation = $this->grantOperation('show');
		if ($grantOperation !== true) return $grantOperation;

		$list = app(ScopedRelationshipService::class)->makeReverseScopedListWithRelations($this->workspace, Client::class, Client::class);
		return Que::passa(true, 'auth.workspace.associated_with_clients.listed', '', null, ['list' => $list]);
	}

	private function validateAndLoadWorkspace($operation)
	{
		// Validate UUID format
		if (!Str::isUuid($this->workspaceId)) return Que::passa(false, 'auth.workspace.' . $operation . '.invalid_id', $this->workspaceId);

		// Try to find the client
		$this->workspace = PBIWorkspace::find($this->workspaceId);
		if (!$this->workspace) return Que::passa(false, 'auth.workspace.' . $operation . '.not_found', $this->workspaceId);

		return true;
	}

	private function grantOperation($operation)
	{
		if (!$this->permissionService::hasPermission('Workspaces.auth.workspaces.' . $operation))
			return Que::passa(false, 'auth.workspaces.' . $operation . '.error.unauthorized');

		if (!$this->accountService->workspaces()->contains('id', $this->workspaceId))
			return Que::passa(false, 'auth.client.show.unauthorized', $this->workspaceId);

		return true;
	}
}
