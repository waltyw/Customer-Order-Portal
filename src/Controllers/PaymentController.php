<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\Stripe;
use App\Core\View;
use App\Models\Invoice;
use App\Models\Payment;

class PaymentController
{
    public function showPay(int $invoiceId): void
    {
        Auth::requireAuth();
        $invoice = Invoice::findForUser($invoiceId, Auth::id());
        if (!$invoice) { http_response_code(404); die('Invoice not found.'); }

        if ($invoice['status'] === 'paid' || (float)$invoice['amount_due'] <= 0) {
            Security::flash('info', 'This invoice has already been paid.');
            Security::redirect('/invoices/' . $invoiceId);
        }

        View::render('customer/payment', [
            'title'             => 'Pay Invoice ' . $invoice['invoice_number'],
            'invoice'           => $invoice,
            'stripePubKey'      => $_ENV['STRIPE_PUBLISHABLE_KEY'],
            'bankName'          => $_ENV['BANK_NAME'] ?? '',
            'bankAccountName'   => $_ENV['BANK_ACCOUNT_NAME'] ?? '',
            'bankSortCode'      => $_ENV['BANK_SORT_CODE'] ?? '',
            'bankAccountNumber' => $_ENV['BANK_ACCOUNT_NUMBER'] ?? '',
            'bankReference'     => ($_ENV['BANK_REFERENCE_PREFIX'] ?? 'BBZ') . '-' . $invoice['invoice_number'],
        ]);
    }

    public function processStripe(int $invoiceId): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $invoice = Invoice::findForUser($invoiceId, Auth::id());
        if (!$invoice || (float)$invoice['amount_due'] <= 0) {
            Security::flash('error', 'Invoice not available for payment.');
            Security::redirect('/invoices');
        }

        $user      = Auth::user();
        $amountGbp = (float)$invoice['amount_due'];

        try {
            $session = Stripe::createCheckoutSession([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => strtolower($invoice['currency']),
                        'unit_amount'  => (int)round($amountGbp * 100),
                        'product_data' => [
                            'name'        => 'Invoice ' . $invoice['invoice_number'],
                            'description' => 'Payment to Beebizzi',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode'          => 'payment',
                'customer_email'=> $user['email'],
                'success_url'   => $_ENV['APP_URL'] . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'    => $_ENV['APP_URL'] . '/invoices/' . $invoiceId,
                'metadata'      => [
                    'invoice_id' => $invoiceId,
                    'user_id'    => Auth::id(),
                ],
            ]);

            Payment::create([
                'user_id'           => Auth::id(),
                'invoice_id'        => $invoiceId,
                'stripe_session_id' => $session['id'],
                'amount'            => $amountGbp,
                'currency'          => $invoice['currency'],
                'method'            => 'stripe',
                'status'            => 'pending',
            ]);

            Security::redirect($session['url']);

        } catch (\RuntimeException $e) {
            error_log('Stripe error: ' . $e->getMessage());
            Security::flash('error', 'Payment could not be started. Please try again or contact support.');
            Security::redirect('/invoices/' . $invoiceId . '/pay');
        }
    }

    public function success(): void
    {
        Auth::requireAuth();
        $sessionId = $_GET['session_id'] ?? '';
        $payment   = $sessionId ? Payment::findBySession($sessionId) : null;

        View::render('customer/payment-success', [
            'title'   => 'Payment Successful',
            'payment' => $payment,
        ]);
    }

    public function cancelled(): void
    {
        Auth::requireAuth();
        Security::flash('info', 'Payment was cancelled. Your invoice is still outstanding.');
        Security::redirect('/invoices');
    }
}
