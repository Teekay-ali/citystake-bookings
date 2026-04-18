<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonnifyService
{
    protected Client $client;
    protected string $apiKey;
    protected string $secretKey;
    protected string $contractCode;
    protected string $baseUrl;

    public function __construct()
    {
        $this->client       = new Client();
        $this->apiKey       = config('services.monnify.api_key');
        $this->secretKey    = config('services.monnify.secret_key');
        $this->contractCode = config('services.monnify.contract_code');
        $this->baseUrl      = config('services.monnify.base_url');
    }

    /**
     * Monnify uses OAuth2. Get a Bearer token (cached for 55 minutes).
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('monnify_access_token', 55 * 60, function () {
            $credentials = base64_encode("{$this->apiKey}:{$this->secretKey}");

            $response = $this->client->post("{$this->baseUrl}/api/v1/auth/login", [
                'headers' => [
                    'Authorization' => "Basic {$credentials}",
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! ($data['requestSuccessful'] ?? false)) {
                throw new \RuntimeException('Monnify authentication failed.');
            }

            return $data['responseBody']['accessToken'];
        });
    }

    /**
     * Verify a transaction by paymentReference (the reference you generated).
     * Returns the full responseBody or throws on failure.
     */
    public function verifyTransaction(string $paymentReference): array
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->client->get("{$this->baseUrl}/api/v2/merchant/transactions/query", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'paymentReference' => $paymentReference,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! ($data['requestSuccessful'] ?? false)) {
                throw new \RuntimeException('Monnify transaction query failed: ' . ($data['responseMessage'] ?? 'unknown error'));
            }

            return $data['responseBody'];

        } catch (GuzzleException $e) {
            Log::error('Monnify Verify Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getContractCode(): string
    {
        return $this->contractCode;
    }

    public function isTestMode(): bool
    {
        return config('services.monnify.mode') === 'test';
    }
}
