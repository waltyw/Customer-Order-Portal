<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class ServiceStatus
{
    public static function all(): array
    {
        try {
            return DB::fetchAll(
                'SELECT s.*, u.name as updated_by_name
                 FROM service_status s
                 LEFT JOIN users u ON s.updated_by = u.id
                 ORDER BY s.sort_order ASC, s.service_name ASC'
            );
        } catch (\Exception) {
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM service_status WHERE id = ?', [$id]);
    }

    public static function update(int $id, string $status, string $message, int $updatedBy): void
    {
        DB::execute(
            'UPDATE service_status SET status = ?, message = ?, updated_by = ?, updated_at = NOW() WHERE id = ?',
            [$status, $message ?: null, $updatedBy, $id]
        );
    }

    public static function create(string $name, string $status, string $message, int $updatedBy): int
    {
        $order = DB::fetchOne('SELECT MAX(sort_order) + 1 as next FROM service_status');
        return DB::insert(
            'INSERT INTO service_status (service_name, sort_order, status, message, updated_by) VALUES (?, ?, ?, ?, ?)',
            [$name, (int)($order['next'] ?? 1), $status, $message ?: null, $updatedBy]
        );
    }

    public static function delete(int $id): void
    {
        DB::execute('DELETE FROM service_status WHERE id = ?', [$id]);
    }

    public static function overallStatus(array $services): string
    {
        if (empty($services)) return 'operational';
        $statuses = array_column($services, 'status');
        if (in_array('outage', $statuses))      return 'outage';
        if (in_array('degraded', $statuses))    return 'degraded';
        if (in_array('maintenance', $statuses)) return 'maintenance';
        return 'operational';
    }

    public static function hasIssues(array $services): bool
    {
        return self::overallStatus($services) !== 'operational';
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'operational' => 'Operational',
            'degraded'    => 'Degraded Performance',
            'outage'      => 'Service Outage',
            'maintenance' => 'Planned Maintenance',
            default       => 'Unknown',
        };
    }

    public static function statusColour(string $status): string
    {
        return match($status) {
            'operational' => '#16a34a',
            'degraded'    => '#d97706',
            'outage'      => '#dc2626',
            'maintenance' => '#2563eb',
            default       => '#64748b',
        };
    }

    public static function statusBg(string $status): string
    {
        return match($status) {
            'operational' => '#f0fdf4',
            'degraded'    => '#fffbeb',
            'outage'      => '#fef2f2',
            'maintenance' => '#eff6ff',
            default       => '#f8fafc',
        };
    }
}
