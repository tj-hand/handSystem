<?php

namespace App\Http\Controllers;

// Import Tools
use Exception;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Import Models
use App\Models\Repository;

// Import Services
use App\Services\PermissionService;
use App\Services\SystemLogService as Que;

class RepositoryController extends Controller
{

	public function view(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$clientId = PermissionService::UserCurrentAccountProperties()->current_client;
			$file = Repository::where('client_id', $clientId)->where('id', $request->id)->first();
			$path = "uploads/{$clientId}/{$file->id}";
			if (!$file || !Storage::disk('private')->exists($path)) return Que::passa(false, 'auth.repository.view.error.not_found', $request->id);
			$fullPath = Storage::disk('private')->path($path);
			$mimeType = mime_content_type($fullPath);
			Que::passa(true, 'auth.repository.view', '', $file);
			return response()->download($fullPath, $file->id, [
				'Content-Type' => $mimeType,
				'Content-Disposition' => 'inline; filename="' . $file->id . '"'
			]);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.repository.view.error.server_error', $request->id);
		}
	}

	public function upload(Request $request)
	{
		try {

			$clientId = PermissionService::UserCurrentAccountProperties()->current_client;

			$request->validate([
				'files' => 'required|array',
				'files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi,wmv,mkv|max:51200',
			]);

			$uploadedFiles = $request->file('files');
			$repositoryIds = '';

			foreach ($uploadedFiles as $file) {

				$fileUuid = Uuid::uuid4();
				$directory = '/uploads/' . $clientId;

				if (!Storage::disk('private')->exists($directory)) Storage::disk('private')->makeDirectory($directory, 0775, true);
				Storage::disk('private')->putFileAs($directory, $file, $fileUuid);

				Repository::create([
					'id' => $fileUuid,
					'original_name' => $file->getClientOriginalName(),
					'display_name' => $file->getClientOriginalName(),
					'file_type' => 'static',
					'client_id' => $clientId
				]);
				$repositoryIds .= $fileUuid . ' - ';
			}

			return Que::Passa(true, 'auth.repository.upload', $repositoryIds);
		} catch (Exception $e) {
			return Que::Passa(false, 'auth.repository.upload.error');
		}
	}

	public function rename(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string', 'name' => 'required|string']);
			$file = Repository::find($request->id);
			$file->display_name = $request->name;
			$file->save();
			return Que::passa(true, 'auth.repository.rename', '', $file);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.repository.rename.error', $request->id);
		}
	}

	public function destroy(Request $request)
	{
		try {
			$request->validate(['id' => 'required|string']);
			$clientId = PermissionService::UserCurrentAccountProperties()->current_client;
			$file = Repository::find($request->id);
			$file->delete();
			Storage::disk('private')->delete("uploads/{$clientId}/" . $request->id);
			return Que::passa(true, 'auth.repository.destroy', '', $file);
		} catch (Exception $e) {
			return Que::passa(false, 'auth.repository.destroy.error', $request->id);
		}
	}
}
