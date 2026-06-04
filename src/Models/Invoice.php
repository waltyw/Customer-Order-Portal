<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Invoice
{
    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT i.*, u.name as customer_name, u.email as customer_email FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ?',
            [$id]
        );
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return DB::fetchOne('SELECT * FROM invoices WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT i.*, u.name as customer_name, u.email as customer_email FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC'
        );
    }

    public static function create(array $data): int
    {
        $lineItems = $data['line_items'] ?? [];
        $subtotal = 0;
        foreach ($lineItems as $item) {
            $subtotal += (float)$item['qty'] * (float)$item['unit_price'];
        }
        $vatRate  = (float)($data['vat_rate'] ?? 20) / 100;
        $vatAmt   = $subtotal * $vatRate;
        $total    = $subtotal + $vatAmt;

        return DB::insert(
            'INSERT INTO invoices (user_id, invoice_number, status, subtotal, vat_amount, total, amount_due, currency, issue_date, due_date, line_items, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['invoice_number'],
                $data['status'] ?? 'authorised',
                round($subtotal, 2),
                round($vatAmt, 2),
                round($total, 2),
                round($total, 2),
                $data['currency'] ?? 'GBP',
                $data['issue_date'] ?? date('Y-m-d'),
                $data['due_date'] ?? null,
                json_encode($lineItems),
                $data['notes'] ?? null,
            ]
        );
    }

    public static function markPaid(int $id): void
    {
        DB::execute(
            'UPDATE invoices SET status = ?, amount_paid = total, amount_due = 0 WHERE id = ?',
            ['paid', $id]
        );
    }

    public static function updateOverdue(): void
    {
        DB::execute(
            "UPDATE invoices SET status = 'overdue' WHERE status = 'authorised' AND due_date < CURDATE() AND amount_due > 0"
        );
    }

    public static function counts(): array
    {
        $rows = DB::fetchAll('SELECT status, COUNT(*) as cnt, COALESCE(SUM(amount_due),0) as total_due FROM invoices GROUP BY status');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = ['count' => (int)$row['cnt'], 'total_due' => (float)$row['total_due']];
        }
        return $result;
    }

    public static function nextNumber(): string
    {
        $row = DB::fetchOne('SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, \'-\', -1) AS UNSIGNED)) as max_num FROM invoices WHERE invoice_number LIKE ?', ['BBZ-%']);
        $next = ((int)($row['max_num'] ?? 0)) + 1;
        return 'BBZ-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}
