<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;

class PushNotificationService
{
    protected $credentialsPath;
    protected $projectId;

    public function __construct()
    {
        $this->credentialsPath = storage_path('app/firebase_credentials.json');
        
        if (file_exists($this->credentialsPath)) {
            $credentialsData = json_decode(file_get_contents($this->credentialsPath), true);
            $this->projectId = $credentialsData['project_id'] ?? null;
        } else {
            Log::warning('Firebase credentials file not found at: ' . $this->credentialsPath);
        }
    }

    /**
     * Obtiene el Access Token de OAuth2 para FCM usando google/auth.
     */
    protected function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            try {
                $credentials = new ServiceAccountCredentials(
                    ['https://www.googleapis.com/auth/firebase.messaging'],
                    $this->credentialsPath
                );
                
                $token = $credentials->fetchAuthToken();
                
                return $token['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('Error generating FCM Auth Token: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Send a notification to a specific user using FCM HTTP v1 API.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = [])
    {
        if (!file_exists($this->credentialsPath) || !$this->projectId) {
            Log::warning('Firebase credentials missing or invalid. Cannot send notification.');
            return false;
        }

        $devices = UserDevice::where('user_id', $userId)->where('is_active', true)->get();

        if ($devices->isEmpty()) {
            return false;
        }

        $tokens = $devices->pluck('fcm_token')->toArray();
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error('Could not get FCM Access Token.');
            return false;
        }

        $success = true;

        foreach ($tokens as $token) {
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => empty($data) ? (object)[] : $data, // Must be an object or string dictionary
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'default',
                        ]
                    ]
                ]
            ];

            try {
                $response = Http::withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", $message);

                if ($response->failed()) {
                    Log::error("FCM Send Error for token {$token}: " . $response->body());
                    
                    // Si el token es inválido o no está registrado, lo borramos
                    $errorData = $response->json('error');
                    if (isset($errorData['details'][0]['errorCode']) && 
                       in_array($errorData['details'][0]['errorCode'], ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                        UserDevice::where('fcm_token', $token)->delete();
                    }
                    $success = false;
                }
            } catch (\Exception $e) {
                Log::error('Error sending push notification (HTTP): ' . $e->getMessage());
                $success = false;
            }
        }

        return $success;
    }
}
