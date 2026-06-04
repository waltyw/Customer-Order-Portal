<?php use App\Core\Security; ?>
<div class="page-header">
    <div>
        <a href="/invoices" style="font-size:14px;color:#64748b;">&larr; Back to Invoices</a>
        <h1 style="margin-top:4px;">Invoice <?= Security::e($invoice['invoice_number']) ?></h1>
    </div>
    <?php if (!in_array($invoice['status'], ['paid','voided']) && (float)$invoice['amount_due'] > 0): ?>
    <a href="/invoices/<?= $invoice['id'] ?>/pay" class="btn btn-primary">Pay Now — £<?= number_format((float)$invoice['amount_due'], 2) ?></a>
    <?php endif; ?>
</div>

<div style="max-width:800px;">
    <div class="card">
        <div class="card-body">
            <!-- Invoice header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;">
                <div>
                    <div style="font-size:28px;font-weight:700;color:#0f172a;">INVOICE</div>
                    <div style="color:#64748b;margin-top:4px;"><?= Security::e($invoice['invoice_number']) ?></div>
                </div>
                <span class="badge badge-<?= $invoice['status'] ?>" style="font-size:14px;padding:6px 14px;"><?= ucfirst($invoice['status']) ?></span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
                <div>
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Issue Date</div>
                    <div><?= $invoice['issue_date'] ? date('j F Y', strtotime($invoice['issue_date'])) : '—' ?></div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Due Date</div>
                    <div class="<?= $invoice['status'] === 'overdue' ? 'text-danger' : '' ?>">
                        <?= $invoice['due_date'] ? date('j F Y', strtotime($invoice['due_date'])) : 'On receipt' ?>
                    </div>
                </div>
            </div>

            <!-- Line items -->
            <table class="table invoice-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Description</th>
                        <th style="text-align:right;">Qty</th>
                        <th style="text-align:right;">Unit Price</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lineItems as $item): ?>
                <tr>
                    <td><?= Security::e($item['description']) ?></td>
                    <td style="text-align:right;"><?= Security::e($item['qty']) ?></td>
                    <td style="text-align:right;">£<?= number_format((float)$item['unit_price'], 2) ?></td>
                    <td style="text-align:right;">£<?= number_format((float)$item['qty'] * (float)$item['unit_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="subtotal-row"><td colspan="3" style="text-align:right;">Subtotal</td><td style="text-align:right;">£<?= number_format((float)$invoice['subtotal'], 2) ?></td></tr>
                    <tr class="subtotal-row"><td colspan="3" style="text-align:right;">VAT (20%)</td><td style="text-align:right;">£<?= number_format((float)$invoice['vat_amount'], 2) ?></td></tr>
                    <tr class="total-row"><td colspan="3" style="text-align:right;font-weight:700;">Total</td><td style="text-align:right;font-weight:700;font-size:18px;">£<?= number_format((float)$invoice['total'], 2) ?></td></tr>
                    <?php if ((float)$invoice['amount_paid'] > 0): ?>
                    <tr class="subtotal-row"><td colspan="3" style="text-align:right;">Amount Paid</td><td style="text-align:right;color:#16a34a;">–£<?= number_format((float)$invoice['amount_paid'], 2) ?></td></tr>
                    <tr class="total-row"><td colspan="3" style="text-align:right;">Amount Due</td><td style="text-align:right;font-size:18px;color:<?= (float)$invoice['amount_due'] > 0 ? '#dc2626' : '#16a34a' ?>;">£<?= number_format((float)$invoice['amount_due'], 2) ?></td></tr>
                    <?php endif; ?>
                </tfoot>
            </table>

            <?php if ($invoice['notes']): ?>
            <div style="margin-top:24px;padding:16px;background:#f8faff;border-radius:8px;">
                <div style="font-weight:600;margin-bottom:6px;font-size:13px;color:#64748b;">Notes</div>
                <?= nl2br(Security::e($invoice['notes'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!in_array($invoice['status'], ['paid','voided']) && (float)$invoice['amount_due'] > 0): ?>
    <div class="card" style="margin-top:16px;">
        <div class="card-body" style="text-align:center;">
            <h2 style="margin-bottom:8px;">Ready to pay?</h2>
            <p style="color:#64748b;margin-bottom:20px;">Pay securely online by card, or transfer by bank.</p>
            <a href="/invoices/<?= $invoice['id'] ?>/pay" class="btn btn-primary btn-lg">Pay £<?= number_format((float)$invoice['amount_due'], 2) ?> Now</a>
        </div>
    </div>
    <?php endif; ?>
</div>
