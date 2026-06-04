<?php

declare(strict_types=1);

namespace App\Core;

class Stripe
{
    private static function request(string $method, string $endpoint, array $params = []): array
    {
        $ch = curl_init();

        $opts = [
            CURLOPT_URL            => 'https://api.stripe.com/v1' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $_ENV['STRIPE_SECRET_KEY'] . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = self::buildQuery($params);
        }

        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException('Stripe cURL error: ' . $err);

        $data = json_decode($body, true);
        if (isset($data['error'])) {
            throw new \RuntimeException('Stripe API error: ' . ($data['error']['message'] ?? 'Unknown'));
        }

        return $data;
    }

    // Flatten nested array to Stripe's bracket notation: line_items[0][price_data][currency]
    private static function buildQuery(array $params, string $prefix = ''): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            $fullKey = $prefix ? "{$prefix}[{$key}]" : $key;
            if (is_array($value)) {
                $parts[] = self::buildQuery($value, $fullKey);
            } else {
                $parts[] = urlencode($fullKey) . '=' . urlencode((string)$value);
            }
        }
        return implode('&', $parts);
    }

    public static function createCheckoutSession(array $params): array
    {
        return self::request('POST', '/checkout/sessions', $params);
    }

    public static function retrieveSession(string $sessionId): array
    {
        return self::request('GET', '/checkout/sessions/' . urlencode($sessionId));
    }

    public static function verifyWebhook(string $payload, string $sigHeader, string $secret): ?array
    {
        $parts      = [];
        $timestamp  = '';
        $signatures = [];

        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = explode('=', trim($part), 2);
            if ($k === 't') $timestamp = $v;
            if ($k === 'v1') $signatures[] = $v;
        }

        if (!$timestamp || !$signatures) return null;

        // Reject webhooks older than 5 minutes
        if (abs(time() - (int)$timestamp) > 300) return null;

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        $valid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) { $valid = true; break; }
        }

        if (!$valid) return null;

        return json_decode($payload, true);
    }
}
