<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScopedRelationship;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class AuthorizationController extends Controller
{

	protected $currentAccount;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$this->currentAccount = $permissionService->UserGlobalProperties()->current_account;
	}

	public function queue()
	{
		try {
			$queue = [];
			$queue['group_users'] = $this->groupUsers();
			$queue['group_actions'] = $this->groupActions();
			return Que::passa(true, 'auth.authorization.queued', '', null, ['queue' => $queue]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.queue');
		}
	}

	public function set(Request $request)
	{
		try {
			$ids = $request->input('ids');
			$operation = $request->input('operation_type');

			return match ($operation) {
				'approve' => $this->approve($ids),
				'refuse'  => $this->refuse($ids)
			};

			return Que::passa(false, 'auth.authorization.invalid_operation', 'auth.authorization.set');
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.set');
		}
	}

	private function approve($ids)
	{
		$userId = $this->permissionService->UserGlobalProperties()->id;
		$userName = $this->permissionService->user()->name;

		try {
			$authorizations = ScopedRelationship::whereIn('id', $ids)->get();
			foreach ($authorizations as $authorization) {
				$authorization->authorized = true;
				$authorization->authorized_by_id = $userId;
				$authorization->authorized_by_name = $userName;
				$authorization->authorization_timestamp = Carbon::now();
				$authorization->save();
			}
			return Que::passa(true, 'auth.authorization_queue.approved', implode(',', $ids));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.approve ' . implode(',', $ids));
		}
	}

	private function refuse($ids)
	{
		try {
			ScopedRelationship::whereIn('id', $ids)->delete();
			return Que::passa(true, 'auth.authorization_queue.deleted', implode(',', $ids));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.delete ' . implode(',', $ids));
		}
	}


	private function groupUsers()
	{
		return ScopedRelationship::select('admin_scoped_relationships.id', 'admin_groups.name AS parent', 'users.name AS child', DB::raw('false as translate'))
			->join('admin_groups', 'admin_scoped_relationships.belongs_to_id', '=', 'admin_groups.id')
			->join('users_global_properties', 'admin_scoped_relationships.object_id', '=', 'users_global_properties.id')
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->where('belongs_to_type', 'App\Models\Group')
			->where('object_type', 'App\Models\UserGlobalProperties')
			->where('scope_type', 'App\Models\Account')
			->where('scope_id', $this->currentAccount)
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('admin_groups.name')
			->orderBy('users.name')
			->get();
	}

	private function groupActions()
	{
		return ScopedRelationship::select('admin_scoped_relationships.id', 'admin_groups.name AS parent', 'admin_actions.identifier AS child', DB::raw('true as translate'))
			->join('admin_groups', 'admin_scoped_relationships.belongs_to_id', '=', 'admin_groups.id')
			->join('admin_actions', 'admin_scoped_relationships.object_id', '=', 'admin_actions.id')
			->where('belongs_to_type', 'App\Models\Group')
			->where('object_type', 'App\Models\Action')
			->where('scope_type', 'App\Models\Account')
			->where('scope_id', $this->currentAccount)
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('admin_groups.name')
			->orderBy('admin_actions.sort_order')
			->get();
	}
}
