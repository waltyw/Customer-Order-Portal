<?php

declare(strict_types=1);

namespace App\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static function make(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 465);
        // Port 465 = implicit SSL, port 587 = STARTTLS
        $mail->SMTPSecure = ($mail->Port === 465) ? 'ssl' : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        return $mail;
    }

    public static function send(string $to, string $toName, string $subject, string $bodyHtml): bool
    {
        try {
            $mail = self::make();
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = self::wrap($subject, $bodyHtml);
            $mail->AltBody = strip_tags($bodyHtml);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendTicketReply(array $ticket, array $message, array $recipient, bool $isAdmin): bool
    {
        $who    = $isAdmin ? ($_ENV['SMTP_FROM_NAME'] ?? 'Support') : htmlspecialchars($message['sender_name'] ?? 'Customer');
        $ref    = htmlspecialchars($ticket['reference']);
        $subj   = htmlspecialchars($ticket['subject']);
        $body   = nl2br(htmlspecialchars($message['message']));

        $html = <<<HTML
        <p>Hello {$recipient['name']},</p>
        <p>There is a new reply on support ticket <strong>#{$ref}</strong>: <em>{$subj}</em></p>
        <blockquote style="border-left:4px solid #2563eb;padding:12px 16px;background:#f8faff;margin:16px 0;">
            <p style="margin:0;color:#1e293b;">{$body}</p>
            <p style="margin:8px 0 0;font-size:12px;color:#64748b;">— {$who}</p>
        </blockquote>
        <p><a href="{$_ENV['APP_URL']}/tickets/{$ticket['id']}" style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View Ticket</a></p>
        HTML;

        return self::send($recipient['email'], $recipient['name'], "Re: [{$ref}] {$subj}", $html);
    }

    public static function sendInvoiceReady(array $invoice, array $user): bool
    {
        $num    = htmlspecialchars($invoice['invoice_number']);
        $amount = '£' . number_format($invoice['amount_due'], 2);
        $due    = $invoice['due_date'] ? date('j F Y', strtotime($invoice['due_date'])) : 'On receipt';

        $html = <<<HTML
        <p>Hello {$user['name']},</p>
        <p>A new invoice is ready for your account.</p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr><td style="padding:8px;border:1px solid #e2e8f0;"><strong>Invoice Number</strong></td><td style="padding:8px;border:1px solid #e2e8f0;">{$num}</td></tr>
            <tr><td style="padding:8px;border:1px solid #e2e8f0;background:#f8faff;"><strong>Amount Due</strong></td><td style="padding:8px;border:1px solid #e2e8f0;background:#f8faff;">{$amount}</td></tr>
            <tr><td style="padding:8px;border:1px solid #e2e8f0;"><strong>Due Date</strong></td><td style="padding:8px;border:1px solid #e2e8f0;">{$due}</td></tr>
        </table>
        <p><a href="{$_ENV['APP_URL']}/invoices/{$invoice['id']}" style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View &amp; Pay Invoice</a></p>
        HTML;

        return self::send($user['email'], $user['name'], "Invoice {$num} is ready — {$amount} due", $html);
    }

    public static function sendWelcome(array $user, string $tempPassword): bool
    {
        $name = htmlspecialchars($user['name']);
        $email = htmlspecialchars($user['email']);
        $pass  = htmlspecialchars($tempPassword);

        $html = <<<HTML
        <p>Hello {$name},</p>
        <p>Your customer portal account has been created. You can now log in and view your invoices, raise support tickets, and manage your account.</p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr><td style="padding:8px;border:1px solid #e2e8f0;"><strong>Portal URL</strong></td><td style="padding:8px;border:1px solid #e2e8f0;"><a href="{$_ENV['APP_URL']}">{$_ENV['APP_URL']}</a></td></tr>
            <tr><td style="padding:8px;border:1px solid #e2e8f0;background:#f8faff;"><strong>Email</strong></td><td style="padding:8px;border:1px solid #e2e8f0;background:#f8faff;">{$email}</td></tr>
            <tr><td style="padding:8px;border:1px solid #e2e8f0;"><strong>Temporary Password</strong></td><td style="padding:8px;border:1px solid #e2e8f0;">{$pass}</td></tr>
        </table>
        <p><strong>Please change your password immediately after logging in.</strong></p>
        <p><a href="{$_ENV['APP_URL']}/login" style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Login Now</a></p>
        HTML;

        return self::send($user['email'], $user['name'], 'Welcome to ' . $_ENV['APP_NAME'], $html);
    }

    private static function wrap(string $subject, string $body): string
    {
        $appName = htmlspecialchars($_ENV['APP_NAME'] ?? 'Customer Portal');
        $appUrl  = htmlspecialchars($_ENV['APP_URL'] ?? '');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
                <tr><td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">
                        <tr><td style="background:#0f172a;padding:24px 32px;">
                            <h1 style="margin:0;color:#fff;font-size:20px;font-weight:600;">{$appName}</h1>
                        </td></tr>
                        <tr><td style="padding:32px;color:#374151;font-size:15px;line-height:1.6;">
                            {$body}
                        </td></tr>
                        <tr><td style="padding:16px 32px;background:#f8faff;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">
                            &copy; {$appName} &bull; <a href="{$appUrl}" style="color:#94a3b8;">{$appUrl}</a>
                        </td></tr>
                    </table>
                </td></tr>
            </table>
        </body></html>
        HTML;
    }
}
