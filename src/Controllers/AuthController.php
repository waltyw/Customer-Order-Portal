<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Email\Mailer;

class AuthController
{
    public function loginForm(): void
    {
        Auth::requireGuest();
        View::renderRaw('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        Auth::requireGuest();
        Security::checkCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            Security::flash('error', 'Please enter your email address and password.');
            Security::redirect('/login');
        }

        if (Auth::attempt($email, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            Security::redirect($redirect ?? (Auth::isAdmin() ? '/admin' : '/dashboard'));
        }

        if (empty($_SESSION['flash'])) {
            Security::flash('error', 'Incorrect email address or password.');
        }
        Security::redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        Security::redirect('/login');
    }

    public function forgotForm(): void
    {
        Auth::requireGuest();
        View::renderRaw('auth/forgot-password', ['title' => 'Forgot Password']);
    }

    public function forgotSubmit(): void
    {
        Auth::requireGuest();
        Security::checkCsrf();

        $email = strtolower(trim($_POST['email'] ?? ''));

        // Always show success to prevent email enumeration
        $token = Auth::createResetToken($email);
        if ($token) {
            $user = \App\Models\User::findByEmail($email);
            $link = $_ENV['APP_URL'] . '/reset-password?token=' . $token;
            $html = "<p>Hello {$user['name']},</p><p>Click the link below to reset your password. This link expires in 1 hour.</p><p><a href=\"{$link}\" style=\"background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;\">Reset Password</a></p><p>If you didn't request this, you can safely ignore this email.</p>";
            Mailer::send($email, $user['name'], 'Reset your password', $html);
        }

        Security::flash('success', 'If that email is registered, you\'ll receive a password reset link shortly.');
        Security::redirect('/forgot-password');
    }

    public function resetForm(): void
    {
        Auth::requireGuest();
        $token = $_GET['token'] ?? '';
        if (!$token) Security::redirect('/login');
        View::renderRaw('auth/reset-password', ['title' => 'Reset Password', 'token' => $token]);
    }

    public function resetSubmit(): void
    {
        Auth::requireGuest();
        Security::checkCsrf();

        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 10) {
            Security::flash('error', 'Password must be at least 10 characters.');
            Security::redirect('/reset-password?token=' . urlencode($token));
        }

        if ($password !== $confirm) {
            Security::flash('error', 'Passwords do not match.');
            Security::redirect('/reset-password?token=' . urlencode($token));
        }

        if (Auth::resetPassword($token, $password)) {
            Security::flash('success', 'Password updated. You can now log in.');
            Security::redirect('/login');
        }

        Security::flash('error', 'Invalid or expired reset link. Please request a new one.');
        Security::redirect('/forgot-password');
    }
}
