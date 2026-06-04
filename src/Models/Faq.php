<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Faq
{
    public static function all(bool $activeOnly = false): array
    {
        try {
            $where = $activeOnly ? 'WHERE is_active = 1' : '';
            return DB::fetchAll("SELECT * FROM faqs {$where} ORDER BY sort_order ASC, id ASC");
        } catch (\Exception) {
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM faqs WHERE id = ?', [$id]);
    }

    public static function create(string $question, string $answer, int $sortOrder = 0): int
    {
        return DB::insert(
            'INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)',
            [$question, $answer, $sortOrder]
        );
    }

    public static function update(int $id, string $question, string $answer, int $sortOrder, bool $active): void
    {
        DB::execute(
            'UPDATE faqs SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?',
            [$question, $answer, $sortOrder, $active ? 1 : 0, $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM faqs WHERE id = ?', [$id]);
    }

    public static function nextOrder(): int
    {
        $row = DB::fetchOne('SELECT COALESCE(MAX(sort_order), 0) + 10 as next FROM faqs');
        return (int)($row['next'] ?? 10);
    }
}
