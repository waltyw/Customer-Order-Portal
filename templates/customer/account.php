<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>My Account</h1>
</div>

<div style="max-width:760px;">

    <!-- Account Details -->
    <div class="card mb-4">
        <div class="card-header"><h2>Your Details</h2></div>
        <div class="card-body">
            <form method="POST" action="/account">
                <?= Security::csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="name" required value="<?= Security::e($user['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" value="<?= Security::e($user['company'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= Security::e($user['phone'] ?? '') ?>" placeholder="07700 000000">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?= Security::e($user['email'] ?? '') ?>" disabled style="background:#f8fafc;color:#94a3b8;cursor:not-allowed;">
                        <small style="color:#64748b;font-size:12px;">To change your email <a href="/tickets/create">raise a support ticket</a>.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CC Email 1 <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                        <input type="email" name="cc_email_1" value="<?= Security::e($user['cc_email_1'] ?? '') ?>" placeholder="cc@example.com">
                    </div>
                    <div class="form-group">
                        <label>CC Email 2 <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                        <input type="email" name="cc_email_2" value="<?= Security::e($user['cc_email_2'] ?? '') ?>" placeholder="cc@example.com">
                    </div>
                </div>
                <p style="margin:-8px 0 16px;font-size:12px;color:#64748b;">CC emails receive copies of order confirmations. They cannot be used to log in.</p>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Save Details</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Billing + Delivery Address -->
    <div class="card mb-4">
        <div class="card-header"><h2>Addresses</h2></div>
        <div class="card-body">
            <form method="POST" action="/account/address">
                <?= Security::csrfField() ?>

                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:12px;">Billing Address</div>
                <div class="form-group">
                    <label>Address Line 1</label>
                    <input type="text" name="billing_address_1" value="<?= Security::e($user['billing_address_1'] ?? '') ?>" placeholder="Street address">
                </div>
                <div class="form-group">
                    <label>Address Line 2</label>
                    <input type="text" name="billing_address_2" value="<?= Security::e($user['billing_address_2'] ?? '') ?>" placeholder="Unit, suite, etc.">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="billing_city" value="<?= Security::e($user['billing_city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>County</label>
                        <input type="text" name="billing_county" value="<?= Security::e($user['billing_county'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="max-width:160px;">
                        <label>Postcode</label>
                        <input type="text" name="billing_postcode" value="<?= Security::e($user['billing_postcode'] ?? '') ?>" placeholder="LN1 1AA">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="billing_country" value="<?= Security::e($user['billing_country'] ?? 'United Kingdom') ?>">
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">

                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px;">
                    <input type="checkbox" name="delivery_same_as_billing" id="sameAsBilling" value="1"
                           <?= (int)($user['delivery_same_as_billing'] ?? 1) ? 'checked' : '' ?>
                           onchange="toggleDelivery(this)">
                    <span style="font-weight:500;">Delivery address same as billing</span>
                </label>

                <div id="deliveryFields" <?= (int)($user['delivery_same_as_billing'] ?? 1) ? 'style="display:none;"' : '' ?>>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:12px;">Delivery Address</div>
                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="delivery_address_1" value="<?= Security::e($user['delivery_address_1'] ?? '') ?>" placeholder="Street address">
                    </div>
                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="delivery_address_2" value="<?= Security::e($user['delivery_address_2'] ?? '') ?>" placeholder="Unit, suite, etc.">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="delivery_city" value="<?= Security::e($user['delivery_city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>County</label>
                            <input type="text" name="delivery_county" value="<?= Security::e($user['delivery_county'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="max-width:160px;">
                            <label>Postcode</label>
                            <input type="text" name="delivery_postcode" value="<?= Security::e($user['delivery_postcode'] ?? '') ?>" placeholder="LN1 1AA">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="delivery_country" value="<?= Security::e($user['delivery_country'] ?? 'United Kingdom') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header"><h2>Change Password</h2></div>
        <div class="card-body">
            <p style="color:#64748b;margin-bottom:16px;">Use the password reset flow to set a new password — a reset link will be emailed to you.</p>
            <a href="/forgot-password" class="btn btn-outline">Send Password Reset Email</a>
        </div>
    </div>
</div>

<script>
function toggleDelivery(cb) {
    document.getElementById('deliveryFields').style.display = cb.checked ? 'none' : '';
}
</script>
