<?php
$symbol    = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
$csrfToken = \App\Core\Security::csrfToken();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Your Basket</h1>
        <?php if (!empty($items)): ?>
        <p class="page-subtitle"><?= (int)$basketCount ?> item(s)</p>
        <?php endif; ?>
    </div>
    <a href="/shop" class="btn btn-secondary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Continue Shopping
    </a>
</div>

<?php if (empty($items)): ?>
<div class="empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted)"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <p style="margin-top:12px;color:var(--text-muted);">Your basket is empty.</p>
    <a href="/shop" class="btn btn-primary" style="margin-top:16px;">Browse Products</a>
</div>
<?php else: ?>
<div class="basket-layout">
    <div class="basket-items">
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="data-table basket-table">
                    <thead>
                        <tr>
                            <th style="width:60px;"></th>
                            <th>Product</th>
                            <th style="width:80px;">SKU</th>
                            <th style="width:100px;">Unit Price</th>
                            <th style="width:120px;">Quantity</th>
                            <th style="width:100px;text-align:right;">Total</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="basketBody">
                    <?php foreach ($items as $item):
                        $price    = (float)($item['tier_price'] ?? 0);
                        $lineTotal = $price * (int)$item['quantity'];
                        $inStock  = (int)$item['stock'] > 0;
                    ?>
                    <tr class="basket-row" data-product-id="<?= (int)$item['product_id'] ?>">
                        <td>
                            <?php if ($item['primary_image']): ?>
                            <img src="/assets/img/products/<?= \App\Core\Security::e($item['primary_image']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                            <div style="width:48px;height:48px;background:var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/shop/<?= \App\Core\Security::e($item['slug'] ?? '') ?>" style="font-weight:600;"><?= \App\Core\Security::e($item['name']) ?></a>
                            <?php if (!$inStock): ?><br><small class="text-danger">Out of stock</small><?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;"><?= \App\Core\Security::e($item['sku']) ?></td>
                        <td><?= $symbol ?><?= number_format($price, 2) ?></td>
                        <td>
                            <form class="qty-form" data-product-id="<?= (int)$item['product_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                <div class="qty-stepper">
                                    <button type="button" class="qty-btn" onclick="changeQty(this,-1)">−</button>
                                    <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="0" max="999" class="qty-field" onchange="updateQty(this)">
                                    <button type="button" class="qty-btn" onclick="changeQty(this,1)">+</button>
                                </div>
                            </form>
                        </td>
                        <td class="line-total" style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format($lineTotal, 2) ?></td>
                        <td>
                            <form method="post" action="/basket/remove" class="remove-form">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                <button type="submit" class="btn-icon" title="Remove" style="color:var(--danger);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;">
            <form method="post" action="/basket/clear" onsubmit="return confirm('Clear your basket?')">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn btn-secondary">Clear Basket</button>
            </form>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="basket-summary">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Order Summary</h3></div>
            <div class="card-body">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="summarySubtotal"><?= $symbol ?><?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span id="summaryDelivery"><?= $delivery > 0 ? $symbol . number_format($delivery, 2) : '<span style="color:var(--success)">FREE</span>' ?></span>
                </div>
                <?php if ($vatEnabled): ?>
                <div class="summary-row">
                    <span>VAT (<?= (int)$vatRate ?>%)</span>
                    <span id="summaryVat"><?= $symbol ?><?= number_format($vatAmount, 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <strong>Total</strong>
                    <strong id="summaryTotal"><?= $symbol ?><?= number_format($total, 2) ?></strong>
                </div>
                <a href="/checkout" class="btn btn-primary" style="width:100%;margin-top:16px;text-align:center;">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const sym = '<?= $symbol ?>';
function changeQty(btn, delta) {
    const form  = btn.closest('.qty-form');
    const input = form.querySelector('.qty-field');
    const newVal = Math.max(0, parseInt(input.value) + delta);
    input.value = newVal;
    updateQty(input);
}

function updateQty(input) {
    const form      = input.closest('.qty-form');
    const productId = parseInt(form.dataset.productId);
    const quantity  = parseInt(input.value) || 0;
    const data      = new FormData(form);
    data.set('quantity', quantity);
    fetch('/basket/update', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
        .then(r => r.json()).then(function(res) {
            if (!res.ok) return;
            const row = document.querySelector('.basket-row[data-product-id="' + productId + '"]');
            if (quantity === 0 && row) { row.remove(); return; }
            if (row) {
                const unitPrice = parseFloat(row.querySelector('td:nth-child(5)').textContent.replace(sym,'').trim());
                // Actually get unit price from the table
                const cells = row.querySelectorAll('td');
                const unitPriceText = cells[3].textContent.replace(sym,'').trim();
                const up = parseFloat(unitPriceText) || 0;
                const lt = row.querySelector('.line-total');
                if (lt) lt.textContent = sym + (up * quantity).toFixed(2);
            }
            document.getElementById('summarySubtotal').textContent = sym + parseFloat(res.subtotal).toFixed(2);
            document.getElementById('summaryDelivery').innerHTML = res.delivery > 0 ? sym + parseFloat(res.delivery).toFixed(2) : '<span style="color:var(--success)">FREE</span>';
            const vatEl = document.getElementById('summaryVat');
            if (vatEl) vatEl.textContent = sym + parseFloat(res.vat).toFixed(2);
            document.getElementById('summaryTotal').textContent = sym + parseFloat(res.total).toFixed(2);
            const badge = document.querySelector('.basket-badge');
            if (badge) badge.textContent = res.count;
        });
}

document.querySelectorAll('.remove-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = new FormData(form);
        fetch('/basket/remove', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
            .then(r => r.json()).then(function(res) {
                if (res.ok) {
                    const productId = data.get('product_id');
                    const row = document.querySelector('.basket-row[data-product-id="' + productId + '"]');
                    if (row) row.remove();
                    const badge = document.querySelector('.basket-badge');
                    if (badge) badge.textContent = res.count;
                    if (res.count === 0) location.reload();
                }
            });
    });
});
</script>
<?php endif; ?>
