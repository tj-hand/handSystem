<?php

namespace App\Services;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Import Models
use App\Models\Slide;
use App\Models\Client;
use App\Models\Profile;
use App\Models\Bookmark;
use App\Models\PBIImage;
use App\Models\PBIObject;
use App\Models\Repository;
use App\Models\PBIRequest;
use App\Models\PBIWorkspace;

// Import Services
use App\Services\ProfileService;
use App\Services\PermissionService;
use App\Services\PBIRequestService;
use App\Services\SyncPBIImagesService;
use App\Services\SystemLogService as Que;

class PBIObjectService
{
	protected PBIObject $object;
	protected string $objectId = '';
	protected PBIWorkspace $workspace;
	protected string $objectType = '';

	public function __construct(
		protected ProfileService $profileService,
		protected PermissionService $permissionService,
		protected PBIRequestService $pbiRequestService,
		protected SyncPBIImagesService $syncPBIImagesService
	) {}

	public function show($request)
	{
		$this->objectId = $request->input('id');

		$validationAndLoad = $this->validateAndLoad('show');
		if ($validationAndLoad !== true) return $validationAndLoad;

		$grantOperation = $this->grantOperation('show');
		if ($grantOperation !== true) return $grantOperation;


		return Que::passa(true, 'auth.bi.show', '', $this->object, ['pbi_object'  => ['record' => $this->object]]);
	}

	public function upsert($request)
	{

		try {
			DB::beginTransaction();
			$this->objectId = $request->input('record.id');

			$validationAndLoad = $this->validateAndLoad('edit');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$grantOperation = $this->grantOperation('edit');
			if ($grantOperation !== true) return $grantOperation;

			$request->input('record.local_name') == ''
				? $this->object->local_name = $this->object->microsoft_name
				: $this->object->local_name = $request->input('record.local_name');

			$this->object->is_active = $request->input('record.is_active');
			$this->object->roles = $request->input('record.roles');
			$this->object->dataset = $request->input('record.dataset');
			$this->object->description = $request->input('record.description');
			$this->object->save();
			if (!empty($request->record['powerbi.bis.associated_profiles'])) $this->updateAssociatedProfiles($request->record['powerbi.bis.associated_profiles']);
			DB::commit();
			return Que::passa(true, 'auth.bis.update', '', $this->object, ['pbi_object' => ['record' => $this->object]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'auth.bis.update.error');
		}
	}

	public function delete($request)
	{
		$this->objectId = $request->input('id');

		$validationAndLoad = $this->validateAndLoad('delete');
		if ($validationAndLoad !== true) return $validationAndLoad;

		$grantOperation = $this->grantOperation('delete');
		if ($grantOperation !== true) return $grantOperation;

		$this->object->delete();

		return Que::passa(true, 'auth.bi.delete', '', $this->object);
	}

	public function render($request)
	{
		try {

			$this->objectId = $request->input('id');

			$validationAndLoad = $this->validateAndLoad('powerbi');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$grantOperation = $this->grantOperation('powerbi');
			if ($grantOperation !== true) return $grantOperation;

			$this->objectType = $this->object->microsoft_type == 'Dashboard' ? 'dashboards' : 'reports';
			$this->workspace = PBIWorkspace::find($this->object->workspace_id);
			if (!$this->workspace) return 'Wokspace not found';

			$params['dashboard'] = $this->object;
			$params['type'] = mb_substr($this->objectType, 0, -1, 'UTF-8');
			$params['url'] = $this->getObjectEmbedURL();
			$params['token'] = $this->getObjectEmbedToken();
			$params['bookmark'] = $this->getBookmark() ? true : false;

			return Que::passa(true, 'auth.bis.rendered', '', null, ['params' => $params]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.bis.render.error', $request);
		}
	}

	public function associated_profiles($request)
	{
		$this->objectId = $request->input('id');

		$validationAndLoad = $this->validateAndLoad('show');
		if ($validationAndLoad !== true) return $validationAndLoad;

		$grantOperation = $this->grantOperation('show');
		if ($grantOperation !== true) return $grantOperation;

		$list = app(ScopedRelationshipService::class)->makeReverseScopedListWithRelations($this->object, Profile::class, Client::class);
		return Que::passa(true, 'auth.bis.associated_profiles.listed', '', null, ['list' => $list]);
	}

	private function updateAssociatedProfiles($items)
	{
		return app(ScopedRelationshipService::class)->syncReverseScopedRelationships(
			childEntity: $this->object,
			items: $items,
			scopeType: 'App\Models\Client',
			parentObjectType: 'App\Models\Profile',
			grantField: 'profile_objects'
		);
	}

	private function validateAndLoad($operation)
	{
		// Validate UUID format
		if (!Str::isUuid($this->objectId)) return Que::passa(false, 'auth.bis.' . $operation . '.invalid_id', $this->objectId);

		// Try to find the client
		$this->object = PBIObject::find($this->objectId);
		if (!$this->object) return Que::passa(false, 'auth.bis.' . $operation . '.not_found', $this->objectId);

		return true;
	}

	private function grantOperation($operation)
	{
		if (!$this->permissionService::hasPermission('BIs.auth.bis.' . $operation))
			return Que::passa(false, 'auth.bis.' . $operation . '.error.unauthorized');

		if (!$this->profileService->pbiObjects()->contains('id', $this->objectId))
			return Que::passa(false, 'auth.client.show.unauthorized', $this->objectId);

		return true;
	}

	private function getObjectEmbedURL()
	{
		$endpoint = config('powerbi.getEmbeddedURL');
		$data = [
			'workspaceId' => $this->workspace->microsoft_id,
			'reportId' => $this->object->microsoft_id,
			'tipo' => $this->objectType
		];

		$response = $this->pbiRequestService->makeRequest('GET', $endpoint, $data);
		return $response['embedUrl'];
	}

	private function getObjectEmbedToken()
	{
		$endpoint = config('powerbi.getEmbeddedToken');
		$json['accessLevel'] = 'View';
		$data = [
			'workspaceId' => $this->workspace->microsoft_id,
			'reportId' => $this->object->microsoft_id,
			'tipo' => $this->objectType
		];


		if ($this->object->roles) {
			$json['identities'] = [
				[
					'username' => PermissionService::User()->email,
					'roles' => array_map('trim', explode(',', $this->object->roles)),
					'datasets' => array_map('trim', explode(',', $this->object->dataset))
				]
			];
		}
		$response = $this->pbiRequestService->makeRequest('POST', $endpoint, $data, $json);

		return $response['token'];
	}

	public function pages($request)
	{
		try {

			$this->objectId = $request->input('id');

			$validationAndLoad = $this->validateAndLoad('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			$this->objectType = $this->object->microsoft_type == 'Dashboard' ? 'dashboards' : 'reports';
			$this->workspace = PBIWorkspace::find($this->object->workspace_id);
			if (!$this->workspace) return 'Wokspace not found';

			if ($this->objectType == 'dashboards') return response()->json(null);
			$endpoint = config('powerbi.reportPagesList');
			$data = [
				'workspaceId' => $this->workspace->microsoft_id,
				'reportId' => $this->object->microsoft_id
			];
			$response = $this->pbiRequestService->makeRequest('GET', $endpoint, $data);
			$data = $response['value'];
			$objectPages = (!empty($data)) ? $this->attachPbiImageData($data) : null;
			return Que::passa(true, 'auth.bis.pages_listed', '', null, ['objectPages' => $objectPages]);
		} catch (Exception $e) {
			return Que::passa(true, 'auth.bis.pages_list.error');
		}
	}

	public function page($request)
	{

		try {

			$this->objectId = $request->input('id');

			$validationAndLoad = $this->validateAndLoad('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			$image = PBIImage::where('object_id', $this->objectId)
				->where('pbi_name', $request->name)
				->where('pbi_displayname', $request->displayName)
				->first();

			if (!$image) return Que::passa(false, 'auth.bis.image.error.not_found');
			return Que::passa(true, 'auth.bis.image.params.show', '', $image, ['image' => $image]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.server.error', 'auth.bis.image.params');
		}
	}

	private function attachPbiImageData(array $data): array
	{
		// Extract unique names and display names to minimize query scope
		$names = array_column($data, 'name');
		$displayNames = array_column($data, 'displayName');

		// Fetch all relevant PBIImage records in one query
		$pages = PBIImage::whereIn('pbi_name', $names)->whereIn('pbi_displayname', $displayNames)->get()->keyBy(fn($page) => $page->pbi_name . '|' . $page->pbi_displayname);

		// Update $data efficiently
		foreach ($data as &$pbiReportPage) {
			$key = $pbiReportPage['name'] . '|' . $pbiReportPage['displayName'];
			if (isset($pages[$key])) {
				$page = $pages[$key];
				$pbiReportPage['status'] = $page->status;
				$pbiReportPage['hasPhoto'] = true;
				$pbiReportPage['id'] = $page->id;
			} else {
				$pbiReportPage['hasPhoto'] = false;
				$pbiReportPage['id'] = '';
			}
		}

		return $data;
	}

	public function createImage($request)
	{

		try {

			DB::beginTransaction();

			$userId = PermissionService::UserGlobalProperties()->id;

			$message = '';

			$image = PBIImage::where('object_id', $request->id)
				->where('pbi_name', $request->pbiName)
				->where('pbi_displayname', $request->pbiDisplayName)
				->first();

			$object = PBIObject::findOrFail($request->id);
			$workspace = PBIWorkspace::where('id', $object->workspace_id)->first();

			if ($image) {
				$image->image_name = $request->imageName;
				$image->image_time = $request->imageTime;
				$image->reload_time = $request->imageTime;
				$image->save();
				$message = 'auth.bis.image.update';
			} else {
				$image = PBIImage::create([
					'pbi_name' => $request->pbiName,
					'pbi_displayname' => $request->pbiDisplayName,
					'image_name' => $request->imageName,
					'image_time' => $request->imageTime,
					'status' => 'pending',
					'reload_time' => $request->imageTime,
					'workspace_id' => $workspace->id,
					'object_id' => $request->id,
					'user_id' => $userId
				]);
				$message = 'auth.bis.image.created';
			}

			$imageRequest = $this->syncPBIImagesService->imageRequest($workspace->microsoft_id, $object, $image);


			if (!$imageRequest) {
				DB::rollBack();
				return Que::passa(false, 'auth.bis.image.upsert.error',  $request->id);
			}

			Log::info('PBI Image request created for image ' . $image->id);
			DB::commit();

			return Que::passa(true, $message, '', null, ['image' => $imageRequest]);
		} catch (Exception $e) {

			DB::rollBack();
			return Que::passa(false, 'auth.bis.image.upsert.error',  $request->id);
		}
	}

	public function destroyImage($request)
	{
		$image = PBIImage::where('object_id', $request->report)
			->where('pbi_name', $request->name)
			->where('pbi_displayname', $request->displayName)
			->first();

		if ($image) {
			PBIRequest::where('image_id', $image->id)->delete();
			$repositoryId = $image->repository_id;
			$repository = Repository::find($repositoryId);
			if ($repository) {
				$slides = Slide::where('repository_id', $repositoryId)->get();
				foreach ($slides as $slide) {
					$slide->delete();
				}
				Storage::disk('private')->delete("uploads/{$repository->client_id}/" . $repositoryId);
				$repository->delete();
			}
			$image->delete();
			Que::Passa('Info', 'Imagem do PowerBI excluída', $image);
			return response()->json('Imagem excluída!');
		}

		Que::Passa('Erro', 'Tentativa de excluir imagem do PowerBI inválida', $request->report);
		return response()->json('Imagem não encontrada!', 404);
	}

	public function bookmark($request)
	{
		try {

			$this->objectId = $request->input('id');

			$validationAndLoad = $this->validateAndLoad('powerbi');
			if ($validationAndLoad !== true) return $validationAndLoad;

			$grantOperation = $this->grantOperation('powerbi');
			if ($grantOperation !== true) return $grantOperation;

			return $this->getBookmark(true);
		} catch (Exception $e) {
			return Que::passa(true, 'auth.bis.bookmark.error');
		}
	}

	private function getBookmark($toggle = false)
	{
		$userId = PermissionService::UserGlobalProperties()->id;
		$clientId = PermissionService::UserCurrentAccountProperties()->current_client;
		$bookmark = Bookmark::where('user_id', $userId)->where('object_id', $this->objectId)->where('client_id', $clientId)->first();

		if (!$toggle) return $bookmark;

		if (!$bookmark) {
			$newBookmark = Bookmark::create([
				'user_id' => $userId,
				'object_id' => $this->objectId,
				'client_id' => $clientId
			]);
			return Que::passa(true, 'auth.bis.bookmarked', '', $newBookmark);
		} else {
			$bookmark->delete();
			return Que::passa(true, 'auth.bis.unbookmarked', '', $bookmark);
		}
	}
}
