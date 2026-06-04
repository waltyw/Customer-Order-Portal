<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Website
{
    public static function forUser(int $userId): array
    {
        try {
            return DB::fetchAll(
                'SELECT * FROM customer_websites WHERE user_id = ? ORDER BY sort_order ASC, id ASC',
                [$userId]
            );
        } catch (\Exception) {
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM customer_websites WHERE id = ?', [$id]);
    }

    public static function add(int $userId, string $url, string $label = ''): int
    {
        $url = self::normalise($url);
        $order = DB::fetchOne(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 as next FROM customer_websites WHERE user_id = ?',
            [$userId]
        );
        return DB::insert(
            'INSERT INTO customer_websites (user_id, url, label, sort_order) VALUES (?, ?, ?, ?)',
            [$userId, $url, $label ?: null, (int)($order['next'] ?? 1)]
        );
    }

    public static function remove(int $id, int $userId): void
    {
        // Ensure user can only remove their own websites
        DB::execute('DELETE FROM customer_websites WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function forDropdown(int $userId): array
    {
        $sites = self::forUser($userId);
        $options = ['' => '— Not applicable / General enquiry —'];
        foreach ($sites as $site) {
            $label = $site['label'] ? $site['label'] . ' (' . $site['url'] . ')' : $site['url'];
            $options[$site['url']] = $label;
        }
        return $options;
    }

    private static function normalise(string $url): string
    {
        $url = trim($url);
        if (!$url) return $url;
        // Strip any partial protocol the user may have typed (http:, https:, http:/, etc.)
        $url = preg_replace('#^https?:/*#i', '', $url);
        return 'https://' . $url;
    }
}
