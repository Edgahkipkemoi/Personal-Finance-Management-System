<?php
/**
 * M-Pesa Daraja API Configuration
 * Personal Finance Management System
 *
 * Credentials: Use the Safaricom Daraja sandbox by default.
 * For production replace with live credentials from developer.safaricom.co.ke
 *
 * SANDBOX shortcodes:  174379  (Lipa Na M-Pesa Online / STK Push)
 * SANDBOX passkey:     bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
 */

class MpesaConfig {
    // ── Environment ─────────────────────────────────────────────────────────
    const ENVIRONMENT   = 'sandbox';   // 'sandbox' | 'production'

    // ── Sandbox credentials (replace with your Daraja app credentials) ──────
    const CONSUMER_KEY    = 'UNODUAlWDEAUQ4GBYlY1x1mukLRgQzWTTtF0MJsGH8HUEYEf';
    const CONSUMER_SECRET = 'AgJvi7gT4QYA5ATB1OvjaGCngHoPN8PA4xYg8QKAVmlIZhAAvbOs6Pw3eShGqvxR';

    // ── Lipa Na M-Pesa (STK Push) ────────────────────────────────────────────
    const SHORTCODE       = '174379';
    const PASSKEY         = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    // ── Callback URL (must be publicly reachable; use ngrok in development) ──
    const CALLBACK_URL    = 'https://your-ngrok-url.ngrok.io/backend/mpesa/callback.php';

    // ── API Base URLs ─────────────────────────────────────────────────────────
    const SANDBOX_BASE    = 'https://sandbox.safaricom.co.ke';
    const LIVE_BASE       = 'https://api.safaricom.co.ke';

    public static function baseUrl(): string {
        return self::ENVIRONMENT === 'production' ? self::LIVE_BASE : self::SANDBOX_BASE;
    }

    public static function isSandbox(): bool {
        return self::ENVIRONMENT === 'sandbox';
    }
}
