<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Support Tickets</h1>
    <a href="/tickets/create" class="btn btn-primary">New Ticket</a>
</div>

<?php if (empty($tickets)): ?>
<div class="card"><div class="empty-state large">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <p>No tickets yet.</p>
    <a href="/tickets/create" class="btn btn-primary">Raise a Support Ticket</a>
</div></div>
<?php else: ?>
<div class="card p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr onclick="location.href='/tickets/<?= $t['id'] ?>'" style="cursor:pointer;">
            <td><code><?= Security::e($t['reference']) ?></code></td>
            <td><?= Security::e($t['subject']) ?></td>
            <td><span class="badge badge-neutral"><?= ucfirst($t['category']) ?></span></td>
            <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
            <td class="text-muted"><?= date('j M Y, H:i', strtotime($t['updated_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
