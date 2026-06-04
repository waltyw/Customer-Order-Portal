<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Setting
{
    private static ?array $cache = null;

    private static array $defaults = [
        'app_name'       => 'Customer Portal',
        'primary_color'  => '#2563eb',
        'primary_dark'   => '#1d4ed8',
        'sidebar_bg'     => '#0f172a',
        'sidebar_text'   => '#94a3b8',
        'sidebar_active' => '#2563eb',
        'body_bg'        => '#f8fafc',
        'text_color'     => '#1e293b',
        'text_muted'     => '#64748b',
        'card_bg'        => '#ffffff',
        'support_email'        => '',
        'logo_ext'             => 'png',
        'favicon_ext'          => 'png',
        'currency_symbol'      => '£',
        'font_family'          => 'Inter',
        'invoices_enabled'     => '1',
        'logo_link_url'        => '',
        'menu_links'           => '[]',
        'xero_client_id'       => '',
        'xero_client_secret'   => '',
        'xero_redirect_uri'    => '',
        'xero_access_token'    => '',
        'xero_refresh_token'   => '',
        'xero_token_expires_at'=> '0',
        'xero_tenant_id'       => '',
        'xero_tenant_name'     => '',
        'xero_oauth_state'     => '',
        'xero_last_sync'       => '',
    ];

    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        try {
            $rows = DB::fetchAll('SELECT `key`, `value` FROM settings');
            self::$cache = array_merge(
                self::$defaults,
                array_column($rows, 'value', 'key')
            );
        } catch (\Exception) {
            // Settings table doesn't exist yet — return defaults
            self::$cache = self::$defaults;
        }

        return self::$cache;
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? self::$defaults[$key] ?? '';
    }

    public static function set(string $key, string $value): void
    {
        DB::execute(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?',
            [$key, $value, $value]
        );
        self::$cache = null;
    }

    public static function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::$defaults)) {
                self::set($key, (string)$value);
            }
        }
    }

    public static function cssVars(): string
    {
        $s    = self::all();
        $font = $s['font_family'] ?: 'Inter';
        $fontStack = "'{$font}',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif";
        return sprintf(
            '<style>:root{--primary:%s;--primary-dark:%s;--sidebar-bg:%s;--sidebar-text:%s;--sidebar-active:%s;--surface:%s;--text:%s;--text-muted:%s;--card-bg:%s;}body,input,textarea,select,button{font-family:%s;}</style>',
            htmlspecialchars($s['primary_color']),
            htmlspecialchars($s['primary_dark']),
            htmlspecialchars($s['sidebar_bg']),
            htmlspecialchars($s['sidebar_text']),
            htmlspecialchars($s['sidebar_active']),
            htmlspecialchars($s['body_bg']),
            htmlspecialchars($s['text_color']),
            htmlspecialchars($s['text_muted']),
            htmlspecialchars($s['card_bg']),
            $fontStack
        );
    }

    public static function menuLinks(): array
    {
        $raw = self::get('menu_links') ?: '[]';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function googleFontUrl(): string
    {
        $font = self::get('font_family') ?: 'Inter';
        if ($font === 'Inter') return ''; // Already loaded from Google Fonts in layout
        return 'https://fonts.googleapis.com/css2?family='
            . urlencode($font) . ':wght@400;500;600;700&display=swap';
    }
}
