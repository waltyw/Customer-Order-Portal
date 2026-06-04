<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Category
{
    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT c.*, p.name AS parent_name
             FROM product_categories c
             LEFT JOIN product_categories p ON p.id = c.parent_id
             ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    public static function active(): array
    {
        return DB::fetchAll(
            'SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
    }

    public static function topLevel(): array
    {
        return DB::fetchAll(
            'SELECT * FROM product_categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
    }

    public static function children(int $parentId): array
    {
        return DB::fetchAll(
            'SELECT * FROM product_categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC',
            [$parentId]
        );
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM product_categories WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::fetchOne('SELECT * FROM product_categories WHERE slug = ?', [$slug]);
    }

    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO product_categories (parent_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['parent_id'] ?: null,
                $data['name'],
                self::slugify($data['slug'] ?: $data['name']),
                $data['description'] ?? null,
                (int)($data['sort_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        DB::execute(
            'UPDATE product_categories SET parent_id = ?, name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [
                $data['parent_id'] ?: null,
                $data['name'],
                self::slugify($data['slug'] ?: $data['name']),
                $data['description'] ?? null,
                (int)($data['sort_order'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM product_categories WHERE id = ?', [$id]);
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
