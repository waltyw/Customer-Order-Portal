<?php

declare(strict_types=1);

namespace App\Core;

class Security
{
    public static function setHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://js.stripe.com https://cdn.quilljs.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.quilljs.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdn.quilljs.com https://cdn.jsdelivr.net; frame-src https://js.stripe.com; img-src 'self' data:; connect-src 'self' https://api.stripe.com;");
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        $stored = $_SESSION['csrf_token'] ?? '';
        return hash_equals($stored, $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    public static function checkCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!self::verifyCsrf($token)) {
            http_response_code(403);
            die('Invalid security token. Please go back and try again.');
        }
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function redirect(string $path, int $code = 302): never
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        $url = str_starts_with($path, 'http') ? $path : $base . $path;
        header('Location: ' . $url, true, $code);
        exit;
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
