<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class DeliveryRule
{
    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT dr.*, pt.name AS tier_name
             FROM delivery_rules dr
             LEFT JOIN pricing_tiers pt ON pt.id = dr.pricing_tier_id
             ORDER BY dr.sort_order ASC, dr.id ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM delivery_rules WHERE id = ?', [$id]);
    }

    public static function calculate(float $subtotal, int $tierId = 0): float
    {
        // Threshold rules take priority
        $threshold = DB::fetchOne(
            'SELECT * FROM delivery_rules WHERE type = "threshold" AND is_active = 1
             ORDER BY sort_order ASC LIMIT 1'
        );
        if ($threshold && $subtotal >= (float)$threshold['free_threshold']) {
            return 0.0;
        }

        // Tier-specific rule
        if ($tierId > 0) {
            $tierRule = DB::fetchOne(
                'SELECT * FROM delivery_rules WHERE type = "tier" AND pricing_tier_id = ? AND is_active = 1 LIMIT 1',
                [$tierId]
            );
            if ($tierRule) {
                return (float)$tierRule['flat_rate'];
            }
        }

        // Default flat rate
        $flat = DB::fetchOne(
            'SELECT * FROM delivery_rules WHERE type = "flat" AND is_active = 1 ORDER BY sort_order ASC LIMIT 1'
        );
        return $flat ? (float)$flat['flat_rate'] : 0.0;
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO delivery_rules (name, type, pricing_tier_id, flat_rate, free_threshold, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['type'],
                $data['pricing_tier_id'] ?: null,
                isset($data['flat_rate'])      ? (float)$data['flat_rate']      : null,
                isset($data['free_threshold']) ? (float)$data['free_threshold'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::execute(
            'UPDATE delivery_rules SET name = ?, type = ?, pricing_tier_id = ?, flat_rate = ?, free_threshold = ?, is_active = ?, sort_order = ? WHERE id = ?',
            [
                $data['name'],
                $data['type'],
                $data['pricing_tier_id'] ?: null,
                isset($data['flat_rate'])      ? (float)$data['flat_rate']      : null,
                isset($data['free_threshold']) ? (float)$data['free_threshold'] : null,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                (int)($data['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM delivery_rules WHERE id = ?', [$id]);
    }
}
