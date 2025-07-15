<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Import Models
use App\Models\Group;
use App\Models\Action;
use App\Models\Client;
use App\Models\ScopedRelationship;
use App\Models\UserGlobalProperties;

// Import Services
use App\Services\AccountService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;
use App\Services\ScopedRelationshipService;

class GroupController extends Controller
{

	protected $record = [];
	protected $groupId = '';
	protected $group = null;
	protected $newRecord = false;
	protected $currentAccountId = '';
	protected AccountService $accountService;
	protected PermissionService $permissionService;

	public function __construct(Request $request, AccountService $accountService, PermissionService $permissionService)
	{

		$this->record = $request->input('record');
		$this->groupId = data_get($request->input('record'), 'id', $request->input('id'));

		$this->accountService = $accountService;
		$this->permissionService = $permissionService;
		$this->currentAccountId = PermissionService::UserGlobalProperties()->current_account;
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

			return Que::passa(true, 'auth.client.show', '', $this->group, ['group'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.client.show ' . $this->groupId);
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
			return Que::passa(true, 'auth.group.deleted', '', $this->group);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.group.delete.error ' . $this->group);
		}
	}

	private function create()
	{
		try {
			DB::beginTransaction();

			if (!$this->permissionService::hasPermission('Groups.auth.groups.add'))
				return Que::passa(false, 'auth.groups.add.error.unauthorized');

			$this->group = Group::create($this->record);
			$this->groupId = $this->group->id;
			$this->setGroupRelationsship();
			if (!empty($this->record['group.associated_users'])) $this->setAssociatedUsers();
			if (!empty($this->record['group.associated_actions'])) $this->setAssociatedActions();
			DB::commit();
			return Que::passa(true, 'auth.groups.created', '', $this->group, ['group'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.group.create ' . $this->groupId);
		}
	}

	private function update()
	{
		if (!$this->permissionService::hasPermission('Groups.auth.groups.edit'))
			return Que::passa(false, 'auth.groups.edit.error.unauthorized');

		try {
			DB::beginTransaction();
			$this->group->name = $this->record['name'];
			$this->group->is_active = $this->record['is_active'];
			$this->group->scope = $this->record['scope'];
			$this->group->description = $this->record['description'];
			$this->group->save();

			ScopedRelationship::where('object_type', 'App\Models\Group')->where('object_id', $this->groupId)->delete();
			$this->setGroupRelationsship();
			if (!empty($this->record['group.associated_users'])) $this->setAssociatedUsers();
			if (!empty($this->record['group.associated_actions']))  $this->setAssociatedActions();
			DB::commit();
			return Que::passa(true, 'auth.groups.updated', '', $this->group, ['group'  => ['record' => $this->group]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.groups.create ' . $this->groupId);
		}
	}

	public function associatedUsers(Request $request)
	{

		$groupId = $request->input('id');

		if ($groupId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(UserGlobalProperties::class);
			return Que::passa(true, 'auth.group.associated_users.listed', '', null, ['list' => $list]);
		}

		$group = Group::find($groupId);
		if (!$group) return Que::passa(false, 'auth.group.associat_users.error.group_not_found', $groupId);

		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($group, UserGlobalProperties::class);
		return Que::passa(true, 'auth.group.associated_users.listed', '', $group, ['list' => $list]);
	}

	public function associatedActions(Request $request)
	{
		$groupId = $request->input('id');

		if ($groupId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(Action::class);
			return Que::passa(true, 'auth.group.associated_actions.listed', '', null, ['list' => $list]);
		}

		$group = Group::find($groupId);
		if (!$group) return Que::passa(false, 'auth.group.associated_actions.error.group_not_found', $groupId);
		$scope = $group->scope == 'client' ? 'App\Models\Client' : 'App\Models\Account';
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($group, Action::class, $scope);
		return Que::passa(true, 'auth.group.actions.listed', '', null, ['list' => $list]);
	}

	private function validateAndLoadGroup($operation)
	{
		// Handle new record
		if (is_array($this->record) && !array_key_exists('id', $this->record)) {
			$this->newRecord = true;
			return true;
		}

		// Validate UUID format
		if (!Str::isUuid($this->groupId)) return Que::passa(false, 'auth.group.' . $operation . '.invalid_id', $this->groupId);

		// Try to find the client
		$this->group = Group::find($this->groupId);
		if (!$this->group) return Que::passa(false, 'auth.group.' . $operation . '.not_found', $this->group);

		return true;
	}

	private function grantOperation($operation)
	{
		if (!$this->permissionService::hasPermission('Groups.auth.groups.' . $operation))
			return Que::passa(false, 'auth.groups.' . $operation . '.error.unauthorized');

		if (!$this->accountService->groups()->contains('id', $this->groupId))
			return Que::passa(false, 'auth.groups.show.unauthorized', $this->groupId);

		return true;
	}

	private function setGroupRelationsship()
	{

		$currentClientId = $this->permissionService::UserCurrentAccountProperties()->current_client;

		$scope = $this->record['scope'];
		$belongsToType = $scope === 'account' ? 'Account' : 'Client';
		$belongsToId   = $scope === 'account' ? $this->currentAccountId : $currentClientId;

		return app(ScopedRelationshipService::class)->setRelationship(
			'Group',
			$this->groupId,
			$belongsToType,
			$belongsToId,
			'Account',
			$this->currentAccountId,
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
			items: $this->record['group.associated_users'],
			scopeType: 'App\Models\Client',
			relatedObjectType: 'App\Models\UserGlobalProperties',
			grantField: 'group_users'
		);
	}

	private function setAssociatedActions()
	{
		return app(ScopedRelationshipService::class)->syncScopedRelationships(
			parentEntity: $this->group,
			items: $this->record['group.associated_actions'],
			scopeType: 'App\Models\Client',
			relatedObjectType: 'App\Models\Action',
			grantField: 'group_actions'
		);
	}
}
