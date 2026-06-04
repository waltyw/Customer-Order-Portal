<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Product
{
    public static function find(int $id): ?array
    {
        $product = DB::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.id = ?',
            [$id]
        );
        if ($product) {
            $product['images'] = self::images($id);
            $product['prices'] = self::prices($id);
        }
        return $product;
    }

    public static function findBySlug(string $slug): ?array
    {
        $product = DB::fetchOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN product_categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1',
            [$slug]
        );
        if ($product) {
            $product['images'] = self::images((int)$product['id']);
            $product['prices'] = self::prices((int)$product['id']);
        }
        return $product;
    }

    public static function findBySku(string $sku): ?array
    {
        return DB::fetchOne('SELECT * FROM products WHERE sku = ?', [$sku]);
    }

    public static function all(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['active_only']) && $filters['active_only']) {
            $where[] = 'p.is_active = 1';
        }

        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN product_categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.sort_order ASC, p.name ASC';

        return DB::fetchAll($sql, $params);
    }

    public static function forCatalogue(array $filters = [], int $tierId = 0): array
    {
        $where  = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $priceJoin  = '';
        $priceSelect = ', NULL AS tier_price';
        if ($tierId > 0) {
            $priceJoin   = 'LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.pricing_tier_id = ' . (int)$tierId;
            $priceSelect = ', pp.price AS tier_price';
        }

        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       pi.filename AS primary_image' . $priceSelect . '
                FROM products p
                LEFT JOIN product_categories c ON c.id = p.category_id
                LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                ' . $priceJoin . '
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.sort_order ASC, p.name ASC';

        return DB::fetchAll($sql, $params);
    }

    public static function images(int $productId): array
    {
        return DB::fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
            [$productId]
        );
    }

    public static function prices(int $productId): array
    {
        $rows = DB::fetchAll(
            'SELECT pp.*, pt.name AS tier_name
             FROM product_prices pp
             JOIN pricing_tiers pt ON pt.id = pp.pricing_tier_id
             WHERE pp.product_id = ?
             ORDER BY pt.sort_order ASC',
            [$productId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['pricing_tier_id']] = $r;
        }
        return $map;
    }

    public static function priceForTier(int $productId, int $tierId): ?float
    {
        $row = DB::fetchOne(
            'SELECT price FROM product_prices WHERE product_id = ? AND pricing_tier_id = ?',
            [$productId, $tierId]
        );
        return $row ? (float)$row['price'] : null;
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO products (type, category_id, sku, name, slug, description, short_description, stock, low_stock_threshold, is_active, sort_order, woo_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['type'] ?? 'simple',
                $data['category_id'] ?: null,
                $data['sku'],
                $data['name'],
                self::slugify($data['slug'] ?: $data['name']),
                $data['description'] ?? null,
                $data['short_description'] ?? null,
                (int)($data['stock'] ?? 0),
                (int)($data['low_stock_threshold'] ?? 5),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
                $data['woo_id'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::execute(
            'UPDATE products SET type = ?, category_id = ?, sku = ?, name = ?, slug = ?, description = ?,
             short_description = ?, stock = ?, low_stock_threshold = ?, is_active = ?, sort_order = ? WHERE id = ?',
            [
                $data['type'] ?? 'simple',
                $data['category_id'] ?: null,
                $data['sku'],
                $data['name'],
                self::slugify($data['slug'] ?: $data['name']),
                $data['description'] ?? null,
                $data['short_description'] ?? null,
                (int)($data['stock'] ?? 0),
                (int)($data['low_stock_threshold'] ?? 5),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public static function updateStock(int $id, int $stock): void
    {
        DB::execute('UPDATE products SET stock = ? WHERE id = ?', [$stock, $id]);
    }

    public static function adjustStock(int $id, int $delta): void
    {
        DB::execute('UPDATE products SET stock = GREATEST(0, stock + ?) WHERE id = ?', [$delta, $id]);
    }

    public static function setPrice(int $productId, int $tierId, float $price): void
    {
        DB::execute(
            'INSERT INTO product_prices (product_id, pricing_tier_id, price) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE price = VALUES(price)',
            [$productId, $tierId, $price]
        );
    }

    public static function addImage(int $productId, string $filename, string $altText = '', bool $isPrimary = false): int
    {
        if ($isPrimary) {
            DB::execute('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$productId]);
        }
        return DB::insert(
            'INSERT INTO product_images (product_id, filename, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?)',
            [
                $productId,
                $filename,
                $altText,
                (int)$isPrimary,
                DB::fetchOne('SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM product_images WHERE product_id = ?', [$productId])['n'] ?? 1,
            ]
        );
    }

    public static function deleteImage(int $imageId): void
    {
        DB::execute('DELETE FROM product_images WHERE id = ?', [$imageId]);
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM products WHERE id = ?', [$id]);
    }

    public static function lowStock(): array
    {
        return DB::fetchAll(
            'SELECT * FROM products WHERE is_active = 1 AND stock <= low_stock_threshold ORDER BY stock ASC'
        );
    }

    public static function counts(): array
    {
        $row = DB::fetchOne(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN stock <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock
             FROM products'
        );
        return $row ?? ['total' => 0, 'active' => 0, 'low_stock' => 0];
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
