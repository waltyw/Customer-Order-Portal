<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Support Tickets</h1>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" action="/admin/tickets" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['open','in_progress','waiting_customer','resolved','closed'] as $s): ?>
            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            <?php foreach (['low','medium','high','urgent'] as $p): ?>
            <option value="<?= $p ?>" <?= ($filters['priority'] ?? '') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($filters)): ?>
        <a href="/admin/tickets" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tickets)): ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No tickets found.</td></tr>
        <?php else: ?>
        <?php foreach ($tickets as $t): ?>
        <tr onclick="location.href='/admin/tickets/<?= $t['id'] ?>'" style="cursor:pointer;">
            <td><code><?= Security::e($t['reference']) ?></code></td>
            <td><?= Security::e($t['customer_name']) ?></td>
            <td><?= Security::e($t['subject']) ?></td>
            <td><span class="badge badge-neutral"><?= ucfirst($t['category']) ?></span></td>
            <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
            <td class="text-muted"><?= date('j M, H:i', strtotime($t['updated_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
