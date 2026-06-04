<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class ShopOrder
{
    public static function find(int $id): ?array
    {
        $order = DB::fetchOne(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                    pt.name AS tier_name
             FROM shop_orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN pricing_tiers pt ON pt.id = o.pricing_tier_id
             WHERE o.id = ?',
            [$id]
        );
        if ($order) {
            $order['items'] = self::items((int)$order['id']);
        }
        return $order;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $order = DB::fetchOne(
            'SELECT o.*, pt.name AS tier_name
             FROM shop_orders o
             LEFT JOIN pricing_tiers pt ON pt.id = o.pricing_tier_id
             WHERE o.id = ? AND o.user_id = ?',
            [$id, $userId]
        );
        if ($order) {
            $order['items'] = self::items((int)$order['id']);
        }
        return $order;
    }

    public static function findByRef(string $ref): ?array
    {
        return DB::fetchOne('SELECT * FROM shop_orders WHERE reference = ?', [$ref]);
    }

    public static function findByStripeSession(string $sessionId): ?array
    {
        return DB::fetchOne('SELECT * FROM shop_orders WHERE stripe_session_id = ?', [$sessionId]);
    }

    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT o.*, pt.name AS tier_name
             FROM shop_orders o
             LEFT JOIN pricing_tiers pt ON pt.id = o.pricing_tier_id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC',
            [$userId]
        );
    }

    public static function all(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $where[]  = 'o.user_id = ?';
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(o.reference LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        return DB::fetchAll(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, pt.name AS tier_name
             FROM shop_orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN pricing_tiers pt ON pt.id = o.pricing_tier_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY o.created_at DESC',
            $params
        );
    }

    public static function items(int $orderId): array
    {
        return DB::fetchAll(
            'SELECT * FROM shop_order_items WHERE order_id = ? ORDER BY id ASC',
            [$orderId]
        );
    }

    public static function create(array $data): int
    {
        $prefix = Setting::get('shop_order_prefix') ?: 'ORD';
        $ref    = $prefix . '-' . strtoupper(substr(uniqid(), -6));

        return DB::insert(
            'INSERT INTO shop_orders
             (reference, user_id, pricing_tier_id, status, checkout_method, po_number, po_notes,
              stripe_session_id, subtotal, delivery_charge, vat_amount, total,
              billing_name, billing_company, billing_line1, billing_line2, billing_city, billing_county, billing_postcode, billing_country,
              shipping_name, shipping_company, shipping_line1, shipping_line2, shipping_city, shipping_county, shipping_postcode, shipping_country,
              notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $ref,
                $data['user_id'],
                $data['pricing_tier_id'] ?? null,
                $data['status'] ?? 'pending',
                $data['checkout_method'],
                $data['po_number'] ?? null,
                $data['po_notes'] ?? null,
                $data['stripe_session_id'] ?? null,
                $data['subtotal'],
                $data['delivery_charge'],
                $data['vat_amount'],
                $data['total'],
                $data['billing_name'] ?? null,
                $data['billing_company'] ?? null,
                $data['billing_line1'] ?? null,
                $data['billing_line2'] ?? null,
                $data['billing_city'] ?? null,
                $data['billing_county'] ?? null,
                $data['billing_postcode'] ?? null,
                $data['billing_country'] ?? null,
                $data['shipping_name'] ?? null,
                $data['shipping_company'] ?? null,
                $data['shipping_line1'] ?? null,
                $data['shipping_line2'] ?? null,
                $data['shipping_city'] ?? null,
                $data['shipping_county'] ?? null,
                $data['shipping_postcode'] ?? null,
                $data['shipping_country'] ?? null,
                $data['notes'] ?? null,
            ]
        );
    }

    public static function addItem(int $orderId, array $item): void
    {
        DB::execute(
            'INSERT INTO shop_order_items (order_id, product_id, sku, name, quantity, unit_price, total)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $orderId,
                $item['product_id'] ?? null,
                $item['sku'],
                $item['name'],
                (int)$item['quantity'],
                (float)$item['unit_price'],
                (float)$item['total'],
            ]
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::execute('UPDATE shop_orders SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function updateStripePayment(int $id, string $paymentIntent, string $status): void
    {
        DB::execute(
            'UPDATE shop_orders SET stripe_payment_intent = ?, status = ? WHERE id = ?',
            [$paymentIntent, $status, $id]
        );
    }

    public static function counts(): array
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending'    THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'confirmed'  THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN status = 'dispatched' THEN 1 ELSE 0 END) AS dispatched
             FROM shop_orders"
        );
        return $row ?? ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'processing' => 0, 'dispatched' => 0];
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'pending'    => 'Pending',
            'confirmed'  => 'Confirmed',
            'processing' => 'Processing',
            'dispatched' => 'Dispatched',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
            default      => ucfirst($status),
        };
    }

    public static function statusClass(string $status): string
    {
        return match($status) {
            'pending'    => 'warning',
            'confirmed'  => 'info',
            'processing' => 'primary',
            'dispatched' => 'purple',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }
}
