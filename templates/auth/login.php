<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= \App\Core\Security::e($_ENV['APP_NAME'] ?? 'Customer Portal') ?></title>
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
            <h1><?= \App\Core\Security::e($appName) ?></h1>
            <?php endif; ?>
            <p>Sign in to your account</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= \App\Core\Security::e($flash['type']) ?>" style="margin-bottom:20px;">
            <?= \App\Core\Security::e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form">
            <?= \App\Core\Security::csrfField() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus
                    value="<?= \App\Core\Security::e($_POST['email'] ?? '') ?>"
                    placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label for="password">
                    Password
                    <a href="/forgot-password" class="label-link">Forgot password?</a>
                </label>
                <input type="password" id="password" name="password" required placeholder="••••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
    </div>
    <p class="auth-footer">
        Need help? <a href="mailto:<?= \App\Core\Security::e($_ENV['SMTP_FROM_EMAIL'] ?? '') ?>">Contact support</a>
    </p>
</div>
</body>
</html>
