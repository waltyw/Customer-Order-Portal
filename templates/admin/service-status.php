<?php
use App\Core\Security;
use App\Models\ServiceStatus;

$overall = ServiceStatus::overallStatus($services);
$colours = [
    'operational' => ['#f0fdf4','#16a34a'],
    'degraded'    => ['#fffbeb','#d97706'],
    'outage'      => ['#fef2f2','#dc2626'],
    'maintenance' => ['#eff6ff','#2563eb'],
];
?>
<div class="page-header">
    <h1>Service Status</h1>
    <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:13px;color:#64748b;">Customers see this on their Help page</span>
        <span style="background:<?= $colours[$overall][0] ?>;color:<?= $colours[$overall][1] ?>;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:600;">
            <?= ServiceStatus::statusLabel($overall) ?>
        </span>
    </div>
</div>

<!-- Current services -->
<div class="card p-0" style="margin-bottom:24px;">
    <div class="card-header" style="padding:16px 20px;"><h2>Current Status</h2></div>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Status</th>
                <th>Message</th>
                <th>Last Updated</th>
                <th style="width:160px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($services as $svc): ?>
        <tr>
            <td style="font-weight:600;"><?= Security::e($svc['service_name']) ?></td>
            <td>
                <span style="background:<?= ServiceStatus::statusBg($svc['status']) ?>;color:<?= ServiceStatus::statusColour($svc['status']) ?>;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                    <?= ServiceStatus::statusLabel($svc['status']) ?>
                </span>
            </td>
            <td style="color:#64748b;font-size:13px;"><?= $svc['message'] ? Security::e($svc['message']) : '—' ?></td>
            <td style="color:#94a3b8;font-size:12px;">
                <?= date('j M, H:i', strtotime($svc['updated_at'])) ?>
                <?php if ($svc['updated_by_name']): ?>
                    <span style="color:#cbd5e1;">by <?= Security::e($svc['updated_by_name']) ?></span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline" onclick="openEdit(<?= $svc['id'] ?>, '<?= Security::e($svc['service_name']) ?>', '<?= $svc['status'] ?>', <?= json_encode($svc['message'] ?? '') ?>)">
                    Update
                </button>
                <form method="POST" action="/admin/service-status/<?= $svc['id'] ?>/delete" style="display:inline;" onsubmit="return confirm('Remove this service?')">
                    <?= Security::csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-danger-outline">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($services)): ?>
        <tr><td colspan="5" class="empty-state">No services yet — add one below.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add new service -->
<div class="card" style="max-width:640px;">
    <div class="card-header"><h2>Add a Service</h2></div>
    <div class="card-body">
        <form method="POST" action="/admin/service-status/add">
            <?= Security::csrfField() ?>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Service Name</label>
                    <input type="text" name="service_name" placeholder="e.g. Web Hosting" required>
                </div>
                <div class="form-group">
                    <label>Initial Status</label>
                    <select name="status">
                        <option value="operational">Operational</option>
                        <option value="degraded">Degraded</option>
                        <option value="outage">Outage</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Message <span class="hint">(optional)</span></label>
                <input type="text" name="message" placeholder="Brief description for customers">
            </div>
            <button type="submit" class="btn btn-primary">Add Service</button>
        </form>
    </div>
</div>

<!-- Edit modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:480px;max-width:90vw;box-shadow:0 20px 40px rgba(0,0,0,.2);">
        <h2 style="font-size:16px;font-weight:700;margin-bottom:20px;" id="modal-title">Update Service</h2>
        <form method="POST" id="edit-form">
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="modal-status" style="width:100%;">
                    <option value="operational">✅ Operational</option>
                    <option value="degraded">⚠️ Degraded Performance</option>
                    <option value="outage">🔴 Service Outage</option>
                    <option value="maintenance">🔧 Planned Maintenance</option>
                </select>
            </div>
            <div class="form-group">
                <label>Message for customers <span class="hint">(optional)</span></label>
                <textarea name="message" id="modal-message" rows="3" placeholder="e.g. We are investigating reports of slow email delivery..."></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, status, message) {
    document.getElementById('modal-title').textContent = 'Update: ' + name;
    document.getElementById('edit-form').action = '/admin/service-status/' + id + '/update';
    document.getElementById('modal-status').value = status;
    document.getElementById('modal-message').value = message || '';
    const modal = document.getElementById('edit-modal');
    modal.style.display = 'flex';
}
function closeEdit() {
    document.getElementById('edit-modal').style.display = 'none';
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
