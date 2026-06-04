<?php
$symbol    = \App\Models\Setting::get('currency_symbol') ?: '£';
$csrfToken = \App\Core\Security::csrfToken();
?>
<div class="page-header">
    <div>
        <a href="/orders" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            All Orders
        </a>
        <h1 class="page-title">Order <?= \App\Core\Security::e($order['reference']) ?></h1>
    </div>
    <span class="badge badge-<?= \App\Models\ShopOrder::statusClass($order['status']) ?>" style="font-size:14px;padding:8px 16px;">
        <?= \App\Models\ShopOrder::statusLabel($order['status']) ?>
    </span>
</div>

<div class="order-detail-grid">
    <div>
        <!-- Items -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Items</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>Product</th><th>SKU</th><th>Unit Price</th><th>Qty</th><th style="text-align:right;">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= \App\Core\Security::e($item['name']) ?></td>
                        <td style="color:var(--text-muted);font-size:13px;"><?= \App\Core\Security::e($item['sku']) ?></td>
                        <td><?= $symbol ?><?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$item['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">Subtotal</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['subtotal'], 2) ?></td></tr>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">Delivery</td><td style="text-align:right;"><?= (float)$order['delivery_charge'] > 0 ? $symbol . number_format((float)$order['delivery_charge'], 2) : '<span style="color:var(--success)">FREE</span>' ?></td></tr>
                        <?php if ($order['vat_amount'] > 0): ?>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">VAT</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['vat_amount'], 2) ?></td></tr>
                        <?php endif; ?>
                        <tr><td colspan="4" style="text-align:right;font-weight:700;font-size:15px;">Total</td><td style="text-align:right;font-weight:700;font-size:15px;"><?= $symbol ?><?= number_format((float)$order['total'], 2) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Addresses -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Shipping Address</h3></div>
                <div class="card-body" style="font-size:14px;line-height:1.8;">
                    <?php if ($order['shipping_name']): ?>
                    <?= \App\Core\Security::e($order['shipping_name']) ?><br>
                    <?php endif; ?>
                    <?php if ($order['shipping_company']): ?>
                    <?= \App\Core\Security::e($order['shipping_company']) ?><br>
                    <?php endif; ?>
                    <?= \App\Core\Security::e($order['shipping_line1'] ?? '') ?><br>
                    <?php if ($order['shipping_line2']): ?><?= \App\Core\Security::e($order['shipping_line2']) ?><br><?php endif; ?>
                    <?= \App\Core\Security::e($order['shipping_city'] ?? '') ?>
                    <?php if ($order['shipping_county']): ?>, <?= \App\Core\Security::e($order['shipping_county']) ?><?php endif; ?><br>
                    <?= \App\Core\Security::e($order['shipping_postcode'] ?? '') ?><br>
                    <?= \App\Core\Security::e($order['shipping_country'] ?? '') ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Billing Address</h3></div>
                <div class="card-body" style="font-size:14px;line-height:1.8;">
                    <?php if ($order['billing_company']): ?>
                    <?= \App\Core\Security::e($order['billing_company']) ?><br>
                    <?php endif; ?>
                    <?= \App\Core\Security::e($order['billing_line1'] ?? '') ?><br>
                    <?php if ($order['billing_line2']): ?><?= \App\Core\Security::e($order['billing_line2']) ?><br><?php endif; ?>
                    <?= \App\Core\Security::e($order['billing_city'] ?? '') ?>
                    <?php if ($order['billing_county']): ?>, <?= \App\Core\Security::e($order['billing_county']) ?><?php endif; ?><br>
                    <?= \App\Core\Security::e($order['billing_postcode'] ?? '') ?><br>
                    <?= \App\Core\Security::e($order['billing_country'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Order Info</h3></div>
            <div class="card-body">
                <div class="kv-grid">
                    <div class="kv-row"><span class="kv-label">Reference</span><span class="kv-value"><?= \App\Core\Security::e($order['reference']) ?></span></div>
                    <div class="kv-row"><span class="kv-label">Date</span><span class="kv-value"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></div>
                    <div class="kv-row"><span class="kv-label">Method</span><span class="kv-value"><?= $order['checkout_method'] === 'po' ? 'Purchase Order' : 'Card Payment' ?></span></div>
                    <?php if ($order['po_number']): ?>
                    <div class="kv-row"><span class="kv-label">PO Number</span><span class="kv-value"><strong><?= \App\Core\Security::e($order['po_number']) ?></strong></span></div>
                    <?php endif; ?>
                    <?php if ($order['po_notes']): ?>
                    <div class="kv-row"><span class="kv-label">PO Notes</span><span class="kv-value"><?= \App\Core\Security::e($order['po_notes']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($order['notes']): ?>
                    <div class="kv-row"><span class="kv-label">Notes</span><span class="kv-value"><?= \App\Core\Security::e($order['notes']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:8px;">
            <form method="post" action="/orders/<?= (int)$order['id'] ?>/reorder">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                    Order Again
                </button>
            </form>
            <a href="/shop" class="btn btn-secondary" style="text-align:center;">Continue Shopping</a>
        </div>
    </div>
</div>
