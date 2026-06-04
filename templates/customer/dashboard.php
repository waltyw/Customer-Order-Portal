<?php
use App\Core\Security;
use App\Models\ServiceStatus;
use App\Models\Setting;
$showInvoices = Setting::get('invoices_enabled') !== '0' && ($user['show_invoices'] ?? 1);
?>
<div class="page-header">
    <h1>Welcome back, <?= Security::e(explode(' ', $_SESSION['user_name'] ?? 'there')[0]) ?></h1>
    <p>Here's an overview of your account</p>
</div>

<?php if ($hasIssues && !empty($services)): ?>
<?php
$affected = array_filter($services, fn($s) => $s['status'] !== 'operational');
$icons    = ['degraded'=>'⚠️','outage'=>'🔴','maintenance'=>'🔧'];
$colours  = ['degraded'=>'warning','outage'=>'error','maintenance'=>'info'];
$worst    = $overallStatus;
?>
<div class="alert alert-<?= $colours[$worst] ?? 'warning' ?>" style="margin-bottom:20px;">
    <div>
        <strong><?= $icons[$worst] ?? '⚠️' ?> Service Update</strong>
        <div style="margin-top:4px;font-size:13px;">
            <?php foreach ($affected as $svc): ?>
            <span><strong><?= Security::e($svc['service_name']) ?>:</strong> <?= ServiceStatus::statusLabel($svc['status']) ?><?= $svc['message'] ? ' — ' . Security::e($svc['message']) : '' ?></span><br>
            <?php endforeach; ?>
        </div>
    </div>
    <a href="/help" style="font-size:13px;font-weight:600;white-space:nowrap;">View Status →</a>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;color:#2563eb;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['ticket_count'] ?? 0) ?></div>
            <div class="stat-label">Support Tickets</div>
        </div>
    </div>
    <?php if ($showInvoices): ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['invoice_count'] ?? 0) ?></div>
            <div class="stat-label">Unpaid Invoices</div>
        </div>
    </div>
    <?php $currency = \App\Models\Setting::get('currency_symbol') ?: '£'; ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;font-size:22px;font-weight:800;font-family:Georgia,serif;">
            <?= htmlspecialchars($currency) ?>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= htmlspecialchars($currency) ?><?= number_format((float)($stats['amount_outstanding'] ?? 0), 2) ?></div>
            <div class="stat-label">Outstanding Balance</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h2>Recent Tickets</h2>
            <a href="/tickets/create" class="btn btn-sm btn-primary">New Ticket</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($tickets)): ?>
            <div class="empty-state">No tickets yet. <a href="/tickets/create">Raise a ticket</a> if you need help.</div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Reference</th><th>Subject</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr onclick="location.href='/tickets/<?= $t['id'] ?>'" style="cursor:pointer;">
                    <td><code><?= Security::e($t['reference']) ?></code></td>
                    <td><?= Security::e($t['subject']) ?></td>
                    <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
                    <td class="text-muted"><?= date('j M', strtotime($t['updated_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer"><a href="/tickets">View all tickets &rarr;</a></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showInvoices): ?>
    <div class="card">
        <div class="card-header">
            <h2>Recent Invoices</h2>
            <a href="/invoices" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($invoices)): ?>
            <div class="empty-state">No invoices on your account yet.</div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Invoice</th><th>Amount</th><th>Status</th><th>Due</th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr onclick="location.href='/invoices/<?= $inv['id'] ?>'" style="cursor:pointer;">
                    <td><?= Security::e($inv['invoice_number']) ?></td>
                    <td>£<?= number_format((float)$inv['total'], 2) ?></td>
                    <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td class="text-muted"><?= $inv['due_date'] ? date('j M Y', strtotime($inv['due_date'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer"><a href="/invoices">View all invoices &rarr;</a></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
