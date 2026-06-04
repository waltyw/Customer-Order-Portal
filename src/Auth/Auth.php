<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\DB;
use App\Core\Security;

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));

        if (self::isLockedOut($email)) {
            Security::flash('error', 'Too many failed login attempts. Please wait 15 minutes and try again.');
            return false;
        }

        $user = DB::fetchOne(
            'SELECT id, email, password_hash, name, role, is_active FROM users WHERE email = ?',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordAttempt($email);
            return false;
        }

        if (!$user['is_active']) {
            Security::flash('error', 'Your account has been deactivated. Please contact support.');
            return false;
        }

        // Successful login — regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in_at'] = time();

        self::clearAttempts($email);

        DB::execute(
            'INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)',
            [$user['id'], 'login', Security::ip()]
        );

        return true;
    }

    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            DB::execute(
                'INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)',
                [$userId, 'logout', Security::ip()]
            );
        }
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        return DB::fetchOne('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            Security::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('Access denied.');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Security::redirect(self::isAdmin() ? '/admin' : '/dashboard');
        }
    }

    public static function createResetToken(string $email): ?string
    {
        $user = DB::fetchOne('SELECT id FROM users WHERE email = ? AND is_active = 1', [strtolower($email)]);
        if (!$user) return null;

        DB::execute('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        DB::execute(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)',
            [$user['id'], $token, $expires]
        );

        return $token;
    }

    public static function resetPassword(string $token, string $newPassword): bool
    {
        $reset = DB::fetchOne(
            'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()',
            [$token]
        );

        if (!$reset) return false;

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        DB::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $reset['user_id']]);
        DB::execute('UPDATE password_resets SET used = 1 WHERE id = ?', [$reset['id']]);

        return true;
    }

    private static function isLockedOut(string $email): bool
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_MINUTES . ' minutes'));
        $count = DB::fetchOne(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE email = ? AND attempted_at > ?',
            [$email, $cutoff]
        );
        return (int)($count['cnt'] ?? 0) >= self::MAX_ATTEMPTS;
    }

    private static function recordAttempt(string $email): void
    {
        DB::execute(
            'INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)',
            [$email, Security::ip()]
        );
    }

    private static function clearAttempts(string $email): void
    {
        DB::execute('DELETE FROM login_attempts WHERE email = ?', [$email]);
    }
}
