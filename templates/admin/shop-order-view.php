<?php
$e      = fn($v) => \App\Core\Security::e($v);
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
$csrf   = \App\Core\Security::csrfToken();
$statuses = ['pending','confirmed','processing','dispatched','delivered','cancelled'];
?>
<div class="page-header">
    <div>
        <a href="/admin/shop/orders" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Orders
        </a>
        <h1 class="page-title">Order <?= $e($order['reference']) ?></h1>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
        <span class="badge badge-<?= \App\Models\ShopOrder::statusClass($order['status']) ?>" style="font-size:14px;padding:8px 16px;">
            <?= \App\Models\ShopOrder::statusLabel($order['status']) ?>
        </span>
        <form method="post" action="/admin/shop/orders/<?= (int)$order['id'] ?>/status" style="display:flex;gap:8px;">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <select name="status" class="form-control" style="max-width:180px;">
                <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= \App\Models\ShopOrder::statusLabel($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
    <div>
        <!-- Items -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Order Items</h3></div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>Product</th><th>SKU</th><th>Unit Price</th><th>Qty</th><th style="text-align:right;">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= $e($item['name']) ?></td>
                        <td style="font-family:monospace;font-size:13px;color:var(--text-muted);"><?= $e($item['sku']) ?></td>
                        <td><?= $symbol ?><?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$item['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">Subtotal</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['subtotal'], 2) ?></td></tr>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">Delivery</td><td style="text-align:right;"><?= (float)$order['delivery_charge'] > 0 ? $symbol . number_format((float)$order['delivery_charge'], 2) : 'FREE' ?></td></tr>
                        <?php if ((float)$order['vat_amount'] > 0): ?>
                        <tr><td colspan="4" style="text-align:right;color:var(--text-muted);">VAT</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['vat_amount'], 2) ?></td></tr>
                        <?php endif; ?>
                        <tr><td colspan="4" style="text-align:right;font-weight:700;font-size:15px;">Total</td><td style="text-align:right;font-weight:700;font-size:15px;"><?= $symbol ?><?= number_format((float)$order['total'], 2) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Addresses -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Shipping Address</h3></div>
                <div class="card-body" style="font-size:14px;line-height:1.8;">
                    <?= $e($order['shipping_name'] ?? '') ?><br>
                    <?php if ($order['shipping_company']): ?><?= $e($order['shipping_company']) ?><br><?php endif; ?>
                    <?= $e($order['shipping_line1'] ?? '') ?><br>
                    <?php if ($order['shipping_line2']): ?><?= $e($order['shipping_line2']) ?><br><?php endif; ?>
                    <?= $e($order['shipping_city'] ?? '') ?><?php if ($order['shipping_county']): ?>, <?= $e($order['shipping_county']) ?><?php endif; ?><br>
                    <?= $e($order['shipping_postcode'] ?? '') ?><br>
                    <?= $e($order['shipping_country'] ?? '') ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Billing Address</h3></div>
                <div class="card-body" style="font-size:14px;line-height:1.8;">
                    <?php if ($order['billing_company']): ?><?= $e($order['billing_company']) ?><br><?php endif; ?>
                    <?= $e($order['billing_line1'] ?? '') ?><br>
                    <?php if ($order['billing_line2']): ?><?= $e($order['billing_line2']) ?><br><?php endif; ?>
                    <?= $e($order['billing_city'] ?? '') ?><?php if ($order['billing_county']): ?>, <?= $e($order['billing_county']) ?><?php endif; ?><br>
                    <?= $e($order['billing_postcode'] ?? '') ?><br>
                    <?= $e($order['billing_country'] ?? '') ?>
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
                    <div class="kv-row"><span class="kv-label">Reference</span><span class="kv-value"><?= $e($order['reference']) ?></span></div>
                    <div class="kv-row"><span class="kv-label">Date</span><span class="kv-value"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></span></div>
                    <div class="kv-row"><span class="kv-label">Customer</span><span class="kv-value"><a href="/admin/customers/<?= (int)$order['user_id'] ?>"><?= $e($order['customer_name']) ?></a></span></div>
                    <div class="kv-row"><span class="kv-label">Method</span><span class="kv-value"><?= $order['checkout_method'] === 'po' ? 'Purchase Order' : 'Card (Stripe)' ?></span></div>
                    <?php if ($order['tier_name']): ?>
                    <div class="kv-row"><span class="kv-label">Tier</span><span class="kv-value"><?= $e($order['tier_name']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($order['po_number']): ?>
                    <div class="kv-row"><span class="kv-label">PO Number</span><span class="kv-value"><strong><?= $e($order['po_number']) ?></strong></span></div>
                    <?php endif; ?>
                    <?php if ($order['po_notes']): ?>
                    <div class="kv-row"><span class="kv-label">PO Notes</span><span class="kv-value"><?= $e($order['po_notes']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($order['notes']): ?>
                    <div class="kv-row"><span class="kv-label">Notes</span><span class="kv-value"><?= $e($order['notes']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($order['stripe_payment_intent']): ?>
                    <div class="kv-row"><span class="kv-label">Stripe PI</span><span class="kv-value" style="font-size:11px;font-family:monospace;"><?= $e($order['stripe_payment_intent']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
