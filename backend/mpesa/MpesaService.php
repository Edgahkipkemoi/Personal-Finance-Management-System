<?php
/**
 * MpesaService — Daraja API wrapper
 * Handles: OAuth token, STK Push, STK Query, Callback processing
 */

require_once __DIR__ . '/../config/mpesa.php';

class MpesaService {

    // ─────────────────────────────────────────────────────────────────────────
    // 1.  OAuth Access Token
    // ─────────────────────────────────────────────────────────────────────────
    public function getAccessToken(): string {
        $url  = MpesaConfig::baseUrl() . '/oauth/v1/generate?grant_type=client_credentials';
        $cred = base64_encode(MpesaConfig::CONSUMER_KEY . ':' . MpesaConfig::CONSUMER_SECRET);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $cred],
            CURLOPT_SSL_VERIFYPEER => !MpesaConfig::isSandbox(),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $httpCode !== 200) {
            throw new Exception('M-Pesa OAuth failed (HTTP ' . $httpCode . ')');
        }

        $data = json_decode($result, true);
        if (empty($data['access_token'])) {
            throw new Exception('M-Pesa OAuth: no access_token in response');
        }
        return $data['access_token'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2.  STK Push (Lipa Na M-Pesa Online)
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Initiates a customer-prompted payment (phone receives a PIN prompt).
     *
     * @param  string $phone   International format without +: 2547XXXXXXXX
     * @param  float  $amount  Amount in KES (must be >= 1)
     * @param  string $ref     Account reference shown in the M-Pesa prompt
     * @param  string $desc    Transaction description (max 20 chars)
     * @return array           Raw Daraja response
     */
    public function stkPush(string $phone, float $amount, string $ref, string $desc): array {
        $token     = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password  = base64_encode(MpesaConfig::SHORTCODE . MpesaConfig::PASSKEY . $timestamp);

        $payload = [
            'BusinessShortCode' => MpesaConfig::SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),   // M-Pesa requires whole numbers
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
            'BusinessShortCode'  => MpesaConfig::SHORTCODE,
            'Password'           => $password,
            'Timestamp'          => $timestamp,
            'CheckoutRequestID'  => $checkoutRequestId,
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
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception('cURL error: ' . $err);
        }

        $data = json_decode($result, true);
        $data['_http_code'] = $httpCode;
        return $data ?? ['error' => 'Invalid JSON response', '_http_code' => $httpCode];
    }
}
