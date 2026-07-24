<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    /**
     * Store or update a user's Expo Push Token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'expo_token' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Check if the token already exists for another user and remove it
        UserDevice::where('expo_token', $request->expo_token)
            ->where('user_id', '!=', $user->id)
            ->delete();

        // Update or create the token for the current user
        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'expo_token' => $request->expo_token,
            ],
            [
                'device_name' => $request->device_name,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Expo Push Token registered successfully',
            'device' => $device,
        ]);
    }
}
