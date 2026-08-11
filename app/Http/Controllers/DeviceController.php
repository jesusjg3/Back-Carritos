<?php

namespace App\Http\Controllers;

use App\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    protected DeviceService $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'expo_token' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $device = $this->deviceService->registerExpoToken(
            Auth::id(),
            $request->expo_token,
            $request->device_name
        );

        return response()->json([
            'message' => 'Expo Push Token registered successfully',
            'device' => $device,
        ]);
    }
}
