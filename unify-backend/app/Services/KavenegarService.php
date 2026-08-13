<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class KavenegarService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.kavenegar.api_key');
    }

    public function send(string $receptor, string $message)
    {
        if (!$this->apiKey) return;

        try {
            $this->client->post("https://api.kavenegar.com/v1/{$this->apiKey}/sms/send.json", [
                'form_params' => [
                    'receptor' => $receptor,
                    'message' => $message,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Kavenegar error: ' . $e->getMessage());
        }
    }
}