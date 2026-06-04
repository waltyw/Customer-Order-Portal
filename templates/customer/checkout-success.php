<?php
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
$status = \App\Models\ShopOrder::statusLabel($order['status']);
?>
<div style="max-width:640px;margin:0 auto;text-align:center;padding:40px 20px;">
    <div style="width:72px;height:72px;background:rgba(22,163,74,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;">Order Confirmed!</h1>
    <p style="color:var(--text-muted);margin-bottom:32px;">Thank you for your order. We'll be in touch shortly.</p>

    <div class="card" style="text-align:left;margin-bottom:24px;">
        <div class="card-header">
            <h3 class="card-title">Order Details</h3>
        </div>
        <div class="card-body">
            <div class="kv-grid">
                <div class="kv-row">
                    <span class="kv-label">Order Reference</span>
                    <span class="kv-value"><strong><?= \App\Core\Security::e($order['reference']) ?></strong></span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Status</span>
                    <span class="kv-value"><span class="badge badge-<?= \App\Models\ShopOrder::statusClass($order['status']) ?>"><?= $status ?></span></span>
                </div>
                <div class="kv-row">
                    <span class="kv-label">Method</span>
                    <span class="kv-value"><?= $order['checkout_method'] === 'po' ? 'Purchase Order' : 'Card Payment' ?></span>
                </div>
                <?php if ($order['po_number']): ?>
                <div class="kv-row">
                    <span class="kv-label">PO Number</span>
                    <span class="kv-value"><?= \App\Core\Security::e($order['po_number']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="text-align:left;margin-bottom:24px;">
        <div class="card-header"><h3 class="card-title">Items Ordered</h3></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th style="text-align:right;">Total</th></tr></thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= \App\Core\Security::e($item['name']) ?></td>
                    <td style="color:var(--text-muted);font-size:13px;"><?= \App\Core\Security::e($item['sku']) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td style="text-align:right;"><?= $symbol ?><?= number_format((float)$item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right;color:var(--text-muted);">Subtotal</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['subtotal'], 2) ?></td></tr>
                    <?php if ($order['delivery_charge'] > 0): ?>
                    <tr><td colspan="3" style="text-align:right;color:var(--text-muted);">Delivery</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['delivery_charge'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($order['vat_amount'] > 0): ?>
                    <tr><td colspan="3" style="text-align:right;color:var(--text-muted);">VAT</td><td style="text-align:right;"><?= $symbol ?><?= number_format((float)$order['vat_amount'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td colspan="3" style="text-align:right;font-weight:700;">Total</td><td style="text-align:right;font-weight:700;"><?= $symbol ?><?= number_format((float)$order['total'], 2) ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="/orders/<?= (int)$order['id'] ?>" class="btn btn-secondary">View Order</a>
        <a href="/orders" class="btn btn-secondary">All Orders</a>
        <a href="/shop" class="btn btn-primary">Continue Shopping</a>
    </div>
</div>
