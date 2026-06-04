<?php
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">My Orders</h1>
        <p class="page-subtitle"><?= count($orders) ?> order(s)</p>
    </div>
    <a href="/shop" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Shop
    </a>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p style="margin-top:12px;color:var(--text-muted);">You haven't placed any orders yet.</p>
    <a href="/shop" class="btn btn-primary" style="margin-top:16px;">Start Shopping</a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th style="text-align:right;">Total</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="/orders/<?= (int)$o['id'] ?>" style="font-weight:600;"><?= \App\Core\Security::e($o['reference']) ?></a></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td style="color:var(--text-muted);">—</td>
                <td><?= $o['checkout_method'] === 'po' ? '<span class="badge badge-outline">PO</span>' : '<span class="badge badge-outline">Card</span>' ?></td>
                <td><span class="badge badge-<?= \App\Models\ShopOrder::statusClass($o['status']) ?>"><?= \App\Models\ShopOrder::statusLabel($o['status']) ?></span></td>
                <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$o['total'], 2) ?></td>
                <td>
                    <a href="/orders/<?= (int)$o['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
