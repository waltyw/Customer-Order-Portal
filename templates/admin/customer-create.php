<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Add Customer</h1>
    <a href="/admin/customers" class="btn btn-outline">&larr; Back</a>
</div>

<form method="POST" action="/admin/customers/create" style="max-width:760px;">
    <?= Security::csrfField() ?>

    <!-- Account Details -->
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Account Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name" required value="<?= Security::e($_POST['name'] ?? '') ?>" placeholder="Jane Smith">
                </div>
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" required value="<?= Security::e($_POST['email'] ?? '') ?>" placeholder="jane@company.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" value="<?= Security::e($_POST['company'] ?? '') ?>" placeholder="Company Ltd">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= Security::e($_POST['phone'] ?? '') ?>" placeholder="07700 000000">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="max-width:200px;">
                    <label>Branch Number</label>
                    <input type="text" name="branch_number" value="<?= Security::e($_POST['branch_number'] ?? '') ?>" placeholder="e.g. BR001">
                </div>
            </div>
        </div>
    </div>

    <!-- Billing Address -->
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Billing Address</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label>Address Line 1</label>
                <input type="text" name="billing_address_1" value="<?= Security::e($_POST['billing_address_1'] ?? '') ?>" placeholder="Street address">
            </div>
            <div class="form-group">
                <label>Address Line 2</label>
                <input type="text" name="billing_address_2" value="<?= Security::e($_POST['billing_address_2'] ?? '') ?>" placeholder="Unit, suite, etc.">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="billing_city" value="<?= Security::e($_POST['billing_city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>County</label>
                    <input type="text" name="billing_county" value="<?= Security::e($_POST['billing_county'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="max-width:160px;">
                    <label>Postcode</label>
                    <input type="text" name="billing_postcode" value="<?= Security::e($_POST['billing_postcode'] ?? '') ?>" placeholder="LN1 1AA">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="billing_country" value="<?= Security::e($_POST['billing_country'] ?? 'United Kingdom') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Address -->
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Delivery Address</h3></div>
        <div class="card-body">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px;">
                <input type="checkbox" name="delivery_same_as_billing" id="sameAsBilling" value="1"
                       <?= !empty($_POST['delivery_same_as_billing']) ? 'checked' : 'checked' ?>
                       onchange="toggleDelivery(this)">
                <span>Same as billing address</span>
            </label>

            <div id="deliveryFields" style="display:none;">
                <div class="form-group">
                    <label>Address Line 1</label>
                    <input type="text" name="delivery_address_1" value="<?= Security::e($_POST['delivery_address_1'] ?? '') ?>" placeholder="Street address">
                </div>
                <div class="form-group">
                    <label>Address Line 2</label>
                    <input type="text" name="delivery_address_2" value="<?= Security::e($_POST['delivery_address_2'] ?? '') ?>" placeholder="Unit, suite, etc.">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="delivery_city" value="<?= Security::e($_POST['delivery_city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>County</label>
                        <input type="text" name="delivery_county" value="<?= Security::e($_POST['delivery_county'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="max-width:160px;">
                        <label>Postcode</label>
                        <input type="text" name="delivery_postcode" value="<?= Security::e($_POST['delivery_postcode'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="delivery_country" value="<?= Security::e($_POST['delivery_country'] ?? 'United Kingdom') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create Customer &amp; Send Welcome Email</button>
        <a href="/admin/customers" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function toggleDelivery(cb) {
    document.getElementById('deliveryFields').style.display = cb.checked ? 'none' : '';
}
// Show delivery fields if checkbox was unchecked on page load (e.g. form validation return)
document.addEventListener('DOMContentLoaded', function() {
    toggleDelivery(document.getElementById('sameAsBilling'));
});
</script>
