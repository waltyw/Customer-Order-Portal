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
<div class="card" style="margin-bottom:20px;border-color:<?= $result['imported'] > 0 ? '#bbf7d0' : '#fecaca' ?>;">
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:<?= !empty($result['errors']) ? '20px' : '0' ?>;">
            <div style="text-align:center;padding:16px;background:#f0fdf4;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#16a34a;"><?= $result['imported'] ?></div>
                <div style="font-size:13px;color:#64748b;">Imported</div>
            </div>
            <div style="text-align:center;padding:16px;background:#fffbeb;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#d97706;"><?= $result['skipped'] ?></div>
                <div style="font-size:13px;color:#64748b;">Already Existed</div>
            </div>
            <div style="text-align:center;padding:16px;background:#fef2f2;border-radius:8px;">
                <div style="font-size:32px;font-weight:700;color:#dc2626;"><?= count($result['errors']) ?></div>
                <div style="font-size:13px;color:#64748b;">Errors</div>
            </div>
        </div>
        <?php if (!empty($result['errors'])): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;">
            <div style="font-weight:600;font-size:13px;color:#991b1b;margin-bottom:8px;">Issues found:</div>
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
                        Use the template (download above) to ensure correct column order.
                    </small>
                </div>

                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:20px;">
                    <input type="checkbox" name="send_emails" value="1" checked style="width:16px;height:16px;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Send welcome emails</div>
                        <div style="font-size:12px;color:#64748b;">Each customer receives their login details by email</div>
                    </div>
                </label>

                <button type="submit" class="btn btn-primary btn-block">Import Customers</button>
            </form>
        </div>
    </div>

    <!-- Instructions -->
    <div class="card">
        <div class="card-header"><h2>CSV Format</h2></div>
        <div class="card-body">
            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Download the template above and open it in Excel or Google Sheets. Fill in your customers and save as CSV.</p>

            <table class="table" style="margin-bottom:16px;">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Required</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>name</code></td><td><span style="color:#16a34a;font-weight:600;">Yes</span></td><td>Jane Smith</td></tr>
                    <tr><td><code>email</code></td><td><span style="color:#16a34a;font-weight:600;">Yes</span></td><td>jane@smithltd.co.uk</td></tr>
                    <tr><td><code>company</code></td><td style="color:#94a3b8;">No</td><td>Smith Ltd</td></tr>
                    <tr><td><code>phone</code></td><td style="color:#94a3b8;">No</td><td>07700 000001</td></tr>
                    <tr><td><code>website_url</code></td><td style="color:#94a3b8;">No</td><td>smithltd.co.uk</td></tr>
                </tbody>
            </table>

            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:13px;color:#1e40af;">
                <strong>Notes:</strong>
                <ul style="margin:6px 0 0 16px;line-height:1.8;">
                    <li>Customers with an existing email address are skipped</li>
                    <li>A random password is generated for each customer</li>
                    <li>Welcome emails include their login link and temporary password</li>
                    <li>The first row must be the header row</li>
                </ul>
            </div>
        </div>
    </div>

</div>
