<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class User
{
    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::fetchOne('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT id, email, name, company, phone, role, is_active, created_at FROM users ORDER BY name ASC'
        );
    }

    public static function customers(): array
    {
        // Detect which columns exist so this works before and after migrations
        $cols = DB::fetchAll(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
        );
        $colNames = array_column($cols, 'COLUMN_NAME');

        $billingPostcode = in_array('billing_postcode', $colNames)
            ? 'u.billing_postcode'
            : (in_array('postcode', $colNames) ? 'u.postcode' : "''");
        $billingCity = in_array('billing_city', $colNames) ? 'u.billing_city' : "''";
        $branchNum   = in_array('branch_number', $colNames) ? 'u.branch_number' : "''";
        $tierId      = in_array('pricing_tier_id', $colNames)
            ? 'LEFT JOIN pricing_tiers pt ON pt.id = u.pricing_tier_id'
            : '';
        $tierName    = $tierId ? 'pt.name AS tier_name' : "'' AS tier_name";

        return DB::fetchAll(
            "SELECT u.id, u.email, u.name, u.company, u.phone, {$branchNum} AS branch_number,
                    {$billingPostcode} AS billing_postcode, {$billingCity} AS billing_city,
                    u.is_active, u.created_at, {$tierName}
             FROM users u
             {$tierId}
             WHERE u.role = 'customer' ORDER BY u.name ASC"
        );
    }

    public static function create(array $data): int
    {
        $hash    = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $hasCols = self::hasAddressCols();

        $cols   = 'email, password_hash, name, company, phone, role';
        $params = [
            strtolower($data['email']), $hash, $data['name'],
            $data['company'] ?? null, $data['phone'] ?? null,
            $data['role'] ?? 'customer',
        ];

        if (in_array('branch_number', $hasCols)) {
            $cols    .= ', branch_number';
            $params[] = $data['branch_number'] ?? null;
        }

        if (in_array('billing_address_1', $hasCols)) {
            $cols    .= ', billing_address_1, billing_address_2, billing_city, billing_county, billing_postcode, billing_country';
            $params[] = $data['billing_address_1'] ?? null;
            $params[] = $data['billing_address_2'] ?? null;
            $params[] = $data['billing_city']      ?? null;
            $params[] = $data['billing_county']    ?? null;
            $params[] = $data['billing_postcode']  ?? null;
            $params[] = $data['billing_country']   ?? 'United Kingdom';
        } elseif (in_array('postcode', $hasCols)) {
            $cols    .= ', postcode';
            $params[] = $data['billing_postcode'] ?? $data['postcode'] ?? null;
        }

        if (in_array('delivery_same_as_billing', $hasCols)) {
            $same     = isset($data['delivery_same_as_billing']) ? (int)$data['delivery_same_as_billing'] : 1;
            $cols    .= ', delivery_same_as_billing, delivery_address_1, delivery_address_2, delivery_city, delivery_county, delivery_postcode, delivery_country';
            $params[] = $same;
            $params[] = $same ? null : ($data['delivery_address_1'] ?? null);
            $params[] = $same ? null : ($data['delivery_address_2'] ?? null);
            $params[] = $same ? null : ($data['delivery_city']      ?? null);
            $params[] = $same ? null : ($data['delivery_county']    ?? null);
            $params[] = $same ? null : ($data['delivery_postcode']  ?? null);
            $params[] = $same ? null : ($data['delivery_country']   ?? 'United Kingdom');
        }

        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        return DB::insert("INSERT INTO users ({$cols}) VALUES ({$placeholders})", $params);
    }

    public static function update(int $id, array $data): void
    {
        $hasCols = self::hasAddressCols();

        $sets   = ['name = ?', 'company = ?', 'phone = ?', 'is_active = ?', 'show_invoices = ?'];
        $params = [
            $data['name'],
            $data['company'] ?? null,
            $data['phone']   ?? null,
            $data['is_active'] ?? 1,
            isset($data['show_invoices']) ? (int)$data['show_invoices'] : 1,
        ];

        if (in_array('cc_email_1', $hasCols)) {
            $cc1 = strtolower(trim($data['cc_email_1'] ?? ''));
            $sets[]   = 'cc_email_1 = ?';
            $params[] = ($cc1 && filter_var($cc1, FILTER_VALIDATE_EMAIL)) ? $cc1 : null;
        }

        if (in_array('cc_email_2', $hasCols)) {
            $cc2 = strtolower(trim($data['cc_email_2'] ?? ''));
            $sets[]   = 'cc_email_2 = ?';
            $params[] = ($cc2 && filter_var($cc2, FILTER_VALIDATE_EMAIL)) ? $cc2 : null;
        }

        if (in_array('branch_number', $hasCols)) {
            $sets[]   = 'branch_number = ?';
            $params[] = $data['branch_number'] ?? null;
        }

        if (in_array('billing_address_1', $hasCols)) {
            array_push($sets,
                'billing_address_1 = ?', 'billing_address_2 = ?',
                'billing_city = ?',      'billing_county = ?',
                'billing_postcode = ?',  'billing_country = ?'
            );
            array_push($params,
                $data['billing_address_1'] ?? null,
                $data['billing_address_2'] ?? null,
                $data['billing_city']      ?? null,
                $data['billing_county']    ?? null,
                $data['billing_postcode']  ?? null,
                $data['billing_country']   ?? 'United Kingdom'
            );
        } elseif (in_array('postcode', $hasCols)) {
            $sets[]   = 'postcode = ?';
            $params[] = $data['billing_postcode'] ?? $data['postcode'] ?? null;
        }

        if (in_array('delivery_same_as_billing', $hasCols)) {
            $same = isset($data['delivery_same_as_billing']) ? (int)$data['delivery_same_as_billing'] : 1;
            array_push($sets,
                'delivery_same_as_billing = ?',
                'delivery_address_1 = ?', 'delivery_address_2 = ?',
                'delivery_city = ?',      'delivery_county = ?',
                'delivery_postcode = ?',  'delivery_country = ?'
            );
            array_push($params,
                $same,
                $same ? null : ($data['delivery_address_1'] ?? null),
                $same ? null : ($data['delivery_address_2'] ?? null),
                $same ? null : ($data['delivery_city']      ?? null),
                $same ? null : ($data['delivery_county']    ?? null),
                $same ? null : ($data['delivery_postcode']  ?? null),
                $same ? null : ($data['delivery_country']   ?? 'United Kingdom')
            );
        }

        $params[] = $id;
        DB::execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    private static function hasAddressCols(): array
    {
        static $cache = null;
        if ($cache === null) {
            $rows  = DB::fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'");
            $cache = array_column($rows, 'COLUMN_NAME');
        }
        return $cache;
    }

    public static function mailServer(?string $websiteUrl): string
    {
        if (!$websiteUrl) return '';
        $host = parse_url($websiteUrl, PHP_URL_HOST) ?? $websiteUrl;
        $host = preg_replace('/^www\./', '', $host);
        return 'mail.' . $host;
    }

    private static function normaliseUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if ($url && !str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return $url ?: null;
    }

    public static function toggleActive(int $id): void
    {
        DB::execute('UPDATE users SET is_active = NOT is_active WHERE id = ?', [$id]);
    }

    public static function changePassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        DB::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public static function stats(int $id): array
    {
        $tickets  = DB::fetchOne('SELECT COUNT(*) as cnt FROM tickets WHERE user_id = ?', [$id]);
        $invoices = DB::fetchOne('SELECT COUNT(*) as cnt, COALESCE(SUM(amount_due),0) as outstanding FROM invoices WHERE user_id = ? AND status NOT IN (?,?)', [$id, 'paid', 'voided']);
        return [
            'ticket_count'   => (int)($tickets['cnt'] ?? 0),
            'invoice_count'  => (int)($invoices['cnt'] ?? 0),
            'amount_outstanding' => (float)($invoices['outstanding'] ?? 0),
        ];
    }
}
