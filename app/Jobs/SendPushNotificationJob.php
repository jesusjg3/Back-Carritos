<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ExpoPushService;
use Exception;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $title;
    public $body;
    public $data;

    public function __construct($userId, $title, $body, $data = [])
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function handle(ExpoPushService $pushService)
    {
        try {
            $pushService->sendToUser($this->userId, $this->title, $this->body, $this->data);
        } catch (Exception $e) {
            Log::error("Error enviando push asíncrono al usuario {$this->userId}: " . $e->getMessage());
        }
    }
}
