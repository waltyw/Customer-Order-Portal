<?php
use App\Core\Security;
$e = fn($v) => Security::e($v ?? '');
$c = $customer;
$sameDelivery = (int)($c['delivery_same_as_billing'] ?? 1);
?>
<div class="page-header">
    <div>
        <a href="/admin/customers" style="font-size:14px;color:#64748b;">&larr; All Customers</a>
        <h1 style="margin-top:4px;"><?= $e($c['name']) ?></h1>
        <div style="color:#64748b;margin-top:4px;"><?= $e($c['email']) ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <form method="POST" action="/admin/customers/<?= (int)$c['id'] ?>/toggle-invoices">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-outline btn-sm">
                <?= ($c['show_invoices'] ?? 1) ? 'Hide Invoices' : 'Show Invoices' ?>
            </button>
        </form>
        <form method="POST" action="/admin/customers/<?= (int)$c['id'] ?>/toggle">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn <?= $c['is_active'] ? 'btn-danger-outline' : 'btn-outline' ?>">
                <?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>
            </button>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="stat-card"><div class="stat-body"><div class="stat-value"><?= (int)$stats['ticket_count'] ?></div><div class="stat-label">Total Tickets</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-value"><?= (int)$stats['invoice_count'] ?></div><div class="stat-label">Unpaid Invoices</div></div></div>
    <div class="stat-card"><div class="stat-body"><div class="stat-value" style="color:#dc2626;">£<?= number_format($stats['amount_outstanding'], 2) ?></div><div class="stat-label">Outstanding</div></div></div>
</div>

<!-- Details + edit form -->
<div class="card mb-4">
    <div class="card-header">
        <h2>Customer Details</h2>
        <button class="btn btn-sm btn-outline" onclick="toggleEdit()">Edit</button>
    </div>

    <!-- View mode -->
    <div id="view-mode">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;border-top:1px solid var(--border);">

            <!-- Account -->
            <div style="padding:20px;border-right:1px solid var(--border);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Account</div>
                <table style="width:100%;font-size:14px;">
                    <tr><td style="color:var(--text-muted);padding:4px 0;width:45%;">Name</td><td><?= $e($c['name']) ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Company</td><td><?= $e($c['company']) ?: '—' ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Phone</td><td><?= $e($c['phone']) ?: '—' ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Branch No.</td><td><?= $e($c['branch_number']) ?: '—' ?></td></tr>
                    <?php if (!empty($c['cc_email_1'])): ?>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">CC Email 1</td><td><?= $e($c['cc_email_1']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($c['cc_email_2'])): ?>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">CC Email 2</td><td><?= $e($c['cc_email_2']) ?></td></tr>
                    <?php endif; ?>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Pricing Tier</td><td><?= $tier ? $e($tier['name']) : '<span style="color:var(--text-muted);">None</span>' ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Checkout</td><td><?= ($c['checkout_method'] ?? 'stripe') === 'po' ? 'Purchase Order' : 'Card (Stripe)' ?></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Status</td><td><span class="badge <?= $c['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr>
                    <tr><td style="color:var(--text-muted);padding:4px 0;">Member Since</td><td><?= date('j M Y', strtotime($c['created_at'])) ?></td></tr>
                </table>
            </div>

            <!-- Billing Address -->
            <div style="padding:20px;border-right:1px solid var(--border);">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Billing Address</div>
                <address style="font-style:normal;font-size:14px;line-height:1.9;">
                    <?php if ($c['billing_address_1']): ?>
                        <?= $e($c['billing_address_1']) ?><br>
                        <?php if ($c['billing_address_2']): ?><?= $e($c['billing_address_2']) ?><br><?php endif; ?>
                        <?= $e($c['billing_city']) ?><?= $c['billing_county'] ? ', ' . $e($c['billing_county']) : '' ?><br>
                        <?= $e($c['billing_postcode']) ?><br>
                        <?= $e($c['billing_country']) ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">Not set</span>
                    <?php endif; ?>
                </address>
            </div>

            <!-- Delivery Address -->
            <div style="padding:20px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Delivery Address</div>
                <?php if ($sameDelivery): ?>
                    <span style="color:var(--text-muted);font-size:14px;">Same as billing</span>
                <?php elseif ($c['delivery_address_1']): ?>
                    <address style="font-style:normal;font-size:14px;line-height:1.9;">
                        <?= $e($c['delivery_address_1']) ?><br>
                        <?php if ($c['delivery_address_2']): ?><?= $e($c['delivery_address_2']) ?><br><?php endif; ?>
                        <?= $e($c['delivery_city']) ?><?= $c['delivery_county'] ? ', ' . $e($c['delivery_county']) : '' ?><br>
                        <?= $e($c['delivery_postcode']) ?><br>
                        <?= $e($c['delivery_country']) ?>
                    </address>
                <?php else: ?>
                    <span style="color:var(--text-muted);font-size:14px;">Not set</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit mode -->
    <div id="edit-mode" style="display:none;">
        <form method="POST" action="/admin/customers/<?= (int)$c['id'] ?>/update">
            <?= Security::csrfField() ?>
            <div style="padding:20px;border-top:1px solid var(--border);">

                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Account</div>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required value="<?= $e($c['name']) ?>"></div>
                    <div class="form-group"><label>Company</label><input type="text" name="company" value="<?= $e($c['company']) ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= $e($c['phone']) ?>"></div>
                    <div class="form-group"><label>Branch Number</label><input type="text" name="branch_number" value="<?= $e($c['branch_number']) ?>" placeholder="BR001"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>CC Email 1 <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label><input type="email" name="cc_email_1" value="<?= $e($c['cc_email_1'] ?? '') ?>" placeholder="cc@example.com"></div>
                    <div class="form-group"><label>CC Email 2 <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label><input type="email" name="cc_email_2" value="<?= $e($c['cc_email_2'] ?? '') ?>" placeholder="cc@example.com"></div>
                </div>
                <p style="margin:-8px 0 12px;font-size:12px;color:var(--text-muted);">CC emails receive copies of invoices and order confirmations. They cannot be used to log in.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pricing Tier</label>
                        <select name="pricing_tier_id">
                            <option value="">— No tier —</option>
                            <?php foreach ($tiers as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= ((int)($c['pricing_tier_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>>
                                <?= $e($t['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Checkout Method</label>
                        <select name="checkout_method">
                            <option value="stripe" <?= ($c['checkout_method'] ?? 'stripe') === 'stripe' ? 'selected' : '' ?>>Card (Stripe)</option>
                            <option value="po"     <?= ($c['checkout_method'] ?? '') === 'po'     ? 'selected' : '' ?>>Purchase Order (PO)</option>
                        </select>
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Billing Address</div>
                <div class="form-group"><label>Address Line 1</label><input type="text" name="billing_address_1" value="<?= $e($c['billing_address_1']) ?>"></div>
                <div class="form-group"><label>Address Line 2</label><input type="text" name="billing_address_2" value="<?= $e($c['billing_address_2']) ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>City</label><input type="text" name="billing_city" value="<?= $e($c['billing_city']) ?>"></div>
                    <div class="form-group"><label>County</label><input type="text" name="billing_county" value="<?= $e($c['billing_county']) ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="max-width:160px;"><label>Postcode</label><input type="text" name="billing_postcode" value="<?= $e($c['billing_postcode']) ?>"></div>
                    <div class="form-group"><label>Country</label><input type="text" name="billing_country" value="<?= $e($c['billing_country'] ?: 'United Kingdom') ?>"></div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:12px;">Delivery Address</div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px;">
                    <input type="checkbox" name="delivery_same_as_billing" id="editSameAsBilling" value="1"
                           <?= $sameDelivery ? 'checked' : '' ?> onchange="toggleEditDelivery(this)">
                    Same as billing address
                </label>
                <div id="editDeliveryFields" <?= $sameDelivery ? 'style="display:none;"' : '' ?>>
                    <div class="form-group"><label>Address Line 1</label><input type="text" name="delivery_address_1" value="<?= $e($c['delivery_address_1']) ?>"></div>
                    <div class="form-group"><label>Address Line 2</label><input type="text" name="delivery_address_2" value="<?= $e($c['delivery_address_2']) ?>"></div>
                    <div class="form-row">
                        <div class="form-group"><label>City</label><input type="text" name="delivery_city" value="<?= $e($c['delivery_city']) ?>"></div>
                        <div class="form-group"><label>County</label><input type="text" name="delivery_county" value="<?= $e($c['delivery_county']) ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="max-width:160px;"><label>Postcode</label><input type="text" name="delivery_postcode" value="<?= $e($c['delivery_postcode']) ?>"></div>
                        <div class="form-group"><label>Country</label><input type="text" name="delivery_country" value="<?= $e($c['delivery_country'] ?: 'United Kingdom') ?>"></div>
                    </div>
                </div>
            </div>

            <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Recent Tickets + Orders side by side -->
<div class="grid-2 mb-4">
    <div class="card p-0">
        <div class="card-header" style="padding:16px 20px;">
            <h2>Recent Tickets</h2>
            <a href="/admin/tickets" class="btn btn-sm btn-outline">All</a>
        </div>
        <?php if (empty($tickets)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:14px;">No tickets</div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Ref</th><th>Subject</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($tickets, 0, 5) as $t): ?>
            <tr onclick="location.href='/admin/tickets/<?= (int)$t['id'] ?>'" style="cursor:pointer;">
                <td><code><?= $e($t['reference']) ?></code></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $e($t['subject']) ?></td>
                <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="card p-0">
        <div class="card-header" style="padding:16px 20px;">
            <h2>Recent Orders</h2>
            <a href="/admin/shop/orders?user_id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline">All</a>
        </div>
        <?php
        $customerOrders = array_slice(\App\Models\ShopOrder::forUser((int)$c['id']), 0, 5);
        $symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
        ?>
        <?php if (empty($customerOrders)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:14px;">No orders</div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Reference</th><th>Status</th><th style="text-align:right;">Total</th></tr></thead>
            <tbody>
            <?php foreach ($customerOrders as $o): ?>
            <tr onclick="location.href='/admin/shop/orders/<?= (int)$o['id'] ?>'" style="cursor:pointer;">
                <td><strong><?= $e($o['reference']) ?></strong><div style="font-size:12px;color:var(--text-muted);"><?= date('j M Y', strtotime($o['created_at'])) ?></div></td>
                <td><span class="badge badge-<?= \App\Models\ShopOrder::statusClass($o['status']) ?>"><?= \App\Models\ShopOrder::statusLabel($o['status']) ?></span></td>
                <td style="text-align:right;font-weight:600;"><?= $symbol ?><?= number_format((float)$o['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Invoices -->
<?php if (!empty($invoices)): ?>
<div class="card p-0 mb-4">
    <div class="card-header" style="padding:16px 20px;">
        <h2>Invoices</h2>
        <a href="/admin/invoices/create?customer=<?= (int)$c['id'] ?>" class="btn btn-sm btn-primary">+ New Invoice</a>
    </div>
    <table class="table">
        <thead><tr><th>Invoice</th><th>Total</th><th>Due</th><th>Status</th><th>Due Date</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr onclick="location.href='/admin/invoices'" style="cursor:pointer;">
            <td><?= $e($inv['invoice_number']) ?></td>
            <td>£<?= number_format((float)$inv['total'], 2) ?></td>
            <td>£<?= number_format((float)$inv['amount_due'], 2) ?></td>
            <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td class="text-muted"><?= $inv['due_date'] ? date('j M Y', strtotime($inv['due_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
function toggleEdit() {
    const vm = document.getElementById('view-mode');
    const em = document.getElementById('edit-mode');
    vm.style.display = vm.style.display === 'none' ? '' : 'none';
    em.style.display = em.style.display === 'none' ? '' : 'none';
}
function toggleEditDelivery(cb) {
    document.getElementById('editDeliveryFields').style.display = cb.checked ? 'none' : '';
}
</script>
