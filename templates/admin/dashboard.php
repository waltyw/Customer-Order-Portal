<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Admin Dashboard</h1>
    <span style="font-size:13px;color:#94a3b8;"><?= date('l, j F Y') ?></span>
</div>

<!-- Order stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
    <?php
    $orderStatMap = [
        'total'      => ['label' => 'Total Orders',  'color' => '#1e293b', 'bg' => '#f1f5f9'],
        'pending'    => ['label' => 'Pending',        'color' => '#d97706', 'bg' => '#fffbeb'],
        'confirmed'  => ['label' => 'Confirmed',      'color' => '#2563eb', 'bg' => '#eff6ff'],
        'processing' => ['label' => 'Processing',     'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        'dispatched' => ['label' => 'Dispatched',     'color' => '#16a34a', 'bg' => '#f0fdf4'],
    ];
    foreach ($orderStatMap as $key => $s): ?>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value" style="color:<?= $s['color'] ?>;"><?= (int)($orderCounts[$key] ?? 0) ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Orders -->
<div class="card p-0" style="margin-bottom:24px;">
    <div class="card-header" style="padding:16px 20px;">
        <h2>Recent Orders</h2>
        <a href="/admin/shop/orders" class="btn btn-sm btn-outline">View All</a>
    </div>
    <?php if (empty($recentOrders)): ?>
    <div style="padding:32px;text-align:center;color:var(--text-muted);">No orders yet.</div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Method</th>
                <th>Status</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
        foreach ($recentOrders as $o): ?>
        <tr onclick="location.href='/admin/shop/orders/<?= (int)$o['id'] ?>'" style="cursor:pointer;">
            <td><strong><?= Security::e($o['reference']) ?></strong></td>
            <td><?= Security::e($o['customer_name']) ?></td>
            <td style="color:var(--text-muted);font-size:13px;"><?= date('j M Y', strtotime($o['created_at'])) ?></td>
            <td><?= $o['checkout_method'] === 'po' ? '<span class="badge badge-outline">PO</span>' : '<span class="badge badge-outline">Card</span>' ?></td>
            <td><span class="badge badge-<?= \App\Models\ShopOrder::statusClass($o['status']) ?>"><?= \App\Models\ShopOrder::statusLabel($o['status']) ?></span></td>
            <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$o['total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Ticket stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
    <?php
    $statMap = [
        'open'             => ['label' => 'Open',             'color' => '#2563eb'],
        'in_progress'      => ['label' => 'In Progress',      'color' => '#d97706'],
        'waiting_customer' => ['label' => 'Waiting Customer', 'color' => '#7c3aed'],
        'resolved'         => ['label' => 'Resolved',         'color' => '#16a34a'],
        'closed'           => ['label' => 'Closed',           'color' => '#64748b'],
    ];
    foreach ($statMap as $key => $s): ?>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value" style="color:<?= $s['color'] ?>;"><?= (int)($ticketCounts[$key] ?? 0) ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Tickets -->
<div class="card p-0">
    <div class="card-header" style="padding:16px 20px;">
        <h2>Recent Tickets</h2>
        <a href="/admin/tickets" class="btn btn-sm btn-outline">View All</a>
    </div>
    <?php if (empty($recentTickets)): ?>
    <div style="padding:32px;text-align:center;color:var(--text-muted);">No tickets yet.</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($recentTickets as $t): ?>
        <tr onclick="location.href='/admin/tickets/<?= (int)$t['id'] ?>'" style="cursor:pointer;">
            <td><code><?= Security::e($t['reference']) ?></code></td>
            <td><?= Security::e($t['customer_name']) ?></td>
            <td><?= Security::e($t['subject']) ?></td>
            <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
            <td class="text-muted"><?= date('j M, H:i', strtotime($t['updated_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
