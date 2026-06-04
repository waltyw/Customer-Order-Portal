<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

class XeroAPI
{
    private const AUTH_URL  = 'https://login.xero.com/identity/connect/authorize';
    private const TOKEN_URL = 'https://identity.xero.com/connect/token';
    private const API_BASE  = 'https://api.xero.com/api.xro/2.0';
    private const SCOPE     = 'openid offline_access accounting.invoices.read accounting.contacts.read';

    // ── OAuth ────────────────────────────────────────────────────────────────

    public static function authUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Setting::set('xero_oauth_state', $state);

        // Build manually so scope uses %20 not + between values
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => Setting::get('xero_client_id'),
            'redirect_uri'  => Setting::get('xero_redirect_uri'),
            'scope'         => self::SCOPE,
            'state'         => $state,
        ]);

        // Xero requires %20 not + in scope
        $params = str_replace('+', '%20', $params);

        return self::AUTH_URL . '?' . $params;
    }

    public static function exchangeCode(string $code, string $state): bool
    {
        if ($state !== Setting::get('xero_oauth_state')) return false;

        $response = self::tokenRequest([
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => Setting::get('xero_redirect_uri'),
        ]);

        if (empty($response['access_token'])) return false;

        self::storeTokens($response);

        // Fetch and store tenant ID
        $tenants = self::getTenants($response['access_token']);
        if (!empty($tenants[0]['tenantId'])) {
            Setting::set('xero_tenant_id', $tenants[0]['tenantId']);
            Setting::set('xero_tenant_name', $tenants[0]['tenantName'] ?? '');
        }

        return true;
    }

    public static function isConnected(): bool
    {
        return !empty(Setting::get('xero_refresh_token'));
    }

    public static function disconnect(): void
    {
        foreach (['xero_access_token','xero_refresh_token','xero_token_expires_at','xero_tenant_id','xero_tenant_name','xero_oauth_state'] as $key) {
            Setting::set($key, '');
        }
    }

    // ── API calls ─────────────────────────────────────────────────────────────

    public static function getInvoices(int $page = 1): array
    {
        return self::apiGet('/Invoices', [
            'Type'   => 'ACCREC',
            'Status' => 'AUTHORISED,PAID,VOIDED',
            'page'   => $page,
        ])['Invoices'] ?? [];
    }

    public static function getAllInvoices(): array
    {
        $all  = [];
        $page = 1;
        do {
            $batch = self::getInvoices($page);
            $all   = array_merge($all, $batch);
            $page++;
        } while (count($batch) === 100);
        return $all;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private static function getAccessToken(): string
    {
        $expires = (int)Setting::get('xero_token_expires_at');

        if (time() >= $expires - 60) {
            $response = self::tokenRequest([
                'grant_type'    => 'refresh_token',
                'refresh_token' => Setting::get('xero_refresh_token'),
            ]);
            if (!empty($response['access_token'])) {
                self::storeTokens($response);
            }
        }

        return Setting::get('xero_access_token');
    }

    private static function apiGet(string $path, array $params = []): array
    {
        $url = self::API_BASE . $path;
        if ($params) $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::getAccessToken(),
                'Xero-tenant-id: '       . Setting::get('xero_tenant_id'),
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        curl_close($ch);

        return json_decode($body, true) ?? [];
    }

    private static function getTenants(string $accessToken): array
    {
        $ch = curl_init('https://api.xero.com/connections');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken, 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return json_decode($body, true) ?? [];
    }

    private static function tokenRequest(array $params): array
    {
        $clientId     = Setting::get('xero_client_id');
        $clientSecret = Setting::get('xero_client_secret');

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_USERPWD        => $clientId . ':' . $clientSecret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $body = curl_exec($ch);
        curl_close($ch);

        return json_decode($body, true) ?? [];
    }

    private static function storeTokens(array $response): void
    {
        Setting::set('xero_access_token',    $response['access_token']);
        Setting::set('xero_refresh_token',   $response['refresh_token'] ?? Setting::get('xero_refresh_token'));
        Setting::set('xero_token_expires_at', (string)(time() + (int)($response['expires_in'] ?? 1800)));
    }
}
