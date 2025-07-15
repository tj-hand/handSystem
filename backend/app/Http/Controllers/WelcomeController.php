<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Log;
use App\Models\PBIObject;
use App\Services\ProfileService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class WelcomeController extends Controller
{
	protected string $userId;
	protected string $currentClientId;
	protected string $currentAccountId;

	protected ProfileService $profileService;
	protected PermissionService $permissionService;

	public function __construct(ProfileService $profileService, PermissionService $permissionService)
	{
		$this->profileService = $profileService;
		$this->permissionService = $permissionService;

		$this->userId = $permissionService::UserGlobalProperties()->id;
		$this->currentClientId = $permissionService::UserCurrentAccountProperties()->current_client;
		$this->currentAccountId = $permissionService::UserGlobalProperties()->current_account;
	}

	public function bookmarks()
	{
		try {
			$objects = $this->getUserBookmarks();
			return Que::passa(true, 'auth.welcome.bookmarks.success', '', null, ['bookmarks' => $objects]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.welcome.bookmarks.error');
		}
	}

	public function lastviews()
	{
		try {
			$objects = $this->getUserLastViewedObjects();
			return Que::passa(true, 'auth.welcome.lastviews.success', '', null, ['lastViews' => $objects]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.welcome.lastviews.error');
		}
	}


	private function getAllowedPbiObjectIds()
	{
		return $this->profileService->pbiObjects()->pluck('id');
	}

	private function getUserBookmarks()
	{
		return PBIObject::select('custom_pbi_objects.id', 'custom_pbi_objects.local_name')
			->join('custom_pbi_bookmarks', 'custom_pbi_objects.id', '=', 'custom_pbi_bookmarks.object_id')
			->where('custom_pbi_bookmarks.user_id', $this->userId)
			->where('custom_pbi_bookmarks.client_id', $this->currentClientId)
			->whereIn('custom_pbi_objects.id', $this->getAllowedPbiObjectIds())
			->get();
	}

	private function getUserLastViewedObjects()
	{
		$lastViewedIds = Log::where('success', true)
			->where('log_message', 'auth.bis.rendered')
			->where('user_id', $this->permissionService->user()->id)
			->where('account_id', $this->currentAccountId)
			->where('client_id', $this->currentClientId)
			->where('object_type', PBIObject::class)
			->orderByDesc('created_at')
			->limit(5)
			->pluck('object_id');

		return PBIObject::select('custom_pbi_objects.id', 'custom_pbi_objects.local_name')
			->whereIn('custom_pbi_objects.id', $this->getAllowedPbiObjectIds())
			->whereIn('custom_pbi_objects.id', $lastViewedIds)
			->get();
	}
}
