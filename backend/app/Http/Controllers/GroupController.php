<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Action;
use App\Models\Group;
use App\Models\ActionSet;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AccountService;
use App\Models\ScopedRelationship;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;
use App\Services\ScopedRelationshipService;

class GroupController extends Controller
{

	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
	}

	public function show(Request $request)
	{
		try {
			if (!Str::isUuid($request->input('id'))) return Que::passa(false, 'auth.user.show.invalid_id', $request->input('id'));
			if (!$this->permissionService::hasPermission('Groups.auth.groups.show'))
				return Que::passa(false, 'auth.groups.show.error.unauthorized');

			$accountId = PermissionService::UserGlobalProperties()->current_account;
			$groups = (new AccountService($accountId))->groups();
			$exists = $groups->contains('id', $request->input('id'));
			if (!$exists) return Que::passa(false, 'auth.group.show.unauthorized', $request->input('id'));
			$group = Group::find($request->input('id'));
			return $group
				? Que::passa(true, 'auth.group.show', '', $group, ['group'  => ['record' => $group]])
				: Que::passa(false, 'auth.group.show.error.group_not_found', $request->input('id'));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.group.show ' . $request->input('id'));
		}
	}

	public function upsert(Request $request)
	{
		$message = '';
		$data = $request->input('record', []);

		try {
			DB::beginTransaction();
			if (!empty($data['id'])) {
				$message = 'auth.groups.updated';
				$group = Group::find($data['id']);
				if (!$group) return Que::passa(false, 'auth.groups.upsert.error.group_not_found', $data['id']);
				$group->name = $data['name'];
				$group->is_active = $data['is_active'];
				$group->description = $data['description'];
				$group->save();
			} else {
				$message = 'auth.groups.created';
				$accountId = $this->permissionService->UserGlobalProperties()->current_account;
				$group = Group::create($data);
				app(ScopedRelationshipService::class)->setRelationship('Group', $group->id, 'Account', $accountId, 'Account', $accountId, false, true, 'System', Carbon::now());
			}
			if (!empty($data['group.associated_users']))
				app(ScopedRelationshipService::class)->syncScopedRelationships(
					parentEntity: $group,
					items: $data['group.associated_users'],
					scopeType: 'App\Models\Account',
					relatedObjectType: 'App\Models\UserGlobalProperties',
					grantField: 'groups_and_account_users'
				);
			if (!empty($data['group.associated_actions']))
				app(ScopedRelationshipService::class)->syncScopedRelationships(
					parentEntity: $group,
					items: $data['group.associated_actions'],
					scopeType: 'App\Models\Account',
					relatedObjectType: 'App\Models\Action',
					grantField: 'groups_and_actions'
				);

			DB::commit();
			return Que::passa(true, $message, '', $group, ['group'  => ['record' => $group]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.groups.upsert ' . ($data['id'] ?? 'new'));
		}
	}

	public function delete(Request $request)
	{
		$validator = Validator::make($request->all(), ['id' => 'uuid']);
		if ($validator->fails()) return Que::passa(false, 'auth.group.error.delete.error.invalid_id_type');

		if (!$this->permissionService::hasPermission('Groups.auth.groups.delete'))
			return Que::passa(false, 'auth.groups.delete.error.unauthorized');

		try {

			DB::beginTransaction();
			$group = Group::find($request->input('id'));
			if (!$group) return Que::passa(false, 'auth.group.delete.error.group_not_found', $request->input('id'));
			$accountId = $this->permissionService->UserGlobalProperties()->current_account;

			ScopedRelationship::where('object_id', $request->input('id'))
				->where('object_type', 'App\Models\Group')
				->where('belongs_to_type', 'App\Models\Account')
				->where('scope_type', 'App\Models\Account')
				->where('belongs_to_id', $accountId)
				->where('scope_id', $accountId)
				->delete();

			ScopedRelationship::where('object_type', 'App\Models\Group')
				->where('belongs_to_type', 'App\Models\Group')
				->where('scope_type', 'App\Models\Account')
				->where('belongs_to_id', $group->id)
				->where('scope_id', $accountId)
				->delete();

			$group->delete();
			DB::commit();
			return Que::passa(true, 'auth.group.deleted', '', $group);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.gorup.delete.error');
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
			return Que::passa(true, 'auth.group.associated_users.listed', '', null, ['list' => $list]);
		}

		$group = Group::find($groupId);
		if (!$group) return Que::passa(false, 'auth.group.associat_users.error.group_not_found', $groupId);
		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($group, Action::class);
		return Que::passa(true, 'auth.group.actions.listed', '', null, ['list' => $list]);
	}
}
