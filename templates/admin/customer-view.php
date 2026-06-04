<?php
use App\Core\Security;
$websites = $websites ?? [];
$customerWebsiteUrl = $customer['website_url'] ?? null;
?>
<div class="page-header">
    <div>
        <a href="/admin/customers" style="font-size:14px;color:#64748b;">&larr; All Customers</a>
        <h1 style="margin-top:4px;"><?= Security::e($customer['name']) ?></h1>
        <div style="color:#64748b;margin-top:4px;"><?= Security::e($customer['email']) ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <form method="POST" action="/admin/customers/<?= $customer['id'] ?>/toggle-invoices">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-outline btn-sm" title="Toggle invoice visibility for this customer">
                <?php $showInv = $customer['show_invoices'] ?? 1; ?>
                <?= $showInv ? '🧾 Hide Invoices' : '🧾 Show Invoices' ?>
            </button>
        </form>
        <form method="POST" action="/admin/customers/<?= $customer['id'] ?>/toggle">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn <?= $customer['is_active'] ? 'btn-danger-outline' : 'btn-outline' ?>">
                <?= $customer['is_active'] ? 'Deactivate Account' : 'Activate Account' ?>
            </button>
        </form>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= (int)$stats['ticket_count'] ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value"><?= (int)$stats['invoice_count'] ?></div>
            <div class="stat-label">Unpaid Invoices</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-value" style="color:#dc2626;">£<?= number_format($stats['amount_outstanding'], 2) ?></div>
            <div class="stat-label">Outstanding</div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h2>Customer Details</h2>
            <button class="btn btn-sm btn-outline" onclick="toggleEdit()">Edit</button>
        </div>

        <!-- View mode -->
        <div id="view-details" class="card-body">
            <table style="width:100%;font-size:14px;">
                <tr><td style="color:#64748b;padding:6px 0;width:40%;">Name</td><td><?= Security::e($customer['name']) ?></td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Company</td><td><?= Security::e($customer['company'] ?? '—') ?></td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Phone</td><td><?= Security::e($customer['phone'] ?? '—') ?></td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Website</td><td>
                    <?php if ($customerWebsiteUrl): ?>
                        <a href="<?= Security::e($customerWebsiteUrl) ?>" target="_blank" rel="noopener"><?= Security::e($customerWebsiteUrl) ?></a>
                    <?php else: ?><span style="color:#94a3b8;">Not set</span><?php endif; ?>
                </td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Mail Server</td><td>
                    <?php $ms = \App\Models\User::mailServer($customerWebsiteUrl); ?>
                    <?= $ms ? Security::e($ms) : '<span style="color:#94a3b8;">—</span>' ?>
                </td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Account Status</td><td><span class="badge <?= $customer['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $customer['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Invoices</td><td><span class="badge <?= ($customer['show_invoices'] ?? 1) ? 'badge-active' : 'badge-inactive' ?>"><?= ($customer['show_invoices'] ?? 1) ? 'Visible to customer' : 'Hidden from customer' ?></span></td></tr>
                <tr><td style="color:#64748b;padding:6px 0;">Member Since</td><td><?= date('j F Y', strtotime($customer['created_at'])) ?></td></tr>
            </table>
        </div>

        <!-- Edit mode -->
        <div id="edit-details" style="display:none;">
            <form method="POST" action="/admin/customers/<?= $customer['id'] ?>/update">
                <?= Security::csrfField() ?>
                <div class="card-body" style="padding-bottom:0;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required value="<?= Security::e($customer['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Company</label>
                            <input type="text" name="company" value="<?= Security::e($customer['company'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?= Security::e($customer['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Website URL</label>
                            <input type="text" name="website_url" value="<?= Security::e($customerWebsiteUrl ?? '') ?>" placeholder="https://theirdomain.co.uk">
                        </div>
                    </div>
                </div>
                <div style="padding:12px 20px;border-top:1px solid #f1f5f9;display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card p-0">
        <div class="card-header" style="padding:16px 20px;"><h2>Recent Tickets</h2><a href="/admin/customers/<?= $customer['id'] ?>" class="btn btn-sm btn-primary" style="margin-left:auto;">+ New Ticket</a></div>
        <?php if (empty($tickets)): ?>
        <div class="empty-state">No tickets</div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Ref</th><th>Subject</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($tickets, 0, 5) as $t): ?>
            <tr onclick="location.href='/admin/tickets/<?= $t['id'] ?>'" style="cursor:pointer;">
                <td><code><?= Security::e($t['reference']) ?></code></td>
                <td><?= Security::e($t['subject']) ?></td>
                <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Websites -->
<div class="card" style="margin-top:16px;">
    <div class="card-header"><h2>Websites</h2></div>
    <div class="card-body">
        <?php if (!empty($websites)): ?>
        <div style="margin-bottom:16px;">
            <?php foreach ($websites as $site): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;">
                <div>
                    <a href="<?= Security::e($site['url']) ?>" target="_blank" rel="noopener" style="font-weight:500;font-size:14px;"><?= Security::e($site['url']) ?></a>
                    <?php if ($site['label']): ?>
                    <span style="font-size:12px;color:#64748b;margin-left:8px;"><?= Security::e($site['label']) ?></span>
                    <?php endif; ?>
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Mail: <?= Security::e(\App\Models\User::mailServer($site['url'])) ?></div>
                </div>
                <form method="POST" action="/admin/customers/<?= $customer['id'] ?>/remove-website/<?= $site['id'] ?>" onsubmit="return confirm('Remove this website?')">
                    <?= Security::csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Remove</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:#94a3b8;font-size:13px;margin-bottom:16px;">No websites added yet.</p>
        <?php endif; ?>

        <form method="POST" action="/admin/customers/<?= $customer['id'] ?>/add-website" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            <?= Security::csrfField() ?>
            <div class="form-group" style="flex:2;min-width:200px;margin-bottom:0;">
                <label>Website URL</label>
                <input type="text" name="url" placeholder="https://theirdomain.co.uk" required>
            </div>
            <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0;">
                <label>Label <span class="hint">(optional)</span></label>
                <input type="text" name="label" placeholder="e.g. Main Site">
            </div>
            <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Add</button>
        </form>
    </div>
</div>

<script>
function toggleEdit() {
    const view = document.getElementById('view-details');
    const edit = document.getElementById('edit-details');
    view.style.display = view.style.display === 'none' ? '' : 'none';
    edit.style.display = edit.style.display === 'none' ? '' : 'none';
}
</script>

<!-- Invoices -->
<div class="card p-0" style="margin-top:16px;">
    <div class="card-header" style="padding:16px 20px;">
        <h2>Invoices</h2>
        <a href="/admin/invoices/create?customer=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">+ New Invoice</a>
    </div>
    <?php if (empty($invoices)): ?>
    <div class="empty-state">No invoices</div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Invoice</th><th>Total</th><th>Due</th><th>Status</th><th>Due Date</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr onclick="location.href='/admin/invoices'" style="cursor:pointer;">
            <td><?= Security::e($inv['invoice_number']) ?></td>
            <td>£<?= number_format((float)$inv['total'], 2) ?></td>
            <td>£<?= number_format((float)$inv['amount_due'], 2) ?></td>
            <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td class="text-muted"><?= $inv['due_date'] ? date('j M Y', strtotime($inv['due_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
