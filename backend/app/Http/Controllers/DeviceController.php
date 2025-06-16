<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Client;
use App\Models\Device;
use App\Models\Signage;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Validator;
use App\Services\SystemLogService as Que;


class DeviceController extends Controller
{

	private $client;
	private $errorResponse = null;

	public function __construct()
	{

		$clientId = PermissionService::UserCurrentAccountProperties()->current_client;
		$this->client = Client::find($clientId);
		if (!$this->client) $this->errorResponse = 'generic.clientNotFound';
	}

	public function index()
	{

		if ($this->errorResponse) return Que::passa(false, $this->errorResponse);

		try {
			$devices = Device::select('id', 'device_name AS nome')
				->where('client_id', $this->client->id)
				->orderBy('device_name')
				->get();
			return Que::Passa(true, 'devices.index', '', $this->client, ['devices' => $devices]);
		} catch (Exception $e) {
			return Que::Passa(false, 'devices.indexServerError', $this->client);
		}
	}

	public function show(Request $request)
	{

		if ($this->errorResponse) return Que::passa(false, $this->errorResponse);

		$uuidValidator = Validator::make(['uuid' => $request->id], ['uuid' => 'required|uuid']);
		if ($uuidValidator->fails()) return Que::Passa(false, 'devices.showInvalidId');

		try {
			$device = Device::select('id', 'device_name', 'custom_sinalizacao_id', 'status', 'transition')
				->where('admin_cliente_id', $this->client->id)
				->where('id', $request->id)
				->orderBy('device_name')
				->first();
			return ($device)
				? Que::Passa(true, 'devices.show', '', $this->client, ['device' => $device])
				: Que::Passa(false, 'devices.show.deviceNotFound', null);
		} catch (Exception $e) {
			return Que::Passa(false, 'devices.show.ServerError', null);
		}
	}

	public function broadcastOptions(Request $request)
	{

		if ($this->errorResponse) return Que::passa(false, $this->errorResponse);

		$uuidValidator = Validator::make(['uuid' => $request->id], ['uuid' => 'required|uuid']);
		if ($uuidValidator->fails()) return Que::Passa(false, 'devices.showInvalidId');

		try {
			$broadcasts = Signage::select('id', 'nome')
				->where('admin_cliente_id', $this->client->id)
				->where('active', true)
				->orderBy('nome')
				->get();
			return ($broadcasts)
				? Que::Passa(true, 'devices.show', '', $this->client, ['broadcasts' => $broadcasts])
				: Que::Passa(false, 'devices.broadcastOptions.unavaible', null);
		} catch (Exception $e) {
			return Que::Passa(false, 'devices.broadcastOptions.serverError', null);
		}
	}

	public function store(Request $request)
	{

		if ($this->errorResponse) return Que::passa(false, $this->errorResponse);

		$uuidValidator = Validator::make(['uuid' => $request->id], ['uuid' => 'required|uuid']);
		if ($uuidValidator->fails()) return Que::Passa(false, 'devices.storeInvalidId');

		try {
			$device = Device::where('client_id', $this->client->id)->where('id', $request->id)->orderBy('device_name')->first();

			if ($device) {
				$device->custom_sinalizacao_id = $request->custom_sinalizacao_id;
				$device->status  = $request->status;
				$device->transition = $request->transition;
				$device->save();
			}

			return ($device)
				? Que::Passa(true, 'devices.store', '', $this->client, ['device' => $device])
				: Que::Passa(false, 'devices.store.deviceNotFound', null);
		} catch (Exception $e) {
			return Que::Passa(false, 'devices.store.ServerError', null);
		}
	}
}
