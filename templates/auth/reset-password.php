<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= \App\Core\Security::e($_ENV['APP_NAME'] ?? 'Portal') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
<?php $flash = \App\Core\Security::getFlash(); ?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <?php
            $logoExt  = \App\Models\Setting::get('logo_ext') ?: 'png';
            $logoFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.' . $logoExt;
            $appName  = \App\Models\Setting::get('app_name') ?: 'Customer Portal';
            ?>
            <?php if (file_exists($logoFile)): ?>
            <img src="/assets/img/logo.<?= $logoExt ?>?v=<?= filemtime($logoFile) ?>" alt="<?= \App\Core\Security::e($appName) ?>" class="auth-logo-img">
            <?php else: ?>
            <div class="auth-logo"><?= \App\Core\Security::e(mb_substr($appName, 0, 1)) ?></div>
            <?php endif; ?>
            <h1>New Password</h1>
            <p>Choose a strong password (min. 10 characters)</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= \App\Core\Security::e($flash['type']) ?>" style="margin-bottom:20px;">
            <?= \App\Core\Security::e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/reset-password" class="auth-form">
            <?= \App\Core\Security::csrfField() ?>
            <input type="hidden" name="token" value="<?= \App\Core\Security::e($token ?? '') ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="10" autofocus placeholder="At least 10 characters">
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="10" placeholder="Repeat your password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Set New Password</button>
        </form>
    </div>
</div>
</body>
</html>
