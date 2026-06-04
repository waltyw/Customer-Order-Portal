<?php
$symbol    = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
$csrfToken = \App\Core\Security::csrfToken();
$isPo      = $method === 'po';
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

function addrFields(string $prefix, array $user): void {
    $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
?>
    <div class="form-group">
        <label class="form-label">Company</label>
        <input type="text" name="<?= $prefix ?>_company" class="form-control" value="<?= $e($user['company'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Address Line 1</label>
        <input type="text" name="<?= $prefix ?>_line1" class="form-control" placeholder="Street address">
    </div>
    <div class="form-group">
        <label class="form-label">Address Line 2</label>
        <input type="text" name="<?= $prefix ?>_line2" class="form-control" placeholder="Apartment, suite, etc.">
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="<?= $prefix ?>_city" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">County</label>
            <input type="text" name="<?= $prefix ?>_county" class="form-control">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Postcode</label>
            <input type="text" name="<?= $prefix ?>_postcode" class="form-control" style="max-width:140px;">
        </div>
        <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="<?= $prefix ?>_country" class="form-control" value="United Kingdom">
        </div>
    </div>
<?php
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Checkout</h1>
        <p class="page-subtitle"><?= $isPo ? 'Purchase Order' : 'Card Payment' ?></p>
    </div>
</div>

<form method="post" action="/checkout/process" id="checkoutForm">
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
<div class="checkout-layout">
    <div class="checkout-form-col">

        <!-- Shipping Address -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Shipping Address</h3></div>
            <div class="card-body">
                <?php if (!empty($addresses)): ?>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Saved Address</label>
                    <select name="shipping_address_id" class="form-control" onchange="toggleInlineAddr('shipping', this.value)">
                        <option value="">— Enter a new address —</option>
                        <?php foreach ($addresses as $addr): if (!in_array($addr['type'], ['shipping','both'])) continue; ?>
                        <option value="<?= (int)$addr['id'] ?>" <?= $addr['is_default_shipping'] ? 'selected' : '' ?>>
                            <?= $e($addr['label'] ?: ($addr['line1'] . ', ' . $addr['city'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div id="shippingInline" <?= !empty($addresses) ? 'style="display:none;"' : '' ?>>
                    <?php addrFields('shipping', $user); ?>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:12px;cursor:pointer;">
                    <input type="checkbox" name="ship_to_billing" value="1" onchange="toggleBillingCard(this)">
                    Billing address same as shipping
                </label>
            </div>
        </div>

        <!-- Billing Address -->
        <div class="card mb-4" id="billingCard">
            <div class="card-header"><h3 class="card-title">Billing Address</h3></div>
            <div class="card-body">
                <?php if (!empty($addresses)): ?>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Saved Address</label>
                    <select name="billing_address_id" class="form-control" onchange="toggleInlineAddr('billing', this.value)">
                        <option value="">— Enter a new address —</option>
                        <?php foreach ($addresses as $addr): if (!in_array($addr['type'], ['billing','both'])) continue; ?>
                        <option value="<?= (int)$addr['id'] ?>" <?= $addr['is_default_billing'] ? 'selected' : '' ?>>
                            <?= $e($addr['label'] ?: ($addr['line1'] . ', ' . $addr['city'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div id="billingInline" <?= !empty($addresses) ? 'style="display:none;"' : '' ?>>
                    <?php addrFields('billing', $user); ?>
                </div>
            </div>
        </div>

        <?php if ($isPo): ?>
        <!-- Purchase Order Details -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Purchase Order Details</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">PO Number <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="po_number" class="form-control" placeholder="e.g. PO-2024-001" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="po_notes" rows="3" class="form-control" placeholder="Delivery instructions, reference notes…"></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Notes -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Order Notes <span style="color:var(--text-muted);font-weight:400;font-size:13px;">(optional)</span></h3>
            </div>
            <div class="card-body">
                <textarea name="notes" rows="3" class="form-control" placeholder="Special instructions…"></textarea>
            </div>
        </div>

    </div>

    <!-- Order Summary -->
    <div class="checkout-summary-col">
        <div class="card" style="position:sticky;top:24px;">
            <div class="card-header"><h3 class="card-title">Order Summary</h3></div>
            <div class="card-body">
                <div class="checkout-item-list">
                    <?php
                    $sym = \App\Models\Setting::get('currency_symbol') ?: '£';
                    foreach ($items as $item):
                        $price = (float)($item['tier_price'] ?? 0);
                    ?>
                    <div class="checkout-item">
                        <span><?= $e($item['name']) ?> <span style="color:var(--text-muted);font-size:12px;">&times;<?= (int)$item['quantity'] ?></span></span>
                        <span><?= $sym ?><?= number_format($price * (int)$item['quantity'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr style="border:none;border-top:1px solid var(--border);margin:12px 0;">
                <div class="summary-row"><span>Subtotal</span><span><?= $symbol ?><?= number_format($subtotal, 2) ?></span></div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span><?= $delivery > 0 ? $symbol . number_format($delivery, 2) : '<span style="color:var(--success)">FREE</span>' ?></span>
                </div>
                <?php if ($vatEnabled): ?>
                <div class="summary-row"><span>VAT (<?= (int)$vatRate ?>%)</span><span><?= $symbol ?><?= number_format($vatAmount, 2) ?></span></div>
                <?php endif; ?>
                <div class="summary-row summary-total" style="margin-top:8px;font-size:16px;padding-top:8px;border-top:2px solid var(--border);">
                    <strong>Total</strong>
                    <strong><?= $symbol ?><?= number_format($total, 2) ?></strong>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;padding:12px;font-size:15px;">
                    <?= $isPo ? 'Place Order (PO)' : 'Pay ' . $symbol . number_format($total, 2) ?>
                </button>
                <a href="/basket" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:var(--text-muted);">Edit Basket</a>
            </div>
        </div>
    </div>
</div>
</form>

<script>
function toggleInlineAddr(prefix, val) {
    document.getElementById(prefix + 'Inline').style.display = val ? 'none' : '';
}
function toggleBillingCard(cb) {
    document.getElementById('billingCard').style.display = cb.checked ? 'none' : '';
}
</script>
