<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
?>
<div class="page-header">
    <h1 class="page-title">Delivery Rules</h1>
</div>
<p style="color:var(--text-muted);margin-bottom:24px;font-size:14px;">
    Rules are evaluated top-to-bottom. Threshold rules (free over £X) take priority.
    Tier rules override the flat rate for specific customer tiers.
</p>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;">
    <!-- Rules list -->
    <div class="card">
        <div class="card-body" style="padding:0;">
            <?php if (empty($rules)): ?>
            <div class="empty-state" style="padding:32px;"><p style="color:var(--text-muted);">No delivery rules yet.</p></div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Name</th><th>Type</th><th>Rate / Threshold</th><th style="width:70px;text-align:center;">Active</th><th style="width:100px;"></th></tr></thead>
                <tbody>
                <?php foreach ($rules as $rule): ?>
                <tr>
                    <td><strong><?= $e($rule['name']) ?></strong></td>
                    <td>
                        <?php if ($rule['type'] === 'flat'): ?>
                        <span class="badge badge-outline">Flat rate</span>
                        <?php elseif ($rule['type'] === 'tier'): ?>
                        <span class="badge badge-outline">Tier: <?= $e($rule['tier_name'] ?? '?') ?></span>
                        <?php else: ?>
                        <span class="badge badge-outline">Threshold</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($rule['type'] === 'threshold'): ?>
                            Free over <?= $symbol ?><?= number_format((float)$rule['free_threshold'], 2) ?>
                        <?php elseif ($rule['flat_rate'] !== null): ?>
                            <?= $rule['flat_rate'] == 0 ? 'FREE' : $symbol . number_format((float)$rule['flat_rate'], 2) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><?= $rule['is_active'] ? '<span style="color:var(--success)">●</span>' : '<span style="color:var(--text-muted)">●</span>' ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button onclick="editRule(<?= htmlspecialchars(json_encode($rule)) ?>)" class="btn btn-sm btn-secondary">Edit</button>
                            <form method="post" action="/admin/shop/delivery/<?= (int)$rule['id'] ?>/delete" onsubmit="return confirm('Delete rule?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit form -->
    <div>
        <div class="card">
            <div class="card-header"><h3 class="card-title" id="ruleFormTitle">Add Delivery Rule</h3></div>
            <div class="card-body">
                <form method="post" id="ruleForm" action="/admin/shop/delivery/create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="ruleName" class="form-control" placeholder="e.g. Standard Delivery" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" id="ruleType" class="form-control" onchange="toggleRuleFields(this.value)">
                            <option value="flat">Flat rate (all customers)</option>
                            <option value="tier">Tier-specific rate</option>
                            <option value="threshold">Free over threshold</option>
                        </select>
                    </div>
                    <div class="form-group" id="tierFieldWrap" style="display:none;">
                        <label class="form-label">Pricing Tier</label>
                        <select name="pricing_tier_id" id="ruleTier" class="form-control">
                            <option value="">— Select tier —</option>
                            <?php foreach ($tiers as $tier): ?>
                            <option value="<?= (int)$tier['id'] ?>"><?= $e($tier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="flatRateWrap">
                        <label class="form-label">Flat Rate (<?= $symbol ?>)</label>
                        <input type="number" name="flat_rate" id="ruleFlatRate" step="0.01" min="0" class="form-control" placeholder="6.95">
                    </div>
                    <div class="form-group" id="thresholdWrap" style="display:none;">
                        <label class="form-label">Free Over (<?= $symbol ?>)</label>
                        <input type="number" name="free_threshold" id="ruleThreshold" step="0.01" min="0" class="form-control" placeholder="150.00">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="ruleSort" class="form-control" value="0">
                        </div>
                        <div class="form-group" style="display:flex;align-items:flex-end;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:6px;">
                                <input type="checkbox" name="is_active" id="ruleActive" value="1" checked> Active
                            </label>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary">Save Rule</button>
                        <button type="button" class="btn btn-secondary" onclick="resetRuleForm()">New</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRuleFields(type) {
    document.getElementById('tierFieldWrap').style.display  = type === 'tier'      ? '' : 'none';
    document.getElementById('flatRateWrap').style.display   = type === 'threshold' ? 'none' : '';
    document.getElementById('thresholdWrap').style.display  = type === 'threshold' ? '' : 'none';
}
function editRule(rule) {
    document.getElementById('ruleFormTitle').textContent = 'Edit Delivery Rule';
    document.getElementById('ruleForm').action = '/admin/shop/delivery/' + rule.id + '/update';
    document.getElementById('ruleName').value      = rule.name || '';
    document.getElementById('ruleType').value      = rule.type || 'flat';
    document.getElementById('ruleTier').value      = rule.pricing_tier_id || '';
    document.getElementById('ruleFlatRate').value  = rule.flat_rate || '';
    document.getElementById('ruleThreshold').value = rule.free_threshold || '';
    document.getElementById('ruleSort').value      = rule.sort_order || 0;
    document.getElementById('ruleActive').checked  = !!parseInt(rule.is_active);
    toggleRuleFields(rule.type);
}
function resetRuleForm() {
    document.getElementById('ruleFormTitle').textContent = 'Add Delivery Rule';
    document.getElementById('ruleForm').action = '/admin/shop/delivery/create';
    document.getElementById('ruleForm').reset();
    toggleRuleFields('flat');
}
</script>
