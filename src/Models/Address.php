<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Address
{
    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default_billing DESC, is_default_shipping DESC, id ASC',
            [$userId]
        );
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM customer_addresses WHERE id = ?', [$id]);
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return DB::fetchOne('SELECT * FROM customer_addresses WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function defaultBilling(int $userId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM customer_addresses WHERE user_id = ? AND is_default_billing = 1 LIMIT 1',
            [$userId]
        ) ?? DB::fetchOne(
            'SELECT * FROM customer_addresses WHERE user_id = ? LIMIT 1',
            [$userId]
        );
    }

    public static function defaultShipping(int $userId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM customer_addresses WHERE user_id = ? AND is_default_shipping = 1 LIMIT 1',
            [$userId]
        ) ?? DB::fetchOne(
            'SELECT * FROM customer_addresses WHERE user_id = ? LIMIT 1',
            [$userId]
        );
    }

    public static function create(int $userId, array $data): int
    {
        if (!empty($data['is_default_billing'])) {
            DB::execute('UPDATE customer_addresses SET is_default_billing = 0 WHERE user_id = ?', [$userId]);
        }
        if (!empty($data['is_default_shipping'])) {
            DB::execute('UPDATE customer_addresses SET is_default_shipping = 0 WHERE user_id = ?', [$userId]);
        }
        return DB::insert(
            'INSERT INTO customer_addresses (user_id, type, label, company, line1, line2, city, county, postcode, country, is_default_billing, is_default_shipping)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $data['type'] ?? 'both',
                $data['label'] ?? null,
                $data['company'] ?? null,
                $data['line1'],
                $data['line2'] ?? null,
                $data['city'],
                $data['county'] ?? null,
                $data['postcode'],
                $data['country'] ?? 'United Kingdom',
                isset($data['is_default_billing']) ? (int)$data['is_default_billing'] : 0,
                isset($data['is_default_shipping']) ? (int)$data['is_default_shipping'] : 0,
            ]
        );
    }

    public static function update(int $id, int $userId, array $data): void
    {
        if (!empty($data['is_default_billing'])) {
            DB::execute('UPDATE customer_addresses SET is_default_billing = 0 WHERE user_id = ?', [$userId]);
        }
        if (!empty($data['is_default_shipping'])) {
            DB::execute('UPDATE customer_addresses SET is_default_shipping = 0 WHERE user_id = ?', [$userId]);
        }
        DB::execute(
            'UPDATE customer_addresses SET type = ?, label = ?, company = ?, line1 = ?, line2 = ?, city = ?, county = ?, postcode = ?, country = ?, is_default_billing = ?, is_default_shipping = ?
             WHERE id = ? AND user_id = ?',
            [
                $data['type'] ?? 'both',
                $data['label'] ?? null,
                $data['company'] ?? null,
                $data['line1'],
                $data['line2'] ?? null,
                $data['city'],
                $data['county'] ?? null,
                $data['postcode'],
                $data['country'] ?? 'United Kingdom',
                isset($data['is_default_billing']) ? (int)$data['is_default_billing'] : 0,
                isset($data['is_default_shipping']) ? (int)$data['is_default_shipping'] : 0,
                $id,
                $userId,
            ]
        );
    }

    public static function delete(int $id, int $userId): void
    {
        DB::execute('DELETE FROM customer_addresses WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function format(array $addr): string
    {
        $parts = array_filter([
            $addr['company'] ?? '',
            $addr['line1']   ?? '',
            $addr['line2']   ?? '',
            $addr['city']    ?? '',
            $addr['county']  ?? '',
            $addr['postcode'] ?? '',
            $addr['country']  ?? '',
        ]);
        return implode(', ', $parts);
    }
}
