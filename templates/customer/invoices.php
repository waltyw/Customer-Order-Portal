<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Invoices &amp; Payments</h1>
</div>

<?php if (empty($invoices)): ?>
<div class="card"><div class="empty-state large">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p>No invoices on your account yet.</p>
</div></div>
<?php else: ?>
<div class="card p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Total</th>
                <th>VAT</th>
                <th>Amount Due</th>
                <th>Status</th>
                <th>Due Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><a href="/invoices/<?= $inv['id'] ?>"><?= Security::e($inv['invoice_number']) ?></a></td>
            <td>£<?= number_format((float)$inv['total'], 2) ?></td>
            <td>£<?= number_format((float)$inv['vat_amount'], 2) ?></td>
            <td><strong>£<?= number_format((float)$inv['amount_due'], 2) ?></strong></td>
            <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td class="<?= $inv['status'] === 'overdue' ? 'text-danger' : 'text-muted' ?>">
                <?= $inv['due_date'] ? date('j M Y', strtotime($inv['due_date'])) : '—' ?>
            </td>
            <td>
                <?php if (!in_array($inv['status'], ['paid', 'voided']) && (float)$inv['amount_due'] > 0): ?>
                <a href="/invoices/<?= $inv['id'] ?>/pay" class="btn btn-sm btn-primary">Pay Now</a>
                <?php else: ?>
                <a href="/invoices/<?= $inv['id'] ?>" class="btn btn-sm btn-outline">View</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
