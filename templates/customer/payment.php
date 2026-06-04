<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <a href="/invoices/<?= $invoice['id'] ?>" style="font-size:14px;color:#64748b;">&larr; Back to Invoice</a>
        <h1 style="margin-top:4px;">Pay Invoice <?= Security::e($invoice['invoice_number']) ?></h1>
    </div>
</div>

<div style="max-width:640px;">
    <div class="payment-amount-card">
        <div class="payment-amount-label">Amount Due</div>
        <div class="payment-amount-value">£<?= number_format((float)$invoice['amount_due'], 2) ?></div>
        <div class="payment-amount-ref"><?= Security::e($invoice['invoice_number']) ?></div>
    </div>

    <!-- Pay by Card -->
    <div class="card" style="margin-top:16px;">
        <div class="card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Pay by Card
            </h2>
            <div style="display:flex;gap:6px;align-items:center;">
                <span style="font-size:12px;color:#64748b;">Secured by</span>
                <strong style="color:#635bff;">Stripe</strong>
            </div>
        </div>
        <div class="card-body">
            <p style="color:#64748b;margin-bottom:20px;">You'll be securely redirected to Stripe to complete your payment. We never store your card details.</p>
            <form method="POST" action="/invoices/<?= $invoice['id'] ?>/pay">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Pay £<?= number_format((float)$invoice['amount_due'], 2) ?> by Card
                </button>
            </form>
        </div>
    </div>

    <!-- Pay by Bank Transfer -->
    <div class="card" style="margin-top:16px;">
        <div class="card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Pay by Bank Transfer
            </h2>
        </div>
        <div class="card-body">
            <p style="color:#64748b;margin-bottom:16px;">Transfer the amount directly to our bank account using the details below. <strong>Please use the reference provided</strong> so we can match your payment.</p>
            <div class="bank-details">
                <div class="bank-row"><span>Bank</span><strong><?= Security::e($bankName) ?></strong></div>
                <div class="bank-row"><span>Account Name</span><strong><?= Security::e($bankAccountName) ?></strong></div>
                <div class="bank-row"><span>Sort Code</span><strong><?= Security::e($bankSortCode) ?></strong></div>
                <div class="bank-row"><span>Account Number</span><strong><?= Security::e($bankAccountNumber) ?></strong></div>
                <div class="bank-row highlight"><span>Payment Reference</span><strong><?= Security::e($bankReference) ?></strong></div>
                <div class="bank-row"><span>Amount</span><strong>£<?= number_format((float)$invoice['amount_due'], 2) ?></strong></div>
            </div>
            <div class="alert alert-info" style="margin-top:16px;">
                Once your transfer has been sent, please <a href="/tickets/create">raise a support ticket</a> or email us to let us know. Bank transfers can take 1–3 working days to clear.
            </div>
        </div>
    </div>
</div>
