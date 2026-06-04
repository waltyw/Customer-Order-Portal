<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Admin Dashboard</h1>
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:13px;color:#94a3b8;"><?= date('l, j F Y') ?></span>
        <a href="/admin/system-status" class="btn btn-outline btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            System Status
        </a>
    </div>
</div>

<!-- Ticket stats -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
    <?php
    $statMap = [
        'open'             => ['label' => 'Open',             'color' => '#2563eb', 'bg' => '#eff6ff'],
        'in_progress'      => ['label' => 'In Progress',      'color' => '#d97706', 'bg' => '#fffbeb'],
        'waiting_customer' => ['label' => 'Waiting Customer', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        'resolved'         => ['label' => 'Resolved',         'color' => '#16a34a', 'bg' => '#f0fdf4'],
        'closed'           => ['label' => 'Closed',           'color' => '#64748b', 'bg' => '#f1f5f9'],
    ];
    foreach ($statMap as $key => $s):
    ?>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value" style="color:<?= $s['color'] ?>;"><?= (int)($ticketCounts[$key] ?? 0) ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Invoice summary -->
<?php
$outstanding = 0;
foreach ($invoiceCounts as $status => $data) {
    if (!in_array($status, ['paid','voided'])) $outstanding += $data['total_due'];
}
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:700;">£<?= number_format($outstanding, 2) ?></div>
                <div style="color:#64748b;font-size:13px;">Total Outstanding</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <div style="font-size:26px;font-weight:700;"><?= (int)($invoiceCounts['paid']['count'] ?? 0) ?></div>
                <div style="color:#64748b;font-size:13px;">Invoices Paid</div>
            </div>
        </div>
    </div>
</div>

<!-- Recent tickets -->
<div class="card p-0">
    <div class="card-header" style="padding:16px 20px;">
        <h2>Recent Tickets</h2>
        <a href="/admin/tickets" class="btn btn-sm btn-outline">View All</a>
    </div>
    <table class="table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Subject</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($recentTickets as $t): ?>
        <tr onclick="location.href='/admin/tickets/<?= $t['id'] ?>'" style="cursor:pointer;">
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
</div>
