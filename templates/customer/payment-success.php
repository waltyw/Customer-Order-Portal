<?php use App\Core\Security; ?>
<div style="max-width:560px;margin:60px auto;text-align:center;">
    <div class="success-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1 style="color:#0f172a;margin-top:24px;">Payment Successful</h1>
    <p style="color:#64748b;font-size:16px;margin-top:8px;">Thank you — your payment has been received. A confirmation has been sent to your email address.</p>

    <?php if ($payment): ?>
    <div class="card" style="margin:28px 0;text-align:left;">
        <div class="card-body">
            <div class="bank-row"><span>Amount Paid</span><strong style="color:#16a34a;">£<?= number_format((float)$payment['amount'], 2) ?></strong></div>
            <div class="bank-row"><span>Payment Method</span><strong>Card (Stripe)</strong></div>
            <div class="bank-row"><span>Date</span><strong><?= date('j F Y, H:i', strtotime($payment['created_at'])) ?></strong></div>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:12px;justify-content:center;">
        <a href="/invoices" class="btn btn-outline">View Invoices</a>
        <a href="/dashboard" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
