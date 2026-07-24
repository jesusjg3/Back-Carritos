<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    /**
     * Send an Expo Push Notification to a specific user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = [])
    {
        $devices = UserDevice::where('user_id', $userId)->where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $messages = [];
        foreach ($devices as $device) {
            $token = $device->expo_token;
            
            if (strpos($token, 'ExponentPushToken') === false && strpos($token, 'ExpoPushToken') === false) {
                Log::warning("Token inválido para Expo Push en ExpoPushService: {$token}");
                continue;
            }

            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => empty($data) ? (object)[] : $data,
                'channelId' => 'default',
            ];
        }

        if (empty($messages)) {
            return false;
        }

        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', $messages);

            if ($response->failed()) {
                Log::error('Error enviando Expo Push: ' . $response->body());
                return false;
            }

            $responseData = $response->json();
            
            // Si el token falló porque ya no es válido (DeviceNotRegistered), lo borramos
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $index => $result) {
                    if (isset($result['status']) && $result['status'] === 'error') {
                        if (isset($result['details']['error']) && $result['details']['error'] === 'DeviceNotRegistered') {
                            $invalidToken = $messages[$index]['to'];
                            UserDevice::where('expo_token', $invalidToken)->delete();
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error ejecutando push a Expo: ' . $e->getMessage());
            return false;
        }
    }
}
