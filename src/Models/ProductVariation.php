<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class ProductVariation
{
    public static function forProduct(int $productId, int $tierId = 0): array
    {
        $priceSelect = 'NULL AS tier_price';
        $priceJoin   = '';
        if ($tierId > 0) {
            $priceSelect = 'vp.price AS tier_price';
            $priceJoin   = 'LEFT JOIN variation_prices vp ON vp.variation_id = v.id AND vp.pricing_tier_id = ' . (int)$tierId;
        }

        $variations = DB::fetchAll(
            "SELECT v.*, {$priceSelect}
             FROM product_variations v
             {$priceJoin}
             WHERE v.product_id = ?
             ORDER BY v.sort_order ASC, v.id ASC",
            [$productId]
        );

        foreach ($variations as &$var) {
            $var['attributes'] = self::attributes((int)$var['id']);
        }

        return $variations;
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM product_variations WHERE id = ?', [$id]);
    }

    public static function findBySku(string $sku): ?array
    {
        return DB::fetchOne('SELECT * FROM product_variations WHERE sku = ?', [$sku]);
    }

    public static function attributes(int $variationId): array
    {
        return DB::fetchAll(
            'SELECT pva.*, pa.name AS attribute_name, pat.name AS term_name
             FROM product_variation_attributes pva
             JOIN product_attributes pa ON pa.id = pva.attribute_id
             JOIN product_attribute_terms pat ON pat.id = pva.term_id
             WHERE pva.variation_id = ?',
            [$variationId]
        );
    }

    public static function prices(int $variationId): array
    {
        $rows = DB::fetchAll(
            'SELECT vp.*, pt.name AS tier_name
             FROM variation_prices vp
             JOIN pricing_tiers pt ON pt.id = vp.pricing_tier_id
             WHERE vp.variation_id = ?
             ORDER BY pt.sort_order ASC',
            [$variationId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['pricing_tier_id']] = $r;
        }
        return $map;
    }

    public static function create(int $productId, array $data): int
    {
        return DB::insert(
            'INSERT INTO product_variations (product_id, sku, stock, image, is_active, sort_order, woo_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $productId,
                $data['sku'],
                (int)($data['stock'] ?? 0),
                $data['image'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
                $data['woo_id'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::execute(
            'UPDATE product_variations SET sku = ?, stock = ?, image = ?, is_active = ?, sort_order = ? WHERE id = ?',
            [
                $data['sku'],
                (int)($data['stock'] ?? 0),
                $data['image'] ?? null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public static function setPrice(int $variationId, int $tierId, float $price): void
    {
        DB::execute(
            'INSERT INTO variation_prices (variation_id, pricing_tier_id, price) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE price = VALUES(price)',
            [$variationId, $tierId, $price]
        );
    }

    public static function setAttributeTerm(int $variationId, int $attributeId, int $termId): void
    {
        DB::execute(
            'INSERT INTO product_variation_attributes (variation_id, attribute_id, term_id) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE term_id = VALUES(term_id)',
            [$variationId, $attributeId, $termId]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM product_variations WHERE id = ?', [$id]);
    }

    // ── Attribute helpers ─────────────────────────────────────────────────────

    public static function findOrCreateAttribute(string $name): int
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        $existing = DB::fetchOne('SELECT id FROM product_attributes WHERE slug = ?', [$slug]);
        if ($existing) return (int)$existing['id'];

        return DB::insert(
            'INSERT INTO product_attributes (name, slug) VALUES (?, ?)',
            [trim($name), $slug]
        );
    }

    public static function findOrCreateTerm(int $attributeId, string $name): int
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        $existing = DB::fetchOne(
            'SELECT id FROM product_attribute_terms WHERE attribute_id = ? AND slug = ?',
            [$attributeId, $slug]
        );
        if ($existing) return (int)$existing['id'];

        return DB::insert(
            'INSERT INTO product_attribute_terms (attribute_id, name, slug) VALUES (?, ?, ?)',
            [$attributeId, trim($name), $slug]
        );
    }

    public static function mapAttributeToProduct(int $productId, int $attributeId, array $termIds, bool $isVariation): void
    {
        DB::execute(
            'INSERT INTO product_attribute_maps (product_id, attribute_id, term_ids, is_variation)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE term_ids = VALUES(term_ids), is_variation = VALUES(is_variation)',
            [$productId, $attributeId, implode(',', $termIds), (int)$isVariation]
        );
    }

    public static function attributesForProduct(int $productId): array
    {
        return DB::fetchAll(
            'SELECT pam.*, pa.name AS attr_name, pa.slug AS attr_slug
             FROM product_attribute_maps pam
             JOIN product_attributes pa ON pa.id = pam.attribute_id
             WHERE pam.product_id = ?
             ORDER BY pam.sort_order ASC',
            [$productId]
        );
    }
}
