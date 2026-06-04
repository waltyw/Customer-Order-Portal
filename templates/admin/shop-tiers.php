<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
?>
<div class="page-header">
    <h1 class="page-title">Pricing Tiers</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;margin-bottom:32px;">
    <!-- Tiers list -->
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Description</th><th style="width:60px;text-align:center;">Sort</th><th style="width:100px;"></th></tr></thead>
                <tbody>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td><strong><?= $e($tier['name']) ?></strong></td>
                    <td style="color:var(--text-muted);"><?= $e($tier['description'] ?? '—') ?></td>
                    <td style="text-align:center;"><?= (int)$tier['sort_order'] ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button onclick="editTier(<?= htmlspecialchars(json_encode($tier)) ?>)" class="btn btn-sm btn-secondary">Edit</button>
                            <form method="post" action="/admin/shop/tiers/<?= (int)$tier['id'] ?>/delete" onsubmit="return confirm('Delete tier? This will affect all customers on this tier.')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Tier -->
    <div>
        <div class="card">
            <div class="card-header"><h3 class="card-title" id="tierFormTitle">Add Tier</h3></div>
            <div class="card-body">
                <form method="post" id="tierForm" action="/admin/shop/tiers/create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label class="form-label">Tier Name</label>
                        <input type="text" name="name" id="tierName" class="form-control" placeholder="e.g. Gold" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" id="tierDesc" class="form-control" placeholder="Short description">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="tierSort" class="form-control" value="0">
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary">Save Tier</button>
                        <button type="button" class="btn btn-secondary" onclick="resetTierForm()">New</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assign Tiers to Customers -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Customer Tier Assignments</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-top:4px;">Assign each customer to a pricing tier and checkout method.</p>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Company</th><th>Current Tier</th><th>Checkout Method</th><th style="width:120px;"></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $cust): ?>
            <tr>
                <td>
                    <a href="/admin/customers/<?= (int)$cust['id'] ?>"><?= $e($cust['name']) ?></a>
                    <div style="font-size:12px;color:var(--text-muted);"><?= $e($cust['email']) ?></div>
                </td>
                <td style="color:var(--text-muted);"><?= $e($cust['company'] ?? '—') ?></td>
                <td>
                    <form method="post" action="/admin/shop/customers/<?= (int)$cust['id'] ?>/tier" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <select name="pricing_tier_id" class="form-control" style="max-width:140px;">
                            <option value="">No tier</option>
                            <?php foreach ($tiers as $tier): ?>
                            <option value="<?= (int)$tier['id'] ?>" <?= ($cust['pricing_tier_id'] ?? null) == $tier['id'] ? 'selected' : '' ?>>
                                <?= $e($tier['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                </td>
                <td>
                        <select name="checkout_method" class="form-control" style="max-width:110px;">
                            <option value="stripe" <?= ($cust['checkout_method'] ?? 'stripe') === 'stripe' ? 'selected' : '' ?>>Card (Stripe)</option>
                            <option value="po"     <?= ($cust['checkout_method'] ?? 'stripe') === 'po'     ? 'selected' : '' ?>>Purchase Order</option>
                        </select>
                </td>
                <td>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function editTier(tier) {
    document.getElementById('tierFormTitle').textContent = 'Edit Tier';
    document.getElementById('tierForm').action = '/admin/shop/tiers/' + tier.id + '/update';
    document.getElementById('tierName').value = tier.name || '';
    document.getElementById('tierDesc').value = tier.description || '';
    document.getElementById('tierSort').value = tier.sort_order || 0;
}
function resetTierForm() {
    document.getElementById('tierFormTitle').textContent = 'Add Tier';
    document.getElementById('tierForm').action = '/admin/shop/tiers/create';
    document.getElementById('tierForm').reset();
}
</script>
