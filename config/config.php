<?php

declare(strict_types=1);

// ── Autoloader (PSR-4 for App\ namespace, no Composer needed) ────────────────
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $rel  = str_replace(['App\\', '\\'], ['', '/'], $class);
    $file = dirname(__DIR__) . '/src/' . $rel . '.php';
    if (is_file($file)) require_once $file;
});

// ── PHPMailer (standalone — no Composer) ─────────────────────────────────────
require_once dirname(__DIR__) . '/vendor-standalone/PHPMailer/Exception.php';
require_once dirname(__DIR__) . '/vendor-standalone/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__) . '/vendor-standalone/PHPMailer/SMTP.php';

// ── Load .env ────────────────────────────────────────────────────────────────
use App\Core\EnvLoader;

EnvLoader::load(dirname(__DIR__) . '/.env');
// Core requirements — app won't work without these
EnvLoader::require([
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
]);

// ── Session ───────────────────────────────────────────────────────────────────
$sessionName     = $_ENV['SESSION_NAME'] ?? 'bbz_portal';
$sessionLifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 7200);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Lax'); // Lax allows session to survive OAuth redirects from Xero/Stripe
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.use_strict_mode', '1');
session_name($sessionName);

// ── Error handling ────────────────────────────────────────────────────────────
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');
}

date_default_timezone_set('Europe/London');
