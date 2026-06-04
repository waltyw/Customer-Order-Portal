<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <h1>Xero Integration</h1>
        <p>Sync invoices directly from your Xero account</p>
    </div>
    <?php if ($connected): ?>
    <form method="POST" action="/admin/xero/sync">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
            Sync Invoices Now
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Status card -->
<div class="card" style="margin-bottom:24px;border-color:<?= $connected ? '#bbf7d0' : '#e2e8f0' ?>;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:12px;background:<?= $connected ? '#f0fdf4' : '#f8fafc' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php if ($connected): ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:15px;color:<?= $connected ? '#16a34a' : '#64748b' ?>;">
                    <?= $connected ? 'Connected to Xero' : 'Not Connected' ?>
                </div>
                <?php if ($connected && $tenantName): ?>
                <div style="font-size:13px;color:#64748b;">Organisation: <strong><?= Security::e($tenantName) ?></strong></div>
                <?php elseif (!$connected): ?>
                <div style="font-size:13px;color:#94a3b8;">Enter your Xero app credentials below and click Connect</div>
                <?php endif; ?>
                <?php
                $lastSync = \App\Models\Setting::get('xero_last_sync');
                if ($connected && $lastSync):
                ?>
                <div style="font-size:12px;color:#94a3b8;">Last synced: <?= date('j M Y, H:i', strtotime($lastSync)) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($connected): ?>
        <form method="POST" action="/admin/xero/disconnect" onsubmit="return confirm('Disconnect from Xero? Existing invoices will remain.')">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-danger-outline btn-sm">Disconnect</button>
        </form>
        <?php else: ?>
        <a href="/admin/xero/connect" class="btn btn-primary">
            Connect to Xero →
        </a>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- Credentials form -->
    <div class="card">
        <div class="card-header"><h2>Xero App Credentials</h2></div>
        <div class="card-body">
            <form method="POST" action="/admin/xero/config">
                <?= Security::csrfField() ?>
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" name="xero_client_id" value="<?= Security::e($clientId) ?>" placeholder="Paste your Xero Client ID">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" name="xero_client_secret" placeholder="Paste your Xero Client Secret" autocomplete="new-password">
                    <small style="color:#64748b;font-size:12px;">Leave blank to keep the existing secret.</small>
                </div>
                <div class="form-group">
                    <label>Redirect URI <span class="hint">(copy this into Xero)</span></label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" value="<?= Security::e($redirectUri) ?>" readonly style="background:#f8fafc;color:#64748b;" id="redirect-uri">
                        <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('redirect-uri').value);this.textContent='Copied!'">Copy</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Credentials</button>
            </form>
        </div>
    </div>

    <!-- Setup guide -->
    <div class="card">
        <div class="card-header"><h2>How to set up</h2></div>
        <div class="card-body">
            <ol style="margin-left:18px;font-size:14px;line-height:2.2;color:#374151;">
                <li>Go to <a href="https://developer.xero.com/app/manage" target="_blank" rel="noopener"><strong>developer.xero.com/app/manage</strong></a></li>
                <li>Click <strong>New app</strong></li>
                <li>Choose <strong>Web app</strong></li>
                <li>Enter any app name (e.g. <em>Beebizzi Portal</em>)</li>
                <li>Paste the <strong>Redirect URI</strong> (from left) into Xero</li>
                <li>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> from Xero into the form on the left</li>
                <li>Click <strong>Save Credentials</strong></li>
                <li>Click <strong>Connect to Xero →</strong></li>
                <li>Log in to Xero and approve access</li>
                <li>Click <strong>Sync Invoices Now</strong></li>
            </ol>
            <div class="alert alert-info" style="margin-top:16px;">
                Invoices are matched to customers by <strong>email address</strong>. Make sure each customer's email in the portal matches their Xero contact email.
            </div>
        </div>
    </div>

</div>

<!-- Sync info -->
<?php if ($connected): ?>
<div class="card" style="margin-top:0;">
    <div class="card-header"><h2>What gets synced</h2></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;font-size:13px;">
            <div style="background:#f0fdf4;border-radius:8px;padding:14px;">
                <div style="font-weight:600;color:#16a34a;margin-bottom:4px;">✅ Imported</div>
                <div style="color:#374151;">Sales invoices (Accounts Receivable) with status Authorised, Paid or Voided</div>
            </div>
            <div style="background:#fffbeb;border-radius:8px;padding:14px;">
                <div style="font-weight:600;color:#d97706;margin-bottom:4px;">⚠️ Skipped</div>
                <div style="color:#374151;">Invoices where the Xero contact email doesn't match a customer in the portal</div>
            </div>
            <div style="background:#eff6ff;border-radius:8px;padding:14px;">
                <div style="font-weight:600;color:#2563eb;margin-bottom:4px;">🔄 Updated</div>
                <div style="color:#374151;">Previously synced invoices — status and payment amounts are refreshed</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
