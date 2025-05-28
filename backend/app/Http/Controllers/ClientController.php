<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;
use App\Services\ScopedRelationshipService;

class ClientController extends Controller
{

	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
	}

	public function show(Request $request)
	{
		try {
			if (!Str::isUuid($request->input('id'))) return Que::passa(false, 'auth.client.show.invalid_id', $request->input('id'));
			if (!$this->permissionService::hasPermission('Clients.auth.clients.show'))
				return Que::passa(false, 'auth.clients.show.error.unauthorized');

			$accountId = PermissionService::UserGlobalProperties()->current_account;
			$clients = Client::select('id', 'name')->where('account_id', $accountId)->orderBy('name')->get();
			$exists = $clients->contains('id', $request->input('id'));
			if (!$exists) return Que::passa(false, 'auth.client.show.unauthorized', $request->input('id'));
			$client = Client::find($request->input('id'));
			return $client
				? Que::passa(true, 'auth.client.show', '', $client, ['client'  => ['record' => $client]])
				: Que::passa(false, 'auth.client.show.error.client_not_found', $request->input('id'));
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.client.show ' . $request->input('id'));
		}
	}

	public function upsert(Request $request)
	{
		$message = '';
		$data = $request->input('record', []);

		try {
			DB::beginTransaction();
			if (!empty($data['id'])) {
				if (!$this->permissionService::hasPermission('Clients.auth.clients.edit'))
					return Que::passa(false, 'auth.clients.edit.error.unauthorized');
				$message = 'auth.clients.updated';
				$client = Client::find($data['id']);
				if (!$client) return Que::passa(false, 'auth.clients.upsert.error.client_not_found', $data['id']);
				$client->name = $data['name'];
				$client->is_active = $data['is_active'];
				$client->description = $data['description'];
				$client->save();
			} else {
				if (!$this->permissionService::hasPermission('Clients.auth.clients.add'))
					return Que::passa(false, 'auth.clients.add.error.unauthorized');
				$message = 'auth.clients.created';
				$accountId = $this->permissionService->UserGlobalProperties()->current_account;
				$data['account_id'] = $accountId;
				$client = Client::create($data);
			}
			if (!empty($data['client.associated_users']))
				app(ScopedRelationshipService::class)->syncScopedRelationships(
					parentEntity: $client,
					items: $data['client.associated_users'],
					scopeType: 'App\Models\Account',
					relatedObjectType: 'App\Models\UserGlobalProperties',
					grantField: 'clients_and_account_users'
				);
			DB::commit();
			return Que::passa(true, $message, '', $client, ['client'  => ['record' => $client]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.groups.upsert ' . ($data['id'] ?? 'new'));
		}
	}

	public function delete(Request $request)
	{
		$validator = Validator::make($request->all(), ['id' => 'uuid']);
		if ($validator->fails()) return Que::passa(false, 'auth.client.error.delete.error.invalid_id_type');

		if (!$this->permissionService::hasPermission('Clients.auth.clients.delete'))
			return Que::passa(false, 'auth.clients.delete.error.unauthorized');

		try {

			DB::beginTransaction();
			$client = Client::find($request->input('id'));
			if (!$client) return Que::passa(false, 'auth.client.delete.error.client_not_found', $request->input('id'));
			$client->delete();
			DB::commit();
			return Que::passa(true, 'auth.client.deleted', '', $client);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.client.delete.error');
		}
	}

	public function associatedUsers(Request $request)
	{

		$clientId = $request->input('id');

		if ($clientId === 'new') {
			$list = app(ScopedRelationshipService::class)->makeScopedListWithoutRelations(UserGlobalProperties::class);
			return Que::passa(true, 'auth.client.associated_users.listed', '', null, ['list' => $list]);
		}

		$client = Client::find($clientId);
		if (!$client) return Que::passa(false, 'auth.client.associat_users.error.group_not_found', $clientId);

		$list = app(ScopedRelationshipService::class)->makeScopedListWithRelations($client, UserGlobalProperties::class);
		return Que::passa(true, 'auth.client.associated_users.listed', '', $client, ['list' => $list]);
	}
}
