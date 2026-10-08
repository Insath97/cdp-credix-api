<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;

class CustomerVerificationJwtService
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = (string) (config('jwt.secret') ?: config('app.key'));
        if (str_starts_with($this->secret, 'base64:')) {
            $this->secret = base64_decode(substr($this->secret, 7));
        }
    }

    /**
     * Generate a signed JWT token containing the verification payload.
     *
     * @param int $reviewId
     * @param int $customerId
     * @param int $snapshotVersion
     * @param string $otpHash
     * @param int $ttlMinutes (Default: 30 minutes)
     * @return string
     */
    public function generateToken(
        int $reviewId,
        int $customerId,
        int $snapshotVersion,
        string $otpHash,
        int $ttlMinutes = 30
    ): string {
        $now = time();
        $exp = $now + ($ttlMinutes * 60);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss'              => config('app.name', 'cdp-credix-api'),
            'iat'              => $now,
            'exp'              => $exp,
            'jti'              => (string) Str::uuid(),
            'purpose'          => 'customer_kyc_verification',
            'review_id'        => $reviewId,
            'customer_id'      => $customerId,
            'snapshot_version' => $snapshotVersion,
            'otp_hash'         => $otpHash,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Verify and decode the JWT token.
     *
     * @param string $token
     * @return array Decoded payload claims
     * @throws Exception
     */
    public function verifyToken(string $token): array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format.');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->secret, true);
        $providedSignature = $this->base64UrlDecode($signatureEncoded);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new Exception('Invalid token signature.');
        }

        $payloadJson = $this->base64UrlDecode($payloadEncoded);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            throw new Exception('Invalid token payload.');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired.');
        }

        if (!isset($payload['purpose']) || $payload['purpose'] !== 'customer_kyc_verification') {
            throw new Exception('Invalid token purpose.');
        }

        if (empty($payload['review_id']) || empty($payload['otp_hash'])) {
            throw new Exception('Token is missing required verification claims.');
        }

        return $payload;
    }

    /**
     * Base64URL encode.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode.
     */
    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
