<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Stripe;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Email\Mailer;

class WebhookController
{
    public function stripe(): void
    {
        $payload   = (string)file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'];

        $event = Stripe::verifyWebhook($payload, $sigHeader, $secret);

        if ($event === null) {
            http_response_code(400);
            error_log('Stripe webhook verification failed');
            exit();
        }

        if ($event['type'] === 'checkout.session.completed') {
            $this->handleCheckoutComplete($event['data']['object']);
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    private function handleCheckoutComplete(array $session): void
    {
        if (($session['payment_status'] ?? '') !== 'paid') return;

        $payment = Payment::findBySession($session['id']);
        if (!$payment || $payment['status'] === 'completed') return;

        Payment::complete($payment['id'], $session['payment_intent'] ?? '');

        if ($payment['invoice_id']) {
            Invoice::markPaid($payment['invoice_id']);

            $invoice = Invoice::find($payment['invoice_id']);
            $user    = User::find($payment['user_id']);

            if ($invoice && $user) {
                $html = "<p>Hello {$user['name']},</p>"
                    . "<p>We've received your payment of <strong>£" . number_format($payment['amount'], 2) . "</strong>"
                    . " for invoice <strong>{$invoice['invoice_number']}</strong>. Thank you!</p>"
                    . "<p><a href=\"{$_ENV['APP_URL']}/invoices/{$invoice['id']}\" style=\"background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;\">View Invoice</a></p>";
                Mailer::send($user['email'], $user['name'], 'Payment Received — Invoice ' . $invoice['invoice_number'], $html);
            }
        }

        \App\Core\DB::execute(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $payment['user_id'],
                'payment_completed',
                'payment',
                $payment['id'],
                json_encode(['session_id' => $session['id'], 'amount' => $payment['amount']]),
                '0.0.0.0',
            ]
        );
    }
}
