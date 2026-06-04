<?php
$e      = fn($v) => \App\Core\Security::e($v);
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
$statuses = ['pending','confirmed','processing','dispatched','delivered','cancelled'];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Shop Orders</h1>
        <p class="page-subtitle">
            <?= (int)$counts['total'] ?> total &bull;
            <span style="color:var(--warning)"><?= (int)$counts['pending'] ?> pending</span>
        </p>
    </div>
    <a href="/admin/shop/orders/export" class="btn btn-secondary">Export CSV</a>
</div>

<!-- Status tabs -->
<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;">
    <a href="/admin/shop/orders" class="btn btn-sm <?= empty($filters['status']) ? 'btn-primary' : 'btn-secondary' ?>">All (<?= (int)$counts['total'] ?>)</a>
    <?php foreach ($statuses as $s): ?>
    <a href="/admin/shop/orders?status=<?= $s ?><?= !empty($filters['search']) ? '&q=' . urlencode($filters['search']) : '' ?>"
       class="btn btn-sm <?= $filters['status'] === $s ? 'btn-primary' : 'btn-secondary' ?>">
        <?= \App\Models\ShopOrder::statusLabel($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px;">
        <form method="get" style="display:flex;gap:12px;align-items:center;">
            <?php if (!empty($filters['status'])): ?>
            <input type="hidden" name="status" value="<?= $e($filters['status']) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= $e($filters['search']) ?>" placeholder="Search by reference, customer…" class="form-control" style="max-width:320px;">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($filters['search']): ?>
            <a href="/admin/shop/orders<?= !empty($filters['status']) ? '?status=' . $filters['status'] : '' ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($orders)): ?>
        <div class="empty-state" style="padding:40px;"><p style="color:var(--text-muted);">No orders found.</p></div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th style="text-align:right;">Total</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="/admin/shop/orders/<?= (int)$o['id'] ?>" style="font-weight:600;"><?= $e($o['reference']) ?></a></td>
                <td>
                    <a href="/admin/customers/<?= (int)$o['user_id'] ?>"><?= $e($o['customer_name']) ?></a>
                    <div style="color:var(--text-muted);font-size:12px;"><?= $e($o['customer_email']) ?></div>
                </td>
                <td style="color:var(--text-muted);font-size:13px;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td><?= $o['checkout_method'] === 'po' ? '<span class="badge badge-outline">PO</span>' : '<span class="badge badge-outline">Card</span>' ?></td>
                <td><span class="badge badge-<?= \App\Models\ShopOrder::statusClass($o['status']) ?>"><?= \App\Models\ShopOrder::statusLabel($o['status']) ?></span></td>
                <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$o['total'], 2) ?></td>
                <td><a href="/admin/shop/orders/<?= (int)$o['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
