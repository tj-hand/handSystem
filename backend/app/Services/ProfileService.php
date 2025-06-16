<?php

namespace App\Services;

// Import Models
use App\Models\Profile;
use App\Models\PBIObject;
use App\Models\PBIWorkspace;
use App\Models\ScopedRelationship;
// Import Services
use App\Services\PermissionService;


class ProfileService
{

	protected string $currentClientId = '';

	public function __construct(
		protected PermissionService $permissionService
	) {
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function profiles()
	{
		return  $this->profileQuery()->select('admin_groups.id', 'admin_groups.name')->get();
	}

	public function pbiObjects()
	{
		$clientActiveWorkspacesIds = $this->getClientActiveAssociatedWorkspaces();
		$allObjects = PBIObject::select('id', 'local_name AS title')
			->where('custom_pbi_objects.is_active', true)
			->whereIn('workspace_id', $clientActiveWorkspacesIds)
			->orderBy('local_name')
			->get();


		$userId = PermissionService::UserGlobalProperties()->id;
		$is_superuser = PermissionService::UserGlobalProperties()->is_superuser;
		$is_account_admin = PermissionService::UserCurrentAccountProperties()->is_account_admin;
		$ignoreProfile = $this->permissionService::hasPermission('SpecialPermissions.auth.bis.ignore_profiles');

		if ($is_superuser || $is_account_admin || $ignoreProfile) return $allObjects;

		$clientProfilesId = $this->profileQuery()->where('authorized', true)->pluck('admin_groups.id');
		$profiles = Profile::where('is_active', true)->whereIn('id', $clientProfilesId)->pluck('id');

		$userProfiles = ScopedRelationship::where('object_type', 'App\Models\UserGlobalProperties')
			->where('object_id', $userId)
			->where('belongs_to_type', 'App\Models\Profile')
			->whereIn('belongs_to_id', $profiles)
			->where('scope_type', 'App\Models\Client')
			->where('scope_id', $this->currentClientId)
			->where('authorized', true)
			->pluck('belongs_to_id');

		$objectsIds = ScopedRelationship::where('object_type', 'App\Models\PBIObject')
			->where('belongs_to_type', 'App\Models\Profile')
			->whereIn('belongs_to_id', $userProfiles)
			->where('scope_type', 'App\Models\Client')
			->where('scope_id', $this->currentClientId)
			->where('authorized', true)
			->pluck('object_id');

		$objects = PBIObject::select('id', 'local_name AS title')->whereIn('id', $objectsIds)->orderBy('local_name')->get();

		return $objects;
	}

	private function getClientActiveAssociatedWorkspaces()
	{
		return PBIWorkspace::join('admin_scoped_relationships', 'custom_pbi_workspaces.id', '=', 'admin_scoped_relationships.object_id')
			->where('custom_pbi_workspaces.is_active', true)
			->where('admin_scoped_relationships.object_type', 'App\Models\PBIWorkspace')
			->where('admin_scoped_relationships.belongs_to_type', 'App\Models\Client')
			->where('admin_scoped_relationships.scope_type', 'App\Models\Client')
			->where('admin_scoped_relationships.belongs_to_id', $this->currentClientId)
			->where('admin_scoped_relationships.scope_id', $this->currentClientId)
			->pluck('custom_pbi_workspaces.id');
	}

	private function profileQuery()
	{
		return Profile::join('admin_scoped_relationships', 'admin_groups.id', '=', 'admin_scoped_relationships.object_id')
			->where('group_type', 'profile_group')
			->where('object_type', 'App\Models\Profile')
			->where('belongs_to_type', 'App\Models\Client')
			->where('belongs_to_id', $this->currentClientId)
			->where('scope_type', 'App\Models\Client')
			->where('scope_id', $this->currentClientId)
			->orderBy('admin_groups.name');
	}
}
