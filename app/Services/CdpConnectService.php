<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;


class CdpConnectService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {

        $this->baseUrl = (string) config('services.cdp_connect.base_url');
        $this->apiKey  = (string) config('services.cdp_connect.api_key');
        $this->timeout = (int) config('services.cdp_connect.timeout', 15);
    }

    /**
     * Retrieve customer details, total investment amounts, products, and expiry
     * dates from CDP Connect.
     *
     * @param  string      $idNumber Customer identification number (NIC, Passport, etc.)
     * @param  string|null $idType   Optional ID type (nic, passport, driving_license, other)
     * @param  string|null $status   Optional investment status filter (all, approved, expired)
     */
    public function getCustomerInvestments(string $idNumber, ?string $idType = null, ?string $status = null): array
    {

        if ($this->baseUrl === '' || $this->apiKey === '') {
            Log::error('[CdpConnectService] Not configured. Set CDP_CONNECT_BASE_URL and CDP_CONNECT_API_KEY in .env.');

            return [
                'success'     => false,
                'status_code' => 503,
                'data'        => null,
                'message'     => 'CDP Connect is not configured on this environment.',
            ];
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/api/v1/external/customer-investments';

            $params = [
                'id_number' => trim($idNumber),
            ];

            if (!empty($idType)) {
                $params['id_type'] = strtolower(trim($idType));
            }

            if (!empty($status)) {
                $params['status'] = $status;
            }

            $response = Http::withHeaders([

                config('services.cdp_connect.key_header', 'X-Credix-Key') => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout($this->timeout)->get($url, $params);

            if ($response->successful()) {
                return [
                    'success'     => true,
                    'status_code' => 200,
                    'data'        => $response->json('data'),
                    'message'     => $response->json('message', 'Customer details retrieved successfully'),
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success'     => false,
                    'status_code' => 404,
                    'data'        => null,
                    'message'     => $response->json('message', 'Customer not found in CDP Connect'),
                ];
            }

            if ($response->status() === 401) {
                Log::error('[CdpConnectService] 401 Unauthorized. Check CDP_CONNECT_API_KEY in .env.');

                return [
                    'success'     => false,
                    'status_code' => 401,
                    'data'        => null,
                    'message'     => 'Unauthorized connection to CDP Connect API.',
                ];
            }

            if ($response->status() === 422) {
                return [
                    'success'     => false,
                    'status_code' => 422,
                    'data'        => null,
                    'message'     => $response->json('message', 'Invalid request parameters'),
                ];
            }

            Log::warning('[CdpConnectService] Unexpected response status: ' . $response->status(), [
                'body' => $response->body(),
            ]);

            return [
                'success'     => false,
                'status_code' => $response->status(),
                'data'        => null,
                'message'     => 'Unexpected error from CDP Connect service.',
            ];

        } catch (Throwable $e) {

            Log::error('[CdpConnectService] Connection exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success'     => false,
                'status_code' => 500,
                'data'        => null,
                'message'     => config('app.debug')
                    ? 'Unable to connect to CDP Connect API: ' . $e->getMessage()
                    : 'Unable to connect to CDP Connect API.',
            ];
        }
    }
}
