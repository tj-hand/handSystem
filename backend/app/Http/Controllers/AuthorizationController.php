<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ScopedRelationship;
use App\Models\UserClientProperties;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class AuthorizationController extends Controller
{

	protected $currentClientId;
	protected $currentAccountId;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$this->currentClientId = $permissionService->UserCurrentAccountProperties()->current_client;
		$this->currentAccountId = $permissionService->UserGlobalProperties()->current_account;
	}

	public function queue()
	{
		try {
			$queue = [];
			$queue['client_users'] = $this->clientUsers();
			$queue['group_users'] = $this->groupUsers();
			$queue['group_actions'] = $this->groupActions();
			$queue['user_global_actions'] = $this->userGlobalActions();
			$queue['user_local_actions'] = $this->userLocalActions();
			return Que::passa(true, 'auth.authorization.queued', '', null, ['queue' => $queue]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.queue');
		}
	}

	public function set(Request $request)
	{
		try {
			$items = $request->input('items');
			$operation = $request->input('operation_type');

			return match ($operation) {
				'approve' => $this->approve($items),
				'refuse'  => $this->refuse($items)
			};

			return Que::passa(false, 'auth.authorization.invalid_operation', 'auth.authorization.set');
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.set');
		}
	}

	private function approve($items)
	{
		$userId = $this->permissionService->UserGlobalProperties()->id;
		$userName = $this->permissionService->user()->name;

		try {
			// Separate items by group
			$groupedItems = $this->groupItemsByType($items);
			$processedIds = [];

			// Process client_users group with different logic
			if (!empty($groupedItems['client_users'])) {
				$clientUserIds = $groupedItems['client_users'];
				$this->approveClientUsers($clientUserIds, $userId, $userName);
				$processedIds = array_merge($processedIds, $clientUserIds);
			}

			// Process other groups with standard logic
			if (!empty($groupedItems['others'])) {
				$otherIds = $groupedItems['others'];
				$authorizations = ScopedRelationship::whereIn('id', $otherIds)->get();
				foreach ($authorizations as $authorization) {
					$authorization->authorized = true;
					$authorization->authorized_by_id = $userId;
					$authorization->authorized_by_name = $userName;
					$authorization->authorization_timestamp = Carbon::now();
					$authorization->save();
				}
				$processedIds = array_merge($processedIds, $otherIds);
			}

			return Que::passa(true, 'auth.authorization_queue.approved', implode(',', $processedIds));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.approve ' . $e->getMessage());
		}
	}

	private function refuse($items)
	{
		try {
			// Separate items by group
			$groupedItems = $this->groupItemsByType($items);
			$processedIds = [];

			// Process client_users group with different logic
			if (!empty($groupedItems['client_users'])) {
				$clientUserIds = $groupedItems['client_users'];
				$this->refuseClientUsers($clientUserIds);
				$processedIds = array_merge($processedIds, $clientUserIds);
			}

			// Process other groups with standard logic
			if (!empty($groupedItems['others'])) {
				$otherIds = $groupedItems['others'];
				ScopedRelationship::whereIn('id', $otherIds)->delete();
				$processedIds = array_merge($processedIds, $otherIds);
			}

			return Que::passa(true, 'auth.authorization_queue.deleted', implode(',', $processedIds));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.authorization.delete ' . $e->getMessage());
		}
	}

	/**
	 * Group items by type (client_users vs others)
	 */
	private function groupItemsByType($items)
	{
		$clientUsers = [];
		$others = [];

		foreach ($items as $item) {
			if ($item['group'] === 'client_users') {
				$clientUsers[] = $item['id'];
			} else {
				$others[] = $item['id'];
			}
		}

		return [
			'client_users' => $clientUsers,
			'others' => $others
		];
	}

	/**
	 * Handle approval for client_users group
	 * Replace this with your specific logic for client users
	 */
	private function approveClientUsers($ids, $userId, $userName)
	{
		$authorizations = UserClientProperties::whereIn('id', $ids)->where('client_id', $this->currentClientId)->get();
		foreach ($authorizations as $authorization) {
			$authorization->authorized = true;
			$authorization->authorized_by_id = $userId;
			$authorization->authorized_by_name = $userName;
			$authorization->authorization_timestamp = Carbon::now();
			$authorization->save();
		}
	}

	/**
	 * Handle refusal for client_users group
	 * Replace this with your specific logic for client users
	 */
	private function refuseClientUsers($ids)
	{
		return UserClientProperties::whereIn('user_id', $ids)->where('client_id', $this->currentClientId)->delete();
	}


	private function clientUsers()
	{
		return UserClientProperties::select('users_clients_properties.id', 'admin_clients.name AS parent', 'users.name AS child', DB::raw('false as translate'))
			->join('admin_clients', 'users_clients_properties.client_id', '=', 'admin_clients.id')
			->join('users', 'users_clients_properties.user_id', '=', 'users.id')
			->where('client_id', $this->currentClientId)
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('admin_clients.name')
			->orderBy('users.name')
			->get();
	}

	private function groupUsers()
	{
		return ScopedRelationship::select('admin_scoped_relationships.id', 'admin_groups.name AS parent', 'users.name AS child', DB::raw('false as translate'))
			->join('admin_groups', 'admin_scoped_relationships.belongs_to_id', '=', 'admin_groups.id')
			->join('users_global_properties', 'admin_scoped_relationships.object_id', '=', 'users_global_properties.id')
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->where('belongs_to_type', 'App\Models\Group')
			->where('object_type', 'App\Models\UserGlobalProperties')
			->where(function ($query) {
				$query->where(function ($subQuery) {
					$subQuery->where('scope_type', 'App\Models\Account')
						->where('scope_id', $this->currentAccountId);
				})
					->orWhere(function ($subQuery) {
						$subQuery->where('scope_type', 'App\Models\Client')
							->where('scope_id', $this->currentClientId);
					});
			})
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
			->where(function ($query) {
				$query->where(function ($subQuery) {
					$subQuery->where('scope_type', 'App\Models\Account')
						->where('scope_id', $this->currentAccountId);
				})
					->orWhere(function ($subQuery) {
						$subQuery->where('scope_type', 'App\Models\Client')
							->where('scope_id', $this->currentClientId);
					});
			})
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('admin_groups.name')
			->orderBy('admin_actions.sort_order')
			->get();
	}

	private function userGlobalActions()
	{
		return ScopedRelationship::select('admin_scoped_relationships.id', 'users.name AS parent', 'admin_actions.identifier AS child', DB::raw('true as translate'))
			->join('users_global_properties', 'admin_scoped_relationships.belongs_to_id', '=', 'users_global_properties.id')
			->join('admin_actions', 'admin_scoped_relationships.object_id', '=', 'admin_actions.id')
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->where('belongs_to_type', 'App\Models\UserGlobalProperties')
			->where('object_type', 'App\Models\Action')
			->where('scope_type', 'App\Models\Account')
			->where('scope_id', $this->currentAccountId)
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('users.name')
			->orderBy('admin_actions.sort_order')
			->get();
	}

	private function userLocalActions()
	{
		return ScopedRelationship::select('admin_scoped_relationships.id', 'users.name AS parent', 'admin_actions.identifier AS child', DB::raw('true as translate'))
			->join('users_global_properties', 'admin_scoped_relationships.belongs_to_id', '=', 'users_global_properties.id')
			->join('admin_actions', 'admin_scoped_relationships.object_id', '=', 'admin_actions.id')
			->join('users', 'users_global_properties.user_id', '=', 'users.id')
			->where('belongs_to_type', 'App\Models\UserGlobalProperties')
			->where('object_type', 'App\Models\Action')
			->where('scope_type', 'App\Models\Client')
			->where('scope_id', $this->currentClientId)
			->where('requires_authorization', true)
			->where('authorized', false)
			->orderBy('users.name')
			->orderBy('admin_actions.sort_order')
			->get();
	}
}
