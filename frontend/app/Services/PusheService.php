<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PusheService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.pushe.api_key');
    }

    public function send(array $userIds, string $title, string $body, array $data = [])
    {
        if (!$this->apiKey) return;

        try {
            $response = $this->client->post('https://api.pushe.co/v2/pushes', [
                'headers' => [
                    'Authorization' => 'Token ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'app_ids' => [config('services.pushe.app_id')],
                    'data' => [
                        'title' => $title,
                        'content' => $body,
                        'data' => $data,
                    ],
                    'filters' => [
                        'user_ids' => $userIds,
                    ],
                ]
            ]);

            Log::info('Pushe push sent', ['response' => $response->getStatusCode()]);
        } catch (\Exception $e) {
            Log::error('Pushe error: ' . $e->getMessage());
        }
    }
}