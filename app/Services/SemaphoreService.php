<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SemaphoreService
{
    protected $client;
    protected $apiKey;
    protected $senderName;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.semaphore.api_key');
        $this->senderName = config('services.semaphore.sender_name', 'SIKAP');
    }

    public function send(string $phone, string $message): void
    {
        try {
            // Format phone: replace leading 0 with 63 (09XXXXXXXXX -> 639XXXXXXXXX)
            $formattedPhone = preg_replace('/^0/', '63', $phone);

            $response = $this->client->post('https://api.semaphore.co/api/v4/messages', [
                'form_params' => [
                    'apikey' => $this->apiKey,
                    'number' => $formattedPhone,
                    'message' => $message,
                    'sendername' => $this->senderName,
                ]
            ]);

            Log::info('SMS sent successfully', [
                'phone' => $formattedPhone,
                'message' => $message,
                'response' => $response->getBody()->getContents()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone' => $phone,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
            // SMS failure should not break the app flow, so we don't throw the exception
        }
    }
}
