<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\ActionSet;
use App\Models\GrantConfig;
use Illuminate\Support\Facades\DB;
use App\Models\ScopedRelationship;
use App\Services\PermissionService;
use App\Models\UserGlobalProperties;
use Illuminate\Database\Eloquent\Model;
use App\Services\SystemLogService as Que;

class ScopedRelationshipService
{
	public function __construct(
		protected PermissionService $permissionService
	) {}

	public function syncScopedRelationships(object $parentEntity, array $items, string $scopeType, string $relatedObjectType, string $grantField): array
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

	protected function addRelationships(array $objectIds, object $parent, string $scopeType, string $scopeId, string $objectType, GrantConfig $grant, string $grantField): void
	{
		foreach ($objectIds as $objectId) {
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

	public function makeScopedListWithoutRelations(string $objectModel)
	{
		$objects = $this->getObjects($objectModel);

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

	protected function makeFlatScopedListWithRelations(Model $belongsTo, string $objectModel, string $scopeType)
	{
		$accountId = $this->permissionService->UserGlobalProperties()->current_account;
		$objects = $this->getObjects($objectModel);

		$relationships = ScopedRelationship::where('object_type', $objectModel)
			->where('belongs_to_type', get_class($belongsTo))
			->where('belongs_to_id', $belongsTo->id)
			->where('scope_type', $scopeType)
			->where('scope_id', $accountId)
			->get()
			->keyBy('object_id');

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

	protected function makeAccountScopedList(Model $belongsTo, string $objectModel, string $scopeType)
	{
		$accountId = $this->permissionService->UserGlobalProperties()->current_account;
		$setActions = ActionSet::select('id', 'name AS title')->where('is_active', true)->where('is_visible', true)->with('itemsList')->get();
		$allItemIds = collect($setActions)->pluck('itemsList')->flatten(1)->pluck('id')->unique()->values();


		// Buscar os relacionamentos existentes
		$relationships = ScopedRelationship::where('object_type', $objectModel)
			->where('belongs_to_type', get_class($belongsTo))
			->where('belongs_to_id', $belongsTo->id)
			->where('scope_type', $scopeType)
			->where('scope_id', $accountId)
			->get()
			->keyBy('object_id');

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



	protected function getObjects(string $objectModel)
	{
		if ($objectModel === UserGlobalProperties::class) {
			return $objectModel::select('users_global_properties.id', 'name AS title', 'email AS subtitle')
				->join('users', 'users_global_properties.user_id', '=', 'users.id')
				->orderBy('name')
				->get();
		}
		return collect();
	}
}
