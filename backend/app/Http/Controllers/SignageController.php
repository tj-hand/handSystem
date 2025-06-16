<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Import Models
use App\Models\Slide;
use App\Models\Signage;

// Import Services
use App\Services\SignageService;
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class SignageController extends Controller
{

	protected $record = [];
	protected $signageId = '';
	protected $signage = null;
	protected $newRecord = false;
	protected string $currentClientId = '';
	protected SignageService $signageService;
	protected PermissionService $permissionService;

	public function __construct(Request $request, PermissionService $permissionService, SignageService $signageService)
	{

		$this->record = $request->input('record');
		$this->signageId = data_get($request->input('record'), 'id', $request->input('id'));

		$this->signageService = $signageService;
		$this->permissionService = $permissionService;
		$this->currentClientId = PermissionService::UserCurrentAccountProperties()->current_client;
	}

	public function show()
	{
		try {

			// Validate the operation
			$validationAndLoad = $this->validateAndLoad('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			return Que::passa(true, 'auth.signage.show', '', $this->signage, ['signage'  => ['record' => $this->signage]]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.signage.show ' . $this->signageId);
		}
	}

	public function upsert()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoad('upsert');
		if ($validationAndLoad !== true) return $validationAndLoad;

		if ($this->newRecord) return $this->create();
		return $this->update();
	}

	public function delete()
	{
		// Validate the operation
		$validationAndLoad = $this->validateAndLoad('delete');
		if ($validationAndLoad !== true) return $validationAndLoad;

		// Check grant
		$grantOperation = $this->grantOperation('delete');
		if ($grantOperation !== true) return $grantOperation;

		try {
			DB::beginTransaction();
			$this->signage->delete();
			DB::commit();
			return Que::passa(true, 'auth.signage.deleted', '', $this->signage);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.signage.delete.error ' . $this->signage);
		}
	}

	private function create()
	{
		try {
			DB::beginTransaction();

			if (!$this->permissionService::hasPermission('Signages.auth.signages.add'))
				return Que::passa(false, 'auth.signages.add.error.unauthorized');

			$this->record['client_id'] = $this->currentClientId;
			$this->signage = Signage::create($this->record);
			$this->signageId = $this->signage->id;
			DB::commit();
			return Que::passa(true, 'auth.signage.created', '', $this->signage, ['signage'  => ['record' => $this->signage]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.signage.create ' . $this->signageId);
		}
	}

	private function update()
	{
		if (!$this->permissionService::hasPermission('Signages.auth.signages.edit'))
			return Que::passa(false, 'auth.signages.edit.error.unauthorized');

		try {
			DB::beginTransaction();
			$this->signage->name = $this->record['name'];
			$this->signage->is_active = $this->record['is_active'];
			$this->signage->description = $this->record['description'];
			$this->signage->save();
			DB::commit();
			return Que::passa(true, 'auth.signage.updated', '', $this->signage, ['signage'  => ['record' => $this->signage]]);
		} catch (Exception $e) {
			DB::rollBack();
			return Que::passa(false, 'generic.server_error', 'auth.signages.create ' . $this->signageId);
		}
	}

	public function addToBroadcast(Request $request)
	{
		try {
			$request->validate([
				'slide_id' => 'required|string',
				'signage_id' => 'required|string'
			]);
			$order = Slide::where('signage_id', $request->signage_id)->count() + 1;
			$slide = Slide::create([
				'id' => Str::uuid(),
				'image_time' => 10,
				'image_order' => $order,
				'signage_id' => $request->signage_id,
				'repository_id' => $request->slide_id
			]);
			return Que::passa(true, 'auth.signage.slide_added', '', $slide);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.signage.slide_add.error', $request->slide_id);
		}
	}

	public function slides()
	{
		try {

			// Validate the operation
			$validationAndLoad = $this->validateAndLoad('show');
			if ($validationAndLoad !== true) return $validationAndLoad;

			// Check grant
			$grantOperation = $this->grantOperation('show');
			if ($grantOperation !== true) return $grantOperation;

			$slides = Slide::select(
				'custom_signages_slides.id',
				'custom_repository.display_name',
				'custom_signages_slides.image_time',
				'custom_signages_slides.repository_id'
			)
				->join('custom_repository', 'custom_signages_slides.repository_id', '=', 'custom_repository.id')
				->where('signage_id', $this->signageId)->orderBy('image_order')
				->get();

			return Que::passa(true, 'auth.signage.slides.list', '', null, ['slides'  => $slides]);
		} catch (Exception $e) {
			return Que::passa(false, 'generic.server_error', 'auth.signage.slides.list' . $this->signageId);
		}
	}

	public function setSlideTime(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$slide = Slide::FindOrFail($request->id);
			$slide->image_time = $request->time;
			$slide->save();
			return Que::passa(true, 'auth.signage.slide_time_updated', '', $slide);
		} catch (Exception $e) {
			return Que::passa(true, 'auth.signage.slide_time_update.error', $request->id);
		}
	}

	public function moveSlideUp(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$file = Slide::findOrFail($request->id);
			$fileUp = Slide::where('image_order', $file->image_order - 1)
				->where('signage_id', $file->signage_id)
				->orderBy('image_order', 'desc')
				->first();
			if ($fileUp) $this->swapOrder($file, $fileUp);
			return Que::passa(true, 'auth.signage.file_uped', '', $file);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.signage.file_up.error');
		}
	}

	public function moveSlideDown(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$file = Slide::findOrFail($request->id);
			$fileDown = Slide::where('image_order', $file->image_order + 1)
				->where('signage_id', $file->signage_id)
				->orderBy('image_order', 'desc')
				->first();
			if ($fileDown) $this->swapOrder($file, $fileDown);
			return Que::passa(true, 'auth.signage.file_downed', '', $file);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.signage.file_down.error');
		}
	}

	public function deleteSlide(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$file = Slide::findOrFail($request->id);
			$file->delete();
			return Que::passa(true, 'auth.signage.slide_deleted', '', $file);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.signage.slide_delete.error', $request->id);
		}
	}

	private function swapOrder($file01, $file02)
	{
		DB::transaction(function () use ($file01, $file02) {
			$tempOrder = $file01->image_order;
			$file01->image_order = $file02->image_order;
			$file02->image_order = $tempOrder;

			$file01->save();
			$file02->save();
		});
	}

	private function validateAndLoad($operation)
	{
		// Handle new record
		if (is_array($this->record) && !array_key_exists('id', $this->record)) {
			$this->newRecord = true;
			return true;
		}

		// Validate UUID format
		if (!Str::isUuid($this->signageId)) return Que::passa(false, 'auth.signage.' . $operation . '.invalid_id', $this->signageId);

		// Try to find the client
		$this->signage = Signage::find($this->signageId);
		if (!$this->signage) return Que::passa(false, 'auth.signage.' . $operation . '.not_found', $this->signage);

		return true;
	}

	private function grantOperation($operation)
	{

		if (!$this->permissionService::hasPermission('Profiles.auth.profiles.' . $operation))
			return Que::passa(false, 'auth.profiles.' . $operation . '.error.unauthorized');

		if (!$this->signageService->signages()->contains('id', $this->signageId))
			return Que::passa(false, 'auth.profiles.show.unauthorized', $this->signageId);

		return true;
	}
}
