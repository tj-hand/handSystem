<?php

namespace App\Services;


// Import Tools
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

// Import Models
use App\Models\Group;
use App\Models\Client;
use App\Models\Account;
use App\Models\ActionSet;
use App\Models\GrantConfig;
use App\Models\ScopedRelationship;
use App\Models\UserClientProperties;
use App\Models\UserGlobalProperties;

// Import Services
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class ScopedRelationshipService
{
	protected string $currentClientId;
	protected string $currentAccountId;
	protected PermissionService $permissionService;

	public function __construct(PermissionService $permissionService)
	{
		$this->permissionService = $permissionService;
		$this->currentAccountId = $this->permissionService::UserGlobalProperties()->current_account;
		$this->currentClientId = $this->permissionService::UserCurrentAccountProperties()->current_client;
	}

	public function syncScopedRelationships(object $parentEntity, array $items, string $scopeType, string $relatedObjectType, string $grantField) //: array
	{
		try {


			DB::beginTransaction();

			$scopeId = $this->resolveScopeId($scopeType);
			$grant = $this->getGrant($scopeType, $scopeId);

			if (!$grant) return Que::passa(false, 'auth.scoped_relationship.error.grant_not_found');

			[$relatedObjectIdsToAdd, $relatedObjectIdsToRemove] = $this->extractIdsFromItems($items);

			$this->removeRelationships(
				$relatedObjectIdsToRemove,
				$parentEntity,
				$scopeType,
				$scopeId,
				$relatedObjectType
			);

			$this->addRelationships(
				$relatedObjectIdsToAdd,
				$parentEntity,
				$scopeType,
				$scopeId,
				$relatedObjectType,
				$grant,
				$grantField
			);

			DB::commit();
			return Que::passa(true, 'auth.scoped_relationship.updated', $relatedObjectType, $parentEntity);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.scoped_relationship.update ' . $relatedObjectType);
		}
	}

	protected function resolveScopeId(string $scopeType): string
	{
		return match ($scopeType) {
			'App\Models\Account' => $this->permissionService->UserGlobalProperties()->current_account,
			'App\Models\Client' => $this->permissionService->UserCurrentAccountProperties()->current_client,
			default => throw new Exception("Invalid scope type: $scopeType"),
		};
	}

	protected function getGrant(string $scopeType, string $scopeId): ?GrantConfig
	{
		return GrantConfig::where('object_type', $scopeType)->where('object_id', $scopeId)->first();
	}

	protected function extractIdsFromItems(array $items): array
	{
		$collection = collect($items);
		$toAdd = $collection->filter(fn($item) => $item['selected'])->pluck('id')->all();
		$toRemove = $collection->reject(fn($item) => $item['selected'])->pluck('id')->all();

		return [$toAdd, $toRemove];
	}

	protected function removeRelationships(array $objectIds, object $parent, string $scopeType, string $scopeId, string $objectType): void
	{
		ScopedRelationship::whereIn('object_id', $objectIds)
			->where('object_type', $objectType)
			->where('belongs_to_type', get_class($parent))
			->where('belongs_to_id', $parent->id)
			->where('scope_type', $scopeType)
			->where('scope_id', $scopeId)
			->delete();
	}

	protected function addRelationships(array $objectIds, object $parent, string $scopeType, string $scopeId, string $objectType, GrantConfig $grant, string $grantField)
	{
		foreach ($objectIds as $objectId) {


			if ($parent instanceof Group) {
				$scopeType = $parent->scope === 'client' ? 'App\Models\Client' : 'App\Models\Account';
				$scopeId = $parent->scope === 'client' ? $this->currentClientId : $this->currentAccountId;
			}


			$exists = ScopedRelationship::where([
				'object_type' => $objectType,
				'object_id' => $objectId,
				'belongs_to_type' => get_class($parent),
				'belongs_to_id' => $parent->id,
				'scope_type' => $scopeType,
				'scope_id' => $scopeId,
			])->exists();


			if (!$exists) {
				ScopedRelationship::create([
					'object_type' => $objectType,
					'object_id' => $objectId,
					'belongs_to_type' => get_class($parent),
					'belongs_to_id' => $parent->id,
					'scope_type' => $scopeType,
					'scope_id' => $scopeId,
					'requires_authorization' => $grant->$grantField,
					'authorized' => !$grant->$grantField,
					'authorized_by_name' => !$grant->$grantField ? 'System' : null,
					'authorization_timestamp' => !$grant->$grantField ? Carbon::now() : null,
				]);
			}
		}
	}

	public function syncReverseScopedRelationships(object $childEntity, array $items, string $scopeType, string $parentObjectType, string $grantField) //: array
	{
		try {
			DB::beginTransaction();

			$scopeId = $this->resolveScopeId($scopeType);
			$grant = $this->getGrant($scopeType, $scopeId);

			if (!$grant) return Que::passa(false, 'auth.scoped_relationship.error.grant_not_found');


			[$parentObjectIdsToAdd, $parentObjectIdsToRemove] = $this->extractIdsFromItems($items);

			$this->removeReverseRelationships(
				$parentObjectIdsToRemove,
				$childEntity,
				$scopeType,
				$scopeId,
				$parentObjectType
			);

			$this->addReverseRelationships(
				$parentObjectIdsToAdd,
				$childEntity,
				$scopeType,
				$scopeId,
				$parentObjectType,
				$grant,
				$grantField
			);

			DB::commit();
			return Que::passa(true, 'auth.scoped_relationship.updated', $parentObjectType, $childEntity);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.scoped_relationship.update ' . $parentObjectType);
		}
	}

	protected function removeReverseRelationships(array $parentObjectIds, object $childEntity, string $scopeType, string $scopeId, string $parentObjectType) // : void
	{
		ScopedRelationship::where([
			'object_type' => get_class($childEntity),
			'object_id' => $childEntity->id,
			'belongs_to_type' => $parentObjectType,
			'scope_type' => $scopeType,
			'scope_id' => $scopeId,
		])->whereIn('belongs_to_id', $parentObjectIds)->delete();
	}

	protected function addReverseRelationships(array $parentObjectIds, object $childEntity, string $scopeType, string $scopeId, string $parentObjectType, GrantConfig $grant, string $grantField) // : void
	{

		foreach ($parentObjectIds as $parentObjectId) {


			if ($parentObjectType === 'App\Models\Group') {
				$group = Group::find($parentObjectId);
				if ($group?->scope === 'client') {
					$scopeType = 'App\Models\Client';
					$scopeId = $this->currentClientId;
				}
			}

			$exists = ScopedRelationship::where([
				'object_type' => get_class($childEntity),
				'object_id' => $childEntity->id,
				'belongs_to_type' => $parentObjectType,
				'belongs_to_id' => $parentObjectId,
				'scope_type' => $scopeType,
				'scope_id' => $scopeId,
			])->exists();

			if (!$exists) {
				ScopedRelationship::create([
					'object_type' => get_class($childEntity),
					'object_id' => $childEntity->id,
					'belongs_to_type' => $parentObjectType,
					'belongs_to_id' => $parentObjectId,
					'scope_type' => $scopeType,
					'scope_id' => $scopeId,
					'requires_authorization' => $grant->$grantField,
					'authorized' => !$grant->$grantField,
					'authorized_by_name' => !$grant->$grantField ? 'System' : null,
					'authorization_timestamp' => !$grant->$grantField ? Carbon::now() : null,
				]);
			}
		}
	}

	public function setRelationship(
		string $objectType,
		string $objectId,
		string $belongsToType,
		string $belongsToId,
		string $scopeType,
		string $scopeId,
		bool $requiresAuthorization = false,
		bool $authorized = false,
		?string $authorizedByName = null,
		?Carbon $authorizationTimestamp = null
	): ScopedRelationship {
		$relationship = new ScopedRelationship();

		$relationship->object_type = 'App\Models\\' . $objectType;
		$relationship->object_id = $objectId;
		$relationship->belongs_to_type = 'App\Models\\' . $belongsToType;
		$relationship->belongs_to_id = $belongsToId;
		$relationship->scope_type = 'App\\Models\\' . $scopeType;
		$relationship->scope_id = $scopeId;
		$relationship->requires_authorization = $requiresAuthorization;
		$relationship->authorized = $authorized;
		$relationship->authorized_by_name = $authorizedByName;
		$relationship->authorization_timestamp = $authorizationTimestamp;

		$relationship->save();

		return $relationship;
	}

	public function makeScopedListWithoutRelations(string $objectModel, string $scopeType = Account::class)
	{

		if ($objectModel == 'App\Models\Action') {
			$setActions = ActionSet::select('id', 'name AS title')->where('is_active', true)->where('is_visible', true)->with('itemsList')->orderBY('sort_order')->get();
			return collect($setActions)->map(function ($group) {
				return [
					'id' => $group['id'],
					'title' => $group['title'],
					'items_list' => collect($group['itemsList'])->map(function ($item) {
						return [
							'id' => $item['id'],
							'title' => $item['title'],
							'selected' => false,
						];
					})->values(),
				];
			})->values();
		}

		$objects = $this->getObjects($objectModel, $scopeType);

		return $objects->map(function ($object) {
			return [
				'id' => $object->id,
				'title' => $object->title,
				'subtitle' => $object->subtitle,
				'selected' => false,
			];
		});
	}

	public function makeScopedListWithRelations(Model $belongsTo, string $objectModel, string $scopeType = Account::class)
	{
		if ($objectModel == 'App\Models\Action') return $this->makeAccountScopedList($belongsTo, $objectModel, $scopeType);
		return $this->makeFlatScopedListWithRelations($belongsTo, $objectModel, $scopeType);
	}

	protected function makeFlatScopedListWithRelations(Model $belongsTo, string $objectModel, string $scopeType = Account::class)
	{
		$objects = $this->getObjects($objectModel, $scopeType);

		$query = ScopedRelationship::where('object_type', $objectModel)
			->where('belongs_to_type', get_class($belongsTo))
			->where('belongs_to_id', $belongsTo->id);

		// Apply scope filtering based on scopeType
		$isClientScope = ($scopeType === 'App\Models\Client' || $scopeType === Client::class);

		if ($isClientScope) {
			// When scope is Client, search only in client scope
			$query->where('scope_type', 'App\Models\Client')
				->where('scope_id', $this->currentClientId);
		} else {
			// When scope is Account (default), search in both Account and Client scopes
			$query->where(function ($subQuery) {
				$subQuery->where(function ($q) {
					$q->where('scope_type', 'App\Models\Account')
						->where('scope_id', $this->currentAccountId);
				})->orWhere(function ($q) {
					$q->where('scope_type', 'App\Models\Client')
						->where('scope_id', $this->currentClientId);
				});
			});
		}

		$relationships = $query->get()->keyBy('object_id');

		return $objects->map(function ($object) use ($relationships) {
			$relation = $relationships->get($object->id);

			$selected = match (true) {
				$relation && !$relation->requires_authorization && $relation->authorized => true,
				$relation && $relation->requires_authorization && !$relation->authorized => 'waiting',
				$relation && $relation->requires_authorization && $relation->authorized => true,
				default => false,
			};

			return [
				'id' => $object->id,
				'title' => $object->title,
				'subtitle' => $object->subtitle,
				'selected' => $selected,
			];
		});
	}

	protected function makeAccountScopedList(Model $belongsTo, string $objectModel, string $scopeType = Account::class)
	{
		$setActions = ActionSet::select('id', 'name AS title')->where('is_active', true)->where('is_visible', true)->with('itemsList')->orderBY('sort_order')->get();

		// Build the base query for relationships
		$query = ScopedRelationship::where('object_type', $objectModel)
			->where('belongs_to_type', get_class($belongsTo))
			->where('belongs_to_id', $belongsTo->id);

		// Apply scope filtering based on scopeType
		$isClientScope = ($scopeType === 'App\Models\Client' || $scopeType === Client::class);

		if ($isClientScope) {
			// When scope is Client, search only in client scope
			$query->where('scope_type', 'App\Models\Client')
				->where('scope_id', $this->currentClientId);
		} else {
			// When scope is Account (default), search in both Account and Client scopes
			// $query->where(function ($subQuery) {
			// 	$subQuery->where(function ($q) {
			// 		$q->where('scope_type', 'App\Models\Account')
			// 			->where('scope_id', $this->currentAccountId);
			// 	})->orWhere(function ($q) {
			// 		$q->where('scope_type', 'App\Models\Client')
			// 			->where('scope_id', $this->currentClientId);
			// 	});
			// });
			$query->where('scope_type', 'App\Models\Account')
				->where('scope_id', $this->currentAccountId);
		}

		$relationships = $query->get()->keyBy('object_id');

		// Mapear para o mesmo formato usado no método flat
		return collect($setActions)->map(function ($group) use ($relationships) {
			return [
				'id' => $group['id'],
				'title' => $group['title'],
				'items_list' => collect($group['itemsList'])->map(function ($item) use ($relationships) {
					$relation = $relationships->get($item['id']); // <- ID correto do item

					$selected = match (true) {
						$relation && !$relation->requires_authorization && $relation->authorized => true,
						$relation && $relation->requires_authorization && !$relation->authorized => 'waiting',
						$relation && $relation->requires_authorization && $relation->authorized => true,
						default => false,
					};

					return [
						'id' => $item['id'],
						'title' => $item['title'],
						'selected' => $selected,
					];
				})->values(),
			];
		})->values();
	}

	protected function getObjects(string $objectModel, string $scopeType = Account::class)
	{

		$isClientScope = ($scopeType === 'App\Models\Client' || $scopeType === Client::class);

		if ($objectModel === UserGlobalProperties::class) {

			$query = UserGlobalProperties::select('users_global_properties.id', 'users.name AS title', 'users.email AS subtitle')
				->join('users', 'users_global_properties.user_id', '=', 'users.id')
				->join('users_accounts_properties', 'users.id', '=', 'users_accounts_properties.user_id')
				->where('users_accounts_properties.account_id', $this->currentAccountId)
				->where('users_global_properties.is_superuser', false)
				->where('users_accounts_properties.is_account_admin', false)
				->orderBy('users.name')
				->distinct();

			if ($isClientScope) {
				$query->join('users_clients_properties', 'users.id', '=', 'users_clients_properties.user_id')
					->where('users_clients_properties.client_id', $this->currentClientId)
					->where('users_clients_properties.authorized', true);
			}

			return $query->get();
		}

		if ($objectModel === Client::class) {
			return $objectModel::select('admin_clients.id', 'name AS title')
				->where('account_id', $this->currentAccountId)
				->orderBy('name')
				->get();
		}

		if ($objectModel === Group::class) {


			$groupsIds = ScopedRelationship::where('admin_scoped_relationships.object_type', 'App\Models\Group')
				->where('admin_scoped_relationships.scope_type', 'App\Models\Account')
				->where('admin_scoped_relationships.scope_id', $this->currentAccountId)
				->where(function ($query) {
					$query->where(function ($subQuery) {
						$subQuery->where('admin_scoped_relationships.belongs_to_type', 'App\Models\Client')
							->where('admin_scoped_relationships.belongs_to_id', $this->currentClientId);
					})
						->orWhere(function ($subQuery) {
							$subQuery->where('admin_scoped_relationships.belongs_to_type', 'App\Models\Account')
								->where('admin_scoped_relationships.belongs_to_id', $this->currentAccountId);
						});
				});


			if ($isClientScope) $groupsIds->where('belongs_to_type', 'App\Models\Client')->where('belongs_to_id', $this->currentClientId);

			$groupsIds = $groupsIds->pluck('object_id');

			return $objectModel::select('admin_groups.id', 'name AS title')
				->whereIn('id', $groupsIds)
				->where('is_active', true)
				->orderBy('name')
				->get();
		}
		return collect();
	}

	public function makeReverseScopedListWithRelations(Model $object, string $belongsToModel, string $scopeType = Account::class)
	{
		if ($belongsToModel == 'App\Models\Action') return $this->makeReverseAccountScopedList($object, $belongsToModel, $scopeType);
		return $this->makeFlatReverseScopedListWithRelations($object, $belongsToModel, $scopeType);
	}

	protected function makeFlatReverseScopedListWithRelations(Model $object, string $belongsToModel, string $scopeType = Account::class)
	{
		$belongsToObjects = $this->getObjects($belongsToModel, $scopeType);
		$userGlobalProperties = UserGlobalProperties::find($object->id);
		$isInClient = UserClientProperties::where('user_id', $userGlobalProperties->user_id)
			->where('client_id', $this->currentClientId)
			->where('authorized', true)
			->first();

		if ($belongsToModel == 'App\Models\Group' && $scopeType == 'App\Models\Account' && !$isInClient) {
			$groupIds = $belongsToObjects->pluck('id');
			$accountScopeGroupIds = Group::whereIn('id', $groupIds)
				->where('scope', 'account')
				->pluck('id');

			$belongsToObjects = $belongsToObjects->filter(function ($item) use ($accountScopeGroupIds) {
				return !$accountScopeGroupIds->contains($item['id']);
			});
		}

		$relationships = ScopedRelationship::where('object_type', get_class($object))
			->where('object_id', $object->id)
			->where('belongs_to_type', $belongsToModel)
			->where(function ($query) {
				$query->where(function ($q) {
					$q->where('scope_type', 'App\Models\Account')
						->where('scope_id', $this->currentAccountId);
				})->orWhere(function ($q) {
					$q->where('scope_type', 'App\Models\Client')
						->where('scope_id', $this->currentClientId);
				});
			})
			->get()
			->keyBy('belongs_to_id');


		return $belongsToObjects->map(function ($belongsToObject) use ($relationships) {
			$relation = $relationships->get($belongsToObject->id);

			$selected = match (true) {
				$relation && !$relation->requires_authorization && $relation->authorized => true,
				$relation && $relation->requires_authorization && !$relation->authorized => 'waiting',
				$relation && $relation->requires_authorization && $relation->authorized => true,
				default => false,
			};

			return [
				'id' => $belongsToObject->id,
				'title' => $belongsToObject->title,
				'subtitle' => $belongsToObject->subtitle,
				'selected' => $selected,
			];
		});
	}

	protected function makeReverseAccountScopedList(Model $object, string $belongsToModel, string $scopeType = Account::class)
	{
		$accountId = $this->permissionService->UserGlobalProperties()->current_account;
		$setActions = ActionSet::select('id', 'name AS title')->where('is_active', true)->where('is_visible', true)->with('itemsList')->orderBY('sort_order')->get();
		$allItemIds = collect($setActions)->pluck('itemsList')->flatten(1)->pluck('id')->unique()->values();

		// Build the base query for relationships
		$query = ScopedRelationship::where('object_type', get_class($object))
			->where('object_id', $object->id)
			->where('belongs_to_type', $belongsToModel);

		// Apply scope filtering based on scopeType
		$isClientScope = ($scopeType === 'App\Models\Client' || $scopeType === Client::class);

		if ($isClientScope) {
			// When scope is Client, search only in client scope
			$query->where('scope_type', 'App\Models\Client')
				->where('scope_id', $this->currentClientId);
		} else {
			// When scope is Account (default), search in both Account and Client scopes
			$query->where(function ($subQuery) {
				$subQuery->where(function ($q) {
					$q->where('scope_type', 'App\Models\Account')
						->where('scope_id', $this->currentAccountId);
				})->orWhere(function ($q) {
					$q->where('scope_type', 'App\Models\Client')
						->where('scope_id', $this->currentClientId);
				});
			});
		}

		$relationships = $query->get()->keyBy('belongs_to_id'); // <- Chave mudou para belongs_to_id

		// Mapear para o mesmo formato usado no método flat
		return collect($setActions)->map(function ($group) use ($relationships) {
			return [
				'id' => $group['id'],
				'title' => $group['title'],
				'items_list' => collect($group['itemsList'])->map(function ($item) use ($relationships) {
					$relation = $relationships->get($item['id']); // <- ID correto do item como belongs_to_id

					$selected = match (true) {
						$relation && !$relation->requires_authorization && $relation->authorized => true,
						$relation && $relation->requires_authorization && !$relation->authorized => 'waiting',
						$relation && $relation->requires_authorization && $relation->authorized => true,
						default => false,
					};

					return [
						'id' => $item['id'],
						'title' => $item['title'],
						'selected' => $selected,
					];
				})->values(),
			];
		})->values();
	}
}
