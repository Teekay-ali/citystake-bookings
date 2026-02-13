<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $client;
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
        $this->baseUrl = 'https://api.paystack.co';
    }

    public function initializeTransaction(array $data)
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/transaction/initialize", [
                'headers' => [
                    'Authorization' => "Bearer {$this->secretKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Paystack Initialize Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function verifyTransaction(string $reference)
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/transaction/verify/{$reference}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->secretKey}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Paystack Verify Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }
}
