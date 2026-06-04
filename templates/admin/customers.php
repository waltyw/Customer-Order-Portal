<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Customers</h1>
    <div style="display:flex;gap:10px;">
        <a href="/admin/customers/import" class="btn btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import CSV
        </a>
        <a href="/admin/customers/create" class="btn btn-primary">Add Customer</a>
    </div>
</div>

<div class="card p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Joined</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($customers)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No customers yet. <a href="/admin/customers/create">Add the first one</a>.</td></tr>
        <?php else: ?>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><a href="/admin/customers/<?= $c['id'] ?>"><?= Security::e($c['name']) ?></a></td>
            <td><?= Security::e($c['email']) ?></td>
            <td><?= Security::e($c['company'] ?? '—') ?></td>
            <td><?= Security::e($c['phone'] ?? '—') ?></td>
            <td><span class="badge <?= $c['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-muted"><?= date('j M Y', strtotime($c['created_at'])) ?></td>
            <td><a href="/admin/customers/<?= $c['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
