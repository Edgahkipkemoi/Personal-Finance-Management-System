<?php
/**
 * MpesaService — Daraja API wrapper
 * Handles: OAuth token (cached), STK Push, STK Query, Callback processing
 */

require_once __DIR__ . '/../config/mpesa.php';

class MpesaService {

    // Token cache file path (stores token so we don't re-fetch every request)
    private static string $tokenCacheFile;

    public function __construct() {
        self::$tokenCacheFile = sys_get_temp_dir() . '/mpesa_token_' . md5(MpesaConfig::CONSUMER_KEY) . '.json';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1.  OAuth Access Token — CACHED to avoid 650ms overhead per request
    // ─────────────────────────────────────────────────────────────────────────
    public function getAccessToken(): string {
        // Check cache first
        if (file_exists(self::$tokenCacheFile)) {
            $cached = json_decode(file_get_contents(self::$tokenCacheFile), true);
            // Tokens are valid for 3600s — refresh 60s early to be safe
            if (!empty($cached['token']) && isset($cached['expires_at']) && time() < $cached['expires_at'] - 60) {
                return $cached['token'];
            }
        }

        // Fetch fresh token
        $url  = MpesaConfig::baseUrl() . '/oauth/v1/generate?grant_type=client_credentials';
        $cred = base64_encode(MpesaConfig::CONSUMER_KEY . ':' . MpesaConfig::CONSUMER_SECRET);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $cred],
            CURLOPT_SSL_VERIFYPEER => !MpesaConfig::isSandbox(),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception('M-Pesa OAuth cURL error: ' . $curlErr);
        }
        if ($httpCode !== 200) {
            throw new Exception('M-Pesa OAuth failed (HTTP ' . $httpCode . '): ' . $result);
        }

        $data = json_decode($result, true);
        if (empty($data['access_token'])) {
            throw new Exception('M-Pesa OAuth: no access_token in response: ' . $result);
        }

        // Cache the token
        $expiresIn = (int)($data['expires_in'] ?? 3600);
        file_put_contents(self::$tokenCacheFile, json_encode([
            'token'      => $data['access_token'],
            'expires_at' => time() + $expiresIn,
        ]));

        return $data['access_token'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2.  STK Push (Lipa Na M-Pesa Online)
    // ─────────────────────────────────────────────────────────────────────────
    public function stkPush(string $phone, float $amount, string $ref, string $desc): array {
        $token     = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password  = base64_encode(MpesaConfig::SHORTCODE . MpesaConfig::PASSKEY . $timestamp);

        $payload = [
            'BusinessShortCode' => MpesaConfig::SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            'PartyB'            => MpesaConfig::SHORTCODE,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => MpesaConfig::CALLBACK_URL,
            'AccountReference'  => substr($ref, 0, 12),
            'TransactionDesc'   => substr($desc, 0, 20),
        ];

        return $this->post('/mpesa/stkpush/v1/processrequest', $payload, $token);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3.  STK Push Query (check payment status)
    // ─────────────────────────────────────────────────────────────────────────
    public function stkQuery(string $checkoutRequestId): array {
        $token     = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password  = base64_encode(MpesaConfig::SHORTCODE . MpesaConfig::PASSKEY . $timestamp);

        $payload = [
            'BusinessShortCode' => MpesaConfig::SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        return $this->post('/mpesa/stkpushquery/v1/query', $payload, $token);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4.  Internal HTTP helper
    // ─────────────────────────────────────────────────────────────────────────
    private function post(string $endpoint, array $payload, string $token): array {
        $url = MpesaConfig::baseUrl() . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => !MpesaConfig::isSandbox(),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception('cURL error: ' . $err);
        }

        $data = json_decode($result, true) ?? [];
        $data['_http_code'] = $httpCode;
        return $data;
    }
}
