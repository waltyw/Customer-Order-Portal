<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Create Invoice</h1>
    <a href="/admin/invoices" class="btn btn-outline">&larr; Back</a>
</div>

<div class="card" style="max-width:900px;">
    <div class="card-body">
        <form method="POST" action="/admin/invoices/create" id="invoiceForm">
            <?= Security::csrfField() ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Customer <span class="required">*</span></label>
                    <select name="user_id" required>
                        <option value="">— Select customer —</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)($_GET['customer'] ?? 0) === $c['id'] ? 'selected' : '' ?>>
                            <?= Security::e($c['name']) ?><?= $c['company'] ? ' (' . Security::e($c['company']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Invoice Number <span class="required">*</span></label>
                    <input type="text" name="invoice_number" required value="<?= Security::e($nextNum) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="form-group">
                    <label>VAT Rate (%)</label>
                    <input type="number" name="vat_rate" value="20" min="0" max="100" step="0.01">
                </div>
            </div>

            <!-- Line items -->
            <h3 style="margin:24px 0 12px;font-size:15px;font-weight:600;">Line Items</h3>
            <table style="width:100%;border-collapse:collapse;" id="lineItems">
                <thead>
                    <tr style="background:#f8faff;">
                        <th style="padding:8px 12px;text-align:left;font-size:13px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">Description</th>
                        <th style="padding:8px 12px;text-align:right;font-size:13px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;width:80px;">Qty</th>
                        <th style="padding:8px 12px;text-align:right;font-size:13px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;width:130px;">Unit Price</th>
                        <th style="padding:8px 12px;text-align:right;font-size:13px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;width:100px;">Amount</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="lineItemsBody">
                    <tr class="line-item-row">
                        <td style="padding:6px 4px;"><input type="text" name="item_desc[]" required placeholder="Service description" style="width:100%;"></td>
                        <td style="padding:6px 4px;"><input type="number" name="item_qty[]" required value="1" min="0.01" step="0.01" class="qty-input" style="text-align:right;width:100%;"></td>
                        <td style="padding:6px 4px;"><input type="number" name="item_price[]" required min="0" step="0.01" class="price-input" placeholder="0.00" style="text-align:right;width:100%;"></td>
                        <td style="padding:6px 4px;text-align:right;font-weight:600;" class="line-total">£0.00</td>
                        <td></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="padding:8px 4px;">
                            <button type="button" class="btn btn-outline btn-sm" id="addLine">+ Add Line</button>
                        </td>
                    </tr>
                    <tr><td colspan="3" style="text-align:right;padding:8px 12px;font-size:13px;color:#64748b;">Subtotal</td><td style="text-align:right;padding:8px;" id="tSubtotal">£0.00</td><td></td></tr>
                    <tr><td colspan="3" style="text-align:right;padding:8px 12px;font-size:13px;color:#64748b;">VAT</td><td style="text-align:right;padding:8px;" id="tVat">£0.00</td><td></td></tr>
                    <tr><td colspan="3" style="text-align:right;padding:8px 12px;font-weight:700;">Total</td><td style="text-align:right;padding:8px;font-weight:700;font-size:18px;" id="tTotal">£0.00</td><td></td></tr>
                </tfoot>
            </table>

            <div class="form-group" style="margin-top:20px;">
                <label>Notes / Terms</label>
                <textarea name="notes" rows="3" placeholder="Payment terms, additional notes..."></textarea>
            </div>

            <div style="display:flex;align-items:center;gap:16px;margin-top:20px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                    <input type="checkbox" name="send_email" value="1" checked>
                    Send invoice notification email to customer
                </label>
            </div>

            <div class="form-actions" style="margin-top:24px;">
                <button type="submit" class="btn btn-primary">Create Invoice</button>
                <a href="/admin/invoices" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function recalc() {
    const vatRate = parseFloat(document.querySelector('[name="vat_rate"]').value) / 100 || 0.2;
    let subtotal = 0;
    document.querySelectorAll('.line-item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const line  = qty * price;
        row.querySelector('.line-total').textContent = '£' + line.toFixed(2);
        subtotal += line;
    });
    const vat   = subtotal * vatRate;
    const total = subtotal + vat;
    document.getElementById('tSubtotal').textContent = '£' + subtotal.toFixed(2);
    document.getElementById('tVat').textContent = '£' + vat.toFixed(2);
    document.getElementById('tTotal').textContent = '£' + total.toFixed(2);
}

document.addEventListener('input', recalc);

document.getElementById('addLine').addEventListener('click', () => {
    const row = document.querySelector('.line-item-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = i.type === 'number' && i.classList.contains('qty-input') ? '1' : '');
    row.querySelector('.line-total').textContent = '£0.00';
    const btn = document.createElement('button');
    btn.type = 'button'; btn.textContent = '×'; btn.className = 'btn btn-sm btn-danger-outline';
    btn.style.padding = '2px 8px';
    btn.onclick = () => { row.remove(); recalc(); };
    row.querySelector('td:last-child').appendChild(btn);
    document.getElementById('lineItemsBody').appendChild(row);
});

document.querySelector('[name="vat_rate"]').addEventListener('input', recalc);
</script>
