<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class PricingTier
{
    public static function all(): array
    {
        return DB::fetchAll('SELECT * FROM pricing_tiers ORDER BY sort_order ASC, id ASC');
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM pricing_tiers WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO pricing_tiers (name, description, sort_order) VALUES (?, ?, ?)',
            [$data['name'], $data['description'] ?? null, (int)($data['sort_order'] ?? 0)]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::execute(
            'UPDATE pricing_tiers SET name = ?, description = ?, sort_order = ? WHERE id = ?',
            [$data['name'], $data['description'] ?? null, (int)($data['sort_order'] ?? 0), $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM pricing_tiers WHERE id = ?', [$id]);
    }
}
