<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Payment
{
    public static function create(array $data): int
    {
        return DB::insert(
            'INSERT INTO payments (user_id, invoice_id, stripe_session_id, amount, currency, method, status, reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['invoice_id'] ?? null,
                $data['stripe_session_id'] ?? null,
                $data['amount'],
                $data['currency'] ?? 'GBP',
                $data['method'] ?? 'stripe',
                $data['status'] ?? 'pending',
                $data['reference'] ?? null,
            ]
        );
    }

    public static function findBySession(string $sessionId): ?array
    {
        return DB::fetchOne('SELECT * FROM payments WHERE stripe_session_id = ?', [$sessionId]);
    }

    public static function complete(int $id, string $paymentIntentId): void
    {
        DB::execute(
            'UPDATE payments SET status = ?, stripe_payment_intent_id = ? WHERE id = ?',
            ['completed', $paymentIntentId, $id]
        );
    }

    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT p.*, i.invoice_number FROM payments p LEFT JOIN invoices i ON p.invoice_id = i.id WHERE p.user_id = ? ORDER BY p.created_at DESC',
            [$userId]
        );
    }

    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT p.*, u.name as customer_name, u.email as customer_email, i.invoice_number FROM payments p JOIN users u ON p.user_id = u.id LEFT JOIN invoices i ON p.invoice_id = i.id ORDER BY p.created_at DESC'
        );
    }
}
