<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Add Customer</h1>
    <a href="/admin/customers" class="btn btn-outline">&larr; Back</a>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="/admin/customers/create">
            <?= Security::csrfField() ?>
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
                <div class="form-group">
                    <label>Postcode</label>
                    <input type="text" name="postcode" value="<?= Security::e($_POST['postcode'] ?? '') ?>" placeholder="LN1 1AA" style="max-width:140px;">
                </div>
                <div class="form-group">
                    <label>Branch Number</label>
                    <input type="text" name="branch_number" value="<?= Security::e($_POST['branch_number'] ?? '') ?>" placeholder="e.g. BR001">
                </div>
            </div>
            <div class="form-group">
                <label>Website URL</label>
                <input type="text" name="website_url" value="<?= Security::e($_POST['website_url'] ?? '') ?>" placeholder="https://theirdomain.co.uk">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Customer &amp; Send Welcome Email</button>
            </div>
        </form>
    </div>
</div>
