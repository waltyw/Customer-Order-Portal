<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
$isNew = ($product === null);
$prices = $product ? ($product['prices'] ?? []) : [];
?>
<div class="page-header">
    <div>
        <a href="/admin/shop/products" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Products
        </a>
        <h1 class="page-title"><?= $isNew ? 'Add Product' : 'Edit Product' ?></h1>
    </div>
</div>

<form method="post" action="<?= $isNew ? '/admin/shop/products/create' : '/admin/shop/products/' . (int)$product['id'] . '/update' ?>"
      enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;">
    <div>
        <!-- Core Details -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Product Details</h3></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label">Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= $e($product['name'] ?? '') ?>" required
                               oninput="autoSlug(this)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="sku" class="form-control" value="<?= $e($product['sku'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slugField" class="form-control" value="<?= $e($product['slug'] ?? '') ?>" placeholder="auto-generated-from-name">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="6" class="form-control"><?= $e($product['description'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">None</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ($product['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                                <?= $e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= (int)($product['sort_order'] ?? 0) ?>">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px;">
                    <input type="checkbox" name="is_active" value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                    Active (visible in catalogue)
                </label>
            </div>
        </div>

        <!-- Tier Pricing -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Tier Pricing</h3></div>
            <div class="card-body">
                <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">Set the price for each customer tier. Leave blank to hide from that tier.</p>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
                    <?php foreach ($tiers as $tier): ?>
                    <div class="form-group">
                        <label class="form-label"><?= $e($tier['name']) ?></label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);">£</span>
                            <input type="number" name="prices[<?= (int)$tier['id'] ?>]" step="0.0001" min="0"
                                   class="form-control" style="padding-left:24px;"
                                   value="<?= isset($prices[(int)$tier['id']]) ? number_format((float)$prices[(int)$tier['id']]['price'], 4) : '' ?>"
                                   placeholder="0.0000">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Images</h3></div>
            <div class="card-body">
                <?php if (!$isNew && !empty($product['images'])): ?>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                    <?php foreach ($product['images'] as $img): ?>
                    <div style="position:relative;">
                        <img src="/assets/img/products/<?= $e($img['filename']) ?>" alt=""
                             style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid <?= $img['is_primary'] ? 'var(--primary)' : 'var(--border)' ?>;">
                        <?php if ($img['is_primary']): ?>
                        <span style="position:absolute;bottom:2px;left:2px;background:var(--primary);color:#fff;font-size:10px;padding:1px 4px;border-radius:3px;">Primary</span>
                        <?php endif; ?>
                        <form method="post" action="/admin/shop/products/<?= (int)$product['id'] ?>/image/<?= (int)$img['id'] ?>/delete" style="position:absolute;top:2px;right:2px;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" onclick="return confirm('Delete image?')" style="background:rgba(220,38,38,.85);border:none;color:#fff;border-radius:4px;width:20px;height:20px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;">&times;</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <label class="form-label">Upload Images <?= $isNew ? '(first image becomes primary)' : '(new uploads added to gallery)' ?></label>
                <input type="file" name="images[]" multiple accept="image/*" class="form-control">
                <p style="color:var(--text-muted);font-size:12px;margin-top:6px;">Accepted: JPG, PNG, WebP, GIF</p>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div>
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Stock</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Current Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?= (int)($product['stock'] ?? 0) ?>" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Low Stock Alert Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="<?= (int)($product['low_stock_threshold'] ?? 5) ?>" min="0">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Product Type</label>
                    <select name="type" class="form-control">
                        <option value="simple"   <?= ($product['type'] ?? 'simple') === 'simple'   ? 'selected' : '' ?>>Simple</option>
                        <option value="variable" <?= ($product['type'] ?? 'simple') === 'variable' ? 'selected' : '' ?>>Variable (has variations)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-bottom:8px;">
                    <?= $isNew ? 'Create Product' : 'Save Changes' ?>
                </button>
                <?php if (!$isNew): ?>
                <a href="/admin/shop/products/<?= (int)$product['id'] ?>/variations" class="btn btn-secondary" style="width:100%;text-align:center;margin-bottom:8px;">
                    Manage Variations
                </a>
                <?php endif; ?>
                <a href="/admin/shop/products" class="btn btn-secondary" style="width:100%;text-align:center;">Cancel</a>
            </div>
        </div>
    </div>
</div>
</form>

<script>
function autoSlug(input) {
    const slug = input.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const slugField = document.getElementById('slugField');
    if (!slugField.dataset.manual) slugField.value = slug;
}
document.getElementById('slugField').addEventListener('input', function() {
    this.dataset.manual = '1';
});
</script>
