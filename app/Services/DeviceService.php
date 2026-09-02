<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;

class DeviceService
{
    public function registerExpoToken(int $userId, string $expoToken, ?string $deviceName)
    {
        return DB::transaction(function () use ($userId, $expoToken, $deviceName) {
            UserDevice::where('expo_token', $expoToken)
                ->where('user_id', '!=', $userId)
                ->delete();

            return UserDevice::updateOrCreate(
                [
                    'user_id' => $userId,
                    'expo_token' => $expoToken,
                ],
                [
                    'device_name' => $deviceName,
                    'is_active' => true,
                ]
            );
        });
    }
}
