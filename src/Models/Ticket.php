<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Ticket
{
    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT t.*, u.name as customer_name, u.email as customer_email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?', [$id]);
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return DB::fetchOne(
            'SELECT * FROM tickets WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC',
            [$userId]
        );
    }

    public static function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 't.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 't.priority = ?';
            $params[] = $filters['priority'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return DB::fetchAll(
            "SELECT t.*, u.name as customer_name, u.email as customer_email
             FROM tickets t JOIN users u ON t.user_id = u.id
             {$whereClause}
             ORDER BY t.updated_at DESC",
            $params
        );
    }

    public static function create(int $userId, array $data): int
    {
        $reference = self::generateReference();
        return DB::insert(
            'INSERT INTO tickets (reference, user_id, subject, priority, category, website_url) VALUES (?, ?, ?, ?, ?, ?)',
            [$reference, $userId, $data['subject'], $data['priority'], $data['category'], $data['website_url'] ?? null]
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::execute('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
    }

    public static function messages(int $ticketId, bool $includeInternal = false): array
    {
        $internalClause = $includeInternal ? '' : 'AND tm.is_internal = 0';
        return DB::fetchAll(
            "SELECT tm.*, u.name as sender_name, u.role as sender_role
             FROM ticket_messages tm JOIN users u ON tm.user_id = u.id
             WHERE tm.ticket_id = ? {$internalClause}
             ORDER BY tm.created_at ASC",
            [$ticketId]
        );
    }

    public static function addMessage(int $ticketId, int $userId, string $message, bool $isInternal = false): int
    {
        $msgId = DB::insert(
            'INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal) VALUES (?, ?, ?, ?)',
            [$ticketId, $userId, $message, $isInternal ? 1 : 0]
        );
        DB::execute('UPDATE tickets SET updated_at = NOW() WHERE id = ?', [$ticketId]);
        return $msgId;
    }

    public static function saveAttachment(int $messageId, array $file): void
    {
        DB::execute(
            'INSERT INTO ticket_attachments (ticket_message_id, filename, original_filename, mime_type, file_size) VALUES (?, ?, ?, ?, ?)',
            [$messageId, $file['stored_name'], $file['original_name'], $file['mime_type'], $file['size']]
        );
    }

    public static function attachments(int $messageId): array
    {
        return DB::fetchAll(
            'SELECT * FROM ticket_attachments WHERE ticket_message_id = ?',
            [$messageId]
        );
    }

    public static function counts(): array
    {
        $rows = DB::fetchAll('SELECT status, COUNT(*) as cnt FROM tickets GROUP BY status');
        $result = array_fill_keys(['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'], 0);
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }

    private static function generateReference(): string
    {
        do {
            $ref = 'BBZ-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = DB::fetchOne('SELECT id FROM tickets WHERE reference = ?', [$ref]);
        } while ($exists);
        return $ref;
    }
}
