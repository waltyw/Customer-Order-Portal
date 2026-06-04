<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <a href="/admin/customers" style="font-size:14px;color:#64748b;">&larr; All Customers</a>
        <h1 style="margin-top:4px;">Import Customers</h1>
    </div>
    <a href="/admin/customers/template" class="btn btn-outline">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download CSV Template
    </a>
</div>

<?php if (!empty($_SESSION['import_result'])): ?>
<?php $result = $_SESSION['import_result']; unset($_SESSION['import_result']); ?>
<div class="card mb-4" style="border-color:<?= $result['imported'] > 0 ? '#bbf7d0' : '#fecaca' ?>;">
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:<?= !empty($result['errors']) ? '20px' : '0' ?>;">
            <div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#16a34a;"><?= (int)$result['imported'] ?></div>
                <div style="font-size:13px;color:#64748b;">Imported</div>
            </div>
            <div style="text-align:center;padding:16px;background:#fffbeb;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#d97706;"><?= (int)$result['skipped'] ?></div>
                <div style="font-size:13px;color:#64748b;">Already Existed</div>
            </div>
            <div style="text-align:center;padding:16px;background:#fef2f2;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#dc2626;"><?= count($result['errors']) ?></div>
                <div style="font-size:13px;color:#64748b;">Errors</div>
            </div>
        </div>
        <?php if (!empty($result['errors'])): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-top:16px;">
            <div style="font-weight:600;font-size:13px;color:#991b1b;margin-bottom:8px;">Issues:</div>
            <?php foreach ($result['errors'] as $err): ?>
            <div style="font-size:13px;color:#991b1b;margin-bottom:4px;">• <?= Security::e($err) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- Upload form -->
    <div class="card">
        <div class="card-header"><h2>Upload CSV File</h2></div>
        <div class="card-body">
            <form method="POST" action="/admin/customers/import" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <div class="form-group">
                    <label>CSV File <span class="required">*</span></label>
                    <input type="file" name="csv" accept=".csv,text/csv" required>
                    <small style="color:#64748b;font-size:12px;display:block;margin-top:4px;">
                        Download the template above to get the correct column order.
                    </small>
                </div>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:20px;">
                    <input type="checkbox" name="send_emails" value="1" checked style="width:16px;height:16px;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Send welcome emails</div>
                        <div style="font-size:12px;color:#64748b;">Each customer receives login details by email</div>
                    </div>
                </label>
                <button type="submit" class="btn btn-primary" style="width:100%;">Import Customers</button>
            </form>
        </div>
    </div>

    <!-- Column reference -->
    <div class="card">
        <div class="card-header"><h2>CSV Columns</h2></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table" style="font-size:13px;">
                <thead>
                    <tr><th>Column</th><th style="width:70px;text-align:center;">Required</th><th>Example</th></tr>
                </thead>
                <tbody>
                    <tr style="background:#fafafa;">
                        <td colspan="3" style="padding:6px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">Account</td>
                    </tr>
                    <tr><td><code>name</code></td><td style="text-align:center;color:var(--success);font-weight:700;">Yes</td><td style="color:var(--text-muted);">Jane Smith</td></tr>
                    <tr><td><code>email</code></td><td style="text-align:center;color:var(--success);font-weight:700;">Yes</td><td style="color:var(--text-muted);">jane@smithltd.co.uk</td></tr>
                    <tr><td><code>company</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Smith Ltd</td></tr>
                    <tr><td><code>phone</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">07700 000001</td></tr>
                    <tr><td><code>branch_number</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">BR001</td></tr>

                    <tr style="background:#fafafa;">
                        <td colspan="3" style="padding:6px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">Billing Address</td>
                    </tr>
                    <tr><td><code>billing_address_1</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">1 High Street</td></tr>
                    <tr><td><code>billing_address_2</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Unit 5</td></tr>
                    <tr><td><code>billing_city</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Lincoln</td></tr>
                    <tr><td><code>billing_county</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Lincolnshire</td></tr>
                    <tr><td><code>billing_postcode</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">LN1 1AA</td></tr>
                    <tr><td><code>billing_country</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">United Kingdom</td></tr>

                    <tr style="background:#fafafa;">
                        <td colspan="3" style="padding:6px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">Delivery Address</td>
                    </tr>
                    <tr><td><code>delivery_same_as_billing</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">1 (yes) or 0 (no)</td></tr>
                    <tr><td><code>delivery_address_1</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">5 Depot Lane</td></tr>
                    <tr><td><code>delivery_address_2</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);"></td></tr>
                    <tr><td><code>delivery_city</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Derby</td></tr>
                    <tr><td><code>delivery_county</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">Derbyshire</td></tr>
                    <tr><td><code>delivery_postcode</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">DE1 1CC</td></tr>
                    <tr><td><code>delivery_country</code></td><td style="text-align:center;color:var(--text-muted);">No</td><td style="color:var(--text-muted);">United Kingdom</td></tr>
                </tbody>
            </table>
        </div>
        <div style="padding:14px 16px;border-top:1px solid var(--border);font-size:13px;color:var(--text-muted);background:#f8fafc;border-radius:0 0 var(--radius) var(--radius);">
            <strong style="color:var(--text);">Tips:</strong>
            Customers with an existing email are skipped. Leave <code>delivery_same_as_billing</code> as <strong>1</strong> if delivery matches billing.
            Also accepts WooCommerce customer export columns (<code>billing_address 1</code>, <code>billing_state</code>, <code>shipping_postcode</code> etc.).
        </div>
    </div>

</div>
