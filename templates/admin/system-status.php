<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <a href="/admin" style="font-size:14px;color:#64748b;">&larr; Dashboard</a>
        <h1 style="margin-top:4px;">System Status</h1>
    </div>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $labels = [
                'database'            => 'Database',
                'storage_attachments' => 'Attachments folder',
                'storage_logs'        => 'Logs folder',
                'php'                 => 'PHP Version',
                'opcache'             => 'OPcache',
                'smtp'                => 'SMTP Email',
                'stripe'              => 'Stripe Payments',
            ];
            foreach ($status as $key => $s):
                $icon = $s['ok']
                    ? '<span style="color:#16a34a;font-size:16px;">&#10003;</span>'
                    : '<span style="color:#dc2626;font-size:16px;">&#10007;</span>';
            ?>
            <tr>
                <td style="font-weight:500;"><?= $labels[$key] ?? $key ?></td>
                <td><?= $icon ?></td>
                <td style="color:#64748b;font-size:13px;"><?= Security::e($s['msg']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="max-width:640px;">
    <div class="card">
        <div class="card-body">
            <h3 style="font-size:14px;font-weight:600;margin-bottom:10px;">PHP Info</h3>
            <table style="font-size:13px;width:100%;">
                <tr><td style="color:#64748b;padding:4px 0;width:50%;">PHP Version</td><td><?= PHP_VERSION ?></td></tr>
                <tr><td style="color:#64748b;padding:4px 0;">Max Upload Size</td><td><?= ini_get('upload_max_filesize') ?></td></tr>
                <tr><td style="color:#64748b;padding:4px 0;">Max POST Size</td><td><?= ini_get('post_max_size') ?></td></tr>
                <tr><td style="color:#64748b;padding:4px 0;">Memory Limit</td><td><?= ini_get('memory_limit') ?></td></tr>
                <tr><td style="color:#64748b;padding:4px 0;">Timezone</td><td><?= date_default_timezone_get() ?></td></tr>
                <tr><td style="color:#64748b;padding:4px 0;">Server Time</td><td><?= date('j F Y, H:i:s') ?></td></tr>
            </table>
        </div>
    </div>
</div>
