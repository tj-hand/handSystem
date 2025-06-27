<?php

namespace App\Console\Commands;

use App\Models\PBIRequest;
use Illuminate\Console\Command;
use App\Services\SyncPBIImagesService;


class ScheduledTasks extends Command
{
	protected $signature = 'scheduled-data'; // The command signature
	protected $description = 'Runs the tasks pendents in backend'; // Description

	public function __construct(
		protected SyncPBIImagesService $syncPBIImagesService
	) {
		parent::__construct();
	}

	public function handle()
	{


		$requests = PBIRequest::where('status', 'pending')->get();

		foreach ($requests as $request) {;
			switch ($request->request_type) {
				case ('pbi_image'):
					$this->syncPBIImagesService->getImageStatus($request->id);
					break;
			}
		}

		$this->syncPBIImagesService->reloadImages();
	}
}
