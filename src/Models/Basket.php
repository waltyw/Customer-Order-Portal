<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Basket
{
    public static function getOrCreate(int $userId): array
    {
        $basket = DB::fetchOne('SELECT * FROM baskets WHERE user_id = ?', [$userId]);
        if (!$basket) {
            $id = DB::insert('INSERT INTO baskets (user_id) VALUES (?)', [$userId]);
            $basket = DB::fetchOne('SELECT * FROM baskets WHERE id = ?', [$id]);
        }
        return $basket;
    }

    public static function items(int $basketId, int $tierId = 0): array
    {
        $priceSelect = 'NULL AS tier_price';
        $priceJoin   = '';
        if ($tierId > 0) {
            $priceSelect = 'pp.price AS tier_price';
            $priceJoin   = 'LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.pricing_tier_id = ' . (int)$tierId;
        }

        return DB::fetchAll(
            "SELECT bi.id, bi.quantity, bi.product_id,
                    p.name, p.sku, p.stock, p.is_active,
                    pi.filename AS primary_image, {$priceSelect}
             FROM basket_items bi
             JOIN products p ON p.id = bi.product_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             {$priceJoin}
             WHERE bi.basket_id = ?
             ORDER BY bi.created_at ASC",
            [$basketId]
        );
    }

    public static function itemCount(int $userId): int
    {
        $basket = DB::fetchOne('SELECT id FROM baskets WHERE user_id = ?', [$userId]);
        if (!$basket) return 0;
        $row = DB::fetchOne(
            'SELECT COALESCE(SUM(quantity), 0) AS cnt FROM basket_items WHERE basket_id = ?',
            [(int)$basket['id']]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public static function addItem(int $basketId, int $productId, int $quantity = 1): void
    {
        DB::execute(
            'INSERT INTO basket_items (basket_id, product_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)',
            [$basketId, $productId, $quantity]
        );
        DB::execute('UPDATE baskets SET updated_at = NOW() WHERE id = ?', [$basketId]);
    }

    public static function setQuantity(int $basketId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            self::removeItem($basketId, $productId);
            return;
        }
        DB::execute(
            'UPDATE basket_items SET quantity = ? WHERE basket_id = ? AND product_id = ?',
            [$quantity, $basketId, $productId]
        );
        DB::execute('UPDATE baskets SET updated_at = NOW() WHERE id = ?', [$basketId]);
    }

    public static function removeItem(int $basketId, int $productId): void
    {
        DB::execute(
            'DELETE FROM basket_items WHERE basket_id = ? AND product_id = ?',
            [$basketId, $productId]
        );
        DB::execute('UPDATE baskets SET updated_at = NOW() WHERE id = ?', [$basketId]);
    }

    public static function clear(int $basketId): void
    {
        DB::execute('DELETE FROM basket_items WHERE basket_id = ?', [$basketId]);
        DB::execute('UPDATE baskets SET updated_at = NOW() WHERE id = ?', [$basketId]);
    }

    public static function totals(array $items, int $tierId = 0): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $price     = (float)($item['tier_price'] ?? 0);
            $subtotal += $price * (int)$item['quantity'];
        }
        return ['subtotal' => round($subtotal, 2)];
    }
}
