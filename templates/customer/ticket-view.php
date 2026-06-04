<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <a href="/tickets" style="font-size:14px;color:#64748b;">&larr; Back to Tickets</a>
        <h1 style="margin-top:4px;"><?= Security::e($ticket['subject']) ?></h1>
        <div style="display:flex;gap:8px;margin-top:6px;align-items:center;">
            <code style="font-size:13px;color:#64748b;"><?= Security::e($ticket['reference']) ?></code>
            <span class="badge badge-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
            <span class="badge badge-priority-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
            <span class="badge badge-neutral"><?= ucfirst($ticket['category']) ?></span>
        </div>
    </div>
</div>

<!-- Message thread -->
<div class="ticket-thread">
    <?php foreach ($messages as $msg): ?>
    <?php $isAdmin = $msg['sender_role'] === 'admin'; ?>
    <div class="ticket-message <?= $isAdmin ? 'message-admin' : 'message-customer' ?>">
        <div class="message-avatar"><?= strtoupper(substr($msg['sender_name'], 0, 1)) ?></div>
        <div class="message-body">
            <div class="message-header">
                <strong><?= Security::e($msg['sender_name']) ?></strong>
                <?php if ($isAdmin): ?><span class="badge-support">Support</span><?php endif; ?>
                <span class="message-time"><?= date('j M Y, H:i', strtotime($msg['created_at'])) ?></span>
            </div>
            <div class="message-text"><?= nl2br(Security::e($msg['message'])) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Reply form -->
<?php if (!in_array($ticket['status'], ['closed'])): ?>
<div class="card" style="margin-top:24px;max-width:760px;">
    <div class="card-header"><h2>Reply</h2></div>
    <div class="card-body">
        <form method="POST" action="/tickets/<?= $ticket['id'] ?>/reply" enctype="multipart/form-data">
            <?= Security::csrfField() ?>
            <div class="form-group">
                <textarea name="message" required rows="5" placeholder="Type your reply..."></textarea>
            </div>
            <div class="form-group">
                <label for="attachment">Attachment <span class="hint">(optional, max 5MB)</span></label>
                <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
            </div>
            <button type="submit" class="btn btn-primary">Send Reply</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info" style="margin-top:16px;">This ticket is closed. Please <a href="/tickets/create">open a new ticket</a> if you need further help.</div>
<?php endif; ?>
