<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $baseUrl;
    protected $sendSmsUrl;
    protected $username;
    protected $password;
    protected $mask;

    public function __construct()
    {
        $this->baseUrl = config('services.dialog_sms.url');
        $this->sendSmsUrl = config('services.dialog_sms.send_url');
        $this->username = config('services.dialog_sms.username');
        $this->password = config('services.dialog_sms.password');
        $this->mask = config('services.dialog_sms.mask');
    }

    /**
     * Get access token from Dialog API
     */
    private function getAccessToken(): ?string
    {
        if (Cache::has('dialog_sms_token')) {
            return Cache::get('dialog_sms_token');
        }

        try {
            $response = Http::post($this->baseUrl . '/api/v2/user/login', [
                'username' => $this->username,
                'password' => $this->password
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success') {
                    Cache::put('dialog_sms_token', $data['token'], now()->addSeconds($data['expiration']));

                    Log::info('Dialog SMS Token Generated Successfully');
                    return $data['token'];
                }
            }

            Log::error('Failed to get Dialog SMS Token', [
                'response' => $response->body()
            ]);

            return null;

        } catch (\Throwable $th) {
            Log::error('Dialog SMS Token Error: ' . $th->getMessage());
            return null;
        }
    }

    /**
     * Send SMS via Dialog Gateway
     *
     * @param string|array $numbers Single number or array of numbers
     * @param string $message
     * @param int $paymentMethod 0=wallet, 4=package
     * @return bool
     */
    public function sendSms($numbers, string $message, int $paymentMethod = 0): bool
    {
        try {

            $token = $this->getAccessToken();
            if (!$token) {
                Log::error('Cannot send SMS: No valid access token');
                return false;
            }

            $numbers = is_array($numbers) ? $numbers : [$numbers];
            $formattedNumbers = [];

            foreach ($numbers as $number) {
                $formattedNumbers[] = [
                    'mobile' => $this->formatNumber($number)
                ];
            }

            $transactionId = time() . rand(100, 999);

            $payload = [
                'msisdn' => $formattedNumbers,
                'sourceAddress' => $this->mask,
                'message' => $message,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
            ];

            Log::info('Sending SMS via Dialog API', [
                'numbers_count' => count($formattedNumbers),
                'transaction_id' => $transactionId
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post($this->sendSmsUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success') {
                    Log::info('SMS Sent Successfully', [
                        'campaign_id' => $data['data']['campaignId'] ?? null,
                        'campaign_cost' => $data['data']['campaignCost'] ?? null,
                        'wallet_balance' => $data['data']['walletBalance'] ?? null,
                        'transaction_id' => $transactionId
                    ]);
                    return true;
                }
            }

            Log::error('SMS API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'errCode' => $response->json('errCode') ?? 'Unknown',
                'transaction_id' => $transactionId
            ]);

            return false;

        } catch (\Throwable $th) {
            Log::error('SMS Service Error: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check campaign status by transaction ID
     */
    public function checkCampaignStatus(string $transactionId): ?array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/v2/sms/check-transaction', [
                'transaction_id' => $transactionId
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Throwable $th) {
            Log::error('Check Campaign Status Error: ' . $th->getMessage());
            return null;
        }
    }

    /**
     * Get account balance (for GET request users)
     */
    public function getBalance(string $esmsqk): ?float
    {
        try {
            $response = Http::get($this->baseUrl . '/api/v1/message-via-url/check/balance', [
                'esmsqk' => $esmsqk
            ]);

            if ($response->successful()) {
                $body = $response->body();
                $parts = explode('|', $body);

                if ($parts[0] == 1) {
                    return floatval($parts[1]);
                }
            }

            return null;

        } catch (\Throwable $th) {
            Log::error('Get Balance Error: ' . $th->getMessage());
            return null;
        }
    }

    /**
     * Format phone number to required format (7XXXXXXXX - 9 digits)
     */
    protected function formatNumber(string $number): string
    {
        return self::normalise($number);
    }

    /**
     * The canonical 9-digit form of a number, which is what identifies a
     * handset. 0752932640, 752932640 and +94752932640 are one phone, and are
     * all stored as-typed -- customers.phone_primary carries no uniqueness of
     * any kind -- so anything that needs to ask "have I already texted this
     * person?" has to compare on this rather than on the raw column.
     *
     * Static because NotificationService dedupes on it without needing a
     * gateway client; formatNumber() stays the instance-side name the send
     * path has always used.
     */
    public static function normalise(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (str_starts_with($number, '94')) {
            $number = substr($number, 2);
        }

        if (str_starts_with($number, '0')) {
            $number = substr($number, 1);
        }

        if (strlen($number) > 9) {
            $number = substr($number, -9);
        }

        return $number;
    }

    /**
     * Send SMS to multiple recipients
     */
    public function sendBulkSms(array $numbers, string $message, int $paymentMethod = 0): array
    {
        $results = [];

        $success = $this->sendSms($numbers, $message, $paymentMethod);

        foreach ($numbers as $number) {
            $results[$number] = $success;
        }

        return $results;
    }
}
