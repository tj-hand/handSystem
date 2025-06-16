<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Import Models
use App\Models\Client;
use App\Models\Profile;
use App\Models\PBIObject;
use App\Models\ScopedRelationship;
use App\Models\UserGlobalProperties;

// Import Services
use App\Services\AccountService;
use App\Services\ProfileService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;
use App\Services\ScopedRelationshipService;

class ProfileController extends Controller
{

	protected $record = [];
	protected $groupId = '';
	protected $group = null;
	protected $newRecord = false;
	protected string $currentClientId = '';
	protected string $currentAccountId = '';
	protected ProfileService $profileService;
	protected AccountService $accountService;
	protected PermissionService $permissionService;

	public function __construct(Request $request, AccountService $accountService, PermissionService $permissionService, ProfileService $profileService)
	{

		$this->record = $request->input('record');
		$this->groupId = data_get($request->input('record'), 'id', $request->input('id'));

		$this->accountService = $accountService;
		$this->profileService = $profileService;
		$this->permissionService = $permissionService;
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function show()
	{
		try {

			// Validate the operation
			$validationAndLoad = $this->validateAndLoadGroup('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			return Que::passa(true, 'auth.profile.show', '', $this->group, ['profile'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.profile.show ' . $this->groupId);
		}
	}

	public function upsert()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadGroup('upsert');
		if ($validationAndLoad !== true) return $validationAndLoad;

		if ($this->newRecord) return $this->create();
		return $this->update();
	}

	public function delete()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoadGroup('delete');
		if ($validationAndLoad !== true) return $validationAndLoad;

		// Check grant
		$grantOperation = $this->grantOperation('delete');
		if ($grantOperation !== true) return $grantOperation;

		try {
			DB::beginTransaction();
			$this->group->delete();
			DB::commit();
			return Que::passa(true, 'auth.profile.deleted', '', $this->group);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.profile.delete.error ' . $this->group);
		}
	}

	private function create()
	{
		try {
			DB::beginTransaction();

			if (!$this->permissionService::hasPermission('Profiles.auth.profiles.add'))
				return Que::passa(false, 'auth.profiles.add.error.unauthorized');

			$this->group = Profile::create($this->record);
			$this->groupId = $this->group->id;
			$this->setGroupRelationsship();
			if (!empty($this->record['profile.associated_users'])) $this->setAssociatedUsers();
			if (!empty($this->record['profile.associated_objects'])) $this->setAssociatedObjects();
			DB::commit();
			return Que::passa(true, 'auth.profiles.created', '', $this->group, ['profile'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.profile.create ' . $this->groupId);
		}
	}

	private function update()
	{
		if (!$this->permissionService::hasPermission('Profiles.auth.profiles.edit'))
			return Que::passa(false, 'auth.profiles.edit.error.unauthorized');

		try {
			DB::beginTransaction();
			$this->group->name = $this->record['name'];
			$this->group->is_active = $this->record['is_active'];
			$this->group->scope = 'client';
			$this->group->description = $this->record['description'];
			$this->group->save();

			ScopedRelationship::where('object_type', 'App\Models\Profile')->where('object_id', $this->groupId)->delete();
			$this->setGroupRelationsship();
			if (!empty($this->record['profile.associated_users'])) $this->setAssociatedUsers();
			if (!empty($this->record['profile.associated_objects']))  $this->setAssociatedObjects();
			DB::commit();
			return Que::passa(true, 'auth.profiles.updated', '', $this->group, ['profile'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.profiles.create ' . $this->groupId);
		}
	}

	public function associatedUsers(Request $request)
	{

		$groupId = $request->input('id');

		if ($groupId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(UserGlobalProperties::class);
			return Que::passa(true, 'auth.profile.associated_users.listed', '', null, ['list' => $list]);
		}

		$group = Profile::find($groupId);
		if (!$group) return Que::passa(false, 'auth.profile.associat_users.error.profile_not_found', $groupId);

		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($group, UserGlobalProperties::class, Client::class);
		return Que::passa(true, 'auth.profile.associated_users.listed', '', $group, ['list' => $list]);
	}

	public function associatedObjects(Request $request)
	{
		$groupId = $request->input('id');

		if ($groupId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(PBIObject::class);
			return Que::passa(true, 'auth.profile.associated_actions.listed', '', null, ['list' => $list]);
		}

		$group = Profile::find($groupId);
		if (!$group) return Que::passa(false, 'auth.profile.associated_actions.error.group_not_found', $groupId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($group, PBIObject::class);
		return Que::passa(true, 'auth.profile.actions.listed', '', null, ['list' => $list]);
	}

	private function validateAndLoadGroup($operation)
	{
		// Handle new record
		if (is_array($this->record) && !array_key_exists('id', $this->record)) {
			$this->newRecord = true;
			return true;
		}

		// Validate UUID format
		if (!Str::isUuid($this->groupId)) return Que::passa(false, 'auth.profile.' . $operation . '.invalid_id', $this->groupId);

		// Try to find the client
		$this->group = Profile::find($this->groupId);
		if (!$this->group) return Que::passa(false, 'auth.profile.' . $operation . '.not_found', $this->group);

		return true;
	}

	private function grantOperation($operation)
	{

		if (!$this->permissionService::hasPermission('Profiles.auth.profiles.' . $operation))
			return Que::passa(false, 'auth.profiles.' . $operation . '.error.unauthorized');

		if (!$this->profileService->profiles()->contains('id', $this->groupId))
			return Que::passa(false, 'auth.profiles.show.unauthorized', $this->groupId);

		return true;
	}

	private function setGroupRelationsship()
	{

		return app(ScopedRelationshipService::class)->setRelationship(
			'Profile',
			$this->groupId,
			'Client',
			$this->currentClientId,
			'Client',
			$this->currentClientId,
			false,
			true,
			'System',
			Carbon::now()
		);
	}

	private function setAssociatedUsers()
	{
		return app(ScopedRelationshipService::class)->syncScopedRelationships(
			parentEntity: $this->group,
			items: $this->record['profile.associated_users'],
			scopeType: 'App\Models\Client',
			relatedObjectType: 'App\Models\UserGlobalProperties',
			grantField: 'profile_users'
		);
	}

	private function setAssociatedObjects()
	{
		return app(ScopedRelationshipService::class)->syncScopedRelationships(
			parentEntity: $this->group,
			items: $this->record['profile.associated_objects'],
			scopeType: 'App\Models\Client',
			relatedObjectType: 'App\Models\PBIObject',
			grantField: 'profile_objects'
		);
	}
}
