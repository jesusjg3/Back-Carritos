<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send a notification to a specific user using Expo Push API.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = [])
    {
        $devices = UserDevice::where('user_id', $userId)->where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $tokens = $devices->pluck('fcm_token')->toArray();
        $success = true;

        $messages = [];
        foreach ($tokens as $token) {
            // Validamos que el token tenga el formato correcto de Expo
            if (strpos($token, 'ExponentPushToken') === false && strpos($token, 'ExpoPushToken') === false) {
                Log::warning("Token inválido para Expo Push: {$token}");
                continue;
            }

            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => empty($data) ? (object)[] : $data,
                'channelId' => 'default', // Asegurarse que coincida con el channel del app.json/frontend
            ];
        }

        if (empty($messages)) {
            return false;
        }

        try {
            $response = Http::post('https://exp.host/--/api/v2/push/send', $messages);

            if ($response->failed()) {
                Log::error("Expo Push Send Error: " . $response->body());
                $success = false;
            } else {
                // Expo devuelve un array de "tickets" para cada mensaje
                $responseData = $response->json('data');
                
                if (is_array($responseData)) {
                    foreach ($responseData as $index => $ticket) {
                        if (isset($ticket['status']) && $ticket['status'] === 'error') {
                            $errorCode = $ticket['details']['error'] ?? 'Unknown Error';
                            Log::error("Expo Push Error para el token {$messages[$index]['to']}: {$errorCode}");
                            
                            // Si el token ya no es válido, lo borramos
                            if ($errorCode === 'DeviceNotRegistered') {
                                UserDevice::where('fcm_token', $messages[$index]['to'])->delete();
                            }
                            $success = false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error enviando push notification (Expo): ' . $e->getMessage());
            $success = false;
        }

        return $success;
    }
}
