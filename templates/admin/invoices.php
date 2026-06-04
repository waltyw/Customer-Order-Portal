<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Invoices</h1>
    <a href="/admin/invoices/create" class="btn btn-primary">Create Invoice</a>
</div>

<div class="card p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Amount Due</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Issue Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($invoices)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No invoices yet. <a href="/admin/invoices/create">Create one</a>.</td></tr>
        <?php else: ?>
        <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><?= Security::e($inv['invoice_number']) ?></td>
            <td><a href="/admin/customers/<?= $inv['user_id'] ?>"><?= Security::e($inv['customer_name']) ?></a></td>
            <td>£<?= number_format((float)$inv['total'], 2) ?></td>
            <td><strong <?= $inv['status'] === 'overdue' ? 'style="color:#dc2626"' : '' ?>>£<?= number_format((float)$inv['amount_due'], 2) ?></strong></td>
            <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td class="text-muted"><?= $inv['due_date'] ? date('j M Y', strtotime($inv['due_date'])) : '—' ?></td>
            <td class="text-muted"><?= $inv['issue_date'] ? date('j M Y', strtotime($inv['issue_date'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
