<?php

namespace App\Http\Controllers;

// Import Tools

use Exception;
use Illuminate\Http\Request;

// Import Models
use App\Models\PBIObject;
use App\Models\PBIWorkspace;

// Import Services
use App\Services\ProfileService;
use App\Services\PBIObjectService;
use App\Services\PBIRequestService;
use App\Services\PermissionService;
use App\Services\PBIWorkspaceService;
use App\Services\SystemLogService as Que;

class PowerBIController extends Controller
{
	protected string $currentAccountId;

	public function __construct(
		protected ProfileService $profileService,
		protected PBIObjectService $pbiObjectService,
		protected PBIRequestService $pbiRequestService,
		protected PermissionService $permissionService,
		protected PBIWorkspaceService $pbiWorkspaceService,
	) {
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
	}

	public function sync()
	{
		try {
			$syncWorkspaces = $this->pbiWorkspaceService->syncWorkspaces();
			if (!$syncWorkspaces) return Que::passa(false, 'auth.powerbi.syncWorkspaces.error');
			$this->resetSyncFlags();
			$this->syncAllObjects();
			$this->destroyNotFoundObjects();
			return Que::passa(true, 'auth.powerbi.sync');
		} catch (Exception $e) {
			return Que::passa(false, 'auth.powerbi.sync.error');
		}
	}

	private function resetSyncFlags()
	{
		return PBIObject::join('custom_pbi_workspaces', 'custom_pbi_objects.workspace_id', '=', 'custom_pbi_workspaces.id')
			->where('custom_pbi_workspaces.account_id', $this->currentAccountId)
			->where('custom_pbi_objects.checked', 1)
			->update(['custom_pbi_objects.checked' => 0]);
	}

	private function syncAllObjects()
	{
		$objects = $this->getObjects();
		$this->syncObject($objects['reports'], 'reports');
		$this->syncObject($objects['dashboards'], 'dashboards');
	}

	private function getObjects()
	{
		$endpoint_reports = config('powerbi.getReports');
		$endpoint_dashboards = config('powerbi.getDashboards');
		$workspaces = PBIWorkspace::where('account_id', $this->currentAccountId)->get();
		foreach ($workspaces as $workspace) {
			$objects['reports'][$workspace->id] = $this->pbiRequestService->makeRequest('GET', $endpoint_reports, ['workspaceId' => $workspace->microsoft_id]);
			$objects['dashboards'][$workspace->id] = $this->pbiRequestService->makeRequest('GET', $endpoint_dashboards, ['workspaceId' => $workspace->microsoft_id]);
		}
		return $objects;
	}

	private function syncObject($objects, $type)
	{

		foreach ($objects as $workspaceId => $objectsGroup) {
			foreach ($objectsGroup['value'] as $objetc) {

				$objectQuery = PBIObject::where('microsoft_id', $objetc['id'])
					->where('account_id', $this->currentAccountId)
					->where('workspace_id', $workspaceId)
					->first();

				if (!$objectQuery) {
					$newObject = new PBIObject();
					$newObject->account_id = $this->currentAccountId;
					$newObject->workspace_id = $workspaceId;
					$newObject->microsoft_id = $objetc['id'];
					if ($type == 'reports') {
						$newObject->microsoft_type = $objetc['reportType'];
						$newObject->microsoft_name = $objetc['name'];
					} else {
						$newObject->microsoft_type = "Dashboard";
						$newObject->microsoft_name = $objetc['displayName'];
					}
					$newObject->is_active = true;
					$newObject->checked = true;
					$newObject->save();
				} else {
					$type == 'reports' ?
						$objectQuery->local_name = $objetc['name'] :
						$objectQuery->local_name = $objetc['displayName'];
					$objectQuery->checked = true;
					$objectQuery->save();
				}
			}
		}
	}

	private function destroyNotFoundObjects()
	{
		return PBIObject::join('custom_pbi_workspaces', 'custom_pbi_objects.workspace_id', '=', 'custom_pbi_workspaces.id')
			->where('custom_pbi_workspaces.account_id', $this->currentAccountId)
			->where('custom_pbi_objects.checked', 0)
			->delete();
	}

	public function workspaceShow(Request $request)
	{
		return $this->pbiWorkspaceService->show($request);
	}

	public function workspaceUpsert(Request $request)
	{
		return $this->pbiWorkspaceService->upsert($request);
	}

	public function workspaceDelete(Request $request)
	{
		return $this->pbiWorkspaceService->delete($request);
	}

	public function workspaceAssociatedClients(Request $request)
	{
		return $this->pbiWorkspaceService->associatedClients($request);
	}

	public function bisList()
	{
		if (!$this->permissionService::hasPermission('BIs.auth.bis.module'))
			return Que::passa(false, 'auth.profile.bis.list.error.unauthorized');
		try {
			$pbiObjects = $this->profileService->pbiObjects();
			return Que::passa(true, 'auth.account.clients.list', '', null, ['pbi_objects' => $pbiObjects]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.profiles.bis');
		}
	}

	public function bisShow(Request $request)
	{
		return $this->pbiObjectService->show($request);
	}

	public function bisUpsert(Request $request)
	{
		return $this->pbiObjectService->upsert($request);
	}

	public function bisDelete(Request $request)
	{
		return $this->pbiObjectService->delete($request);
	}

	public function bisAssociatedProfiles(Request $request)
	{
		return $this->pbiObjectService->associated_profiles($request);
	}

	public function bisRender(Request $request)
	{
		return $this->pbiObjectService->render($request);
	}

	public function bisPages(Request $request)
	{
		return $this->pbiObjectService->pages($request);
	}

	public function bisPage(Request $request)
	{
		return $this->pbiObjectService->page($request);
	}

	public function bisCreateImage(Request $request)
	{
		return $this->pbiObjectService->createImage($request);
	}

	public function bisDestroyImage(Request $request)
	{
		return $this->pbiObjectService->destroyImage($request);
	}

	public function bisBookmark(Request $request)
	{
		return $this->pbiObjectService->bookmark($request);
	}
}
