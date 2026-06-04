<?php
$currency = \App\Models\Setting::get('shop_default_currency') ?: 'GBP';
$symbol   = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
$csrfToken = \App\Core\Security::csrfToken();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Shop</h1>
        <p class="page-subtitle">Browse our product catalogue</p>
    </div>
    <a href="/basket" class="btn btn-primary btn-basket-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Basket
        <?php if ($basketCount > 0): ?>
        <span class="basket-badge"><?= (int)$basketCount ?></span>
        <?php endif; ?>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding:16px 20px;">
        <form method="get" class="shop-filter-bar">
            <input type="text" name="q" value="<?= \App\Core\Security::e($search) ?>" placeholder="Search by name or SKU…" class="form-control" style="max-width:300px;">
            <select name="category" class="form-control" style="max-width:220px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= (isset($activeCategory['id']) && $activeCategory['id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= \App\Core\Security::e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $activeCategory): ?>
            <a href="/shop" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Quick Order -->
<details class="card mb-4">
    <summary style="padding:14px 20px;cursor:pointer;font-weight:600;font-size:14px;list-style:none;display:flex;align-items:center;gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Quick Order — Bulk Add by SKU
    </summary>
    <div class="card-body" style="border-top:1px solid var(--border);">
        <form method="post" action="/basket/bulk-add">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <p style="color:var(--text-muted);margin-bottom:12px;font-size:13px;">Paste SKUs and quantities (one per line, e.g. <code>SKU001,2</code>) or use the row form below.</p>
            <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                <textarea name="csv_input" rows="5" class="form-control" style="max-width:400px;font-family:monospace;font-size:13px;" placeholder="SKU001, 2&#10;SKU002, 1&#10;SKU003, 5"></textarea>
                <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Add to Basket</button>
            </div>
        </form>
    </div>
</details>

<!-- Product Grid -->
<?php if (empty($products)): ?>
<div class="empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    <p style="margin-top:12px;color:var(--text-muted);">No products found<?= $search ? ' for "' . \App\Core\Security::e($search) . '"' : '' ?>.</p>
</div>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $p):
        $hasPrice = ($tierId > 0 && $p['tier_price'] !== null);
        $price    = $hasPrice ? (float)$p['tier_price'] : null;
        $inStock  = (int)$p['stock'] > 0;
    ?>
    <div class="product-card <?= !$inStock ? 'out-of-stock' : '' ?>">
        <a href="/shop/<?= \App\Core\Security::e($p['slug']) ?>" class="product-card-img-link">
            <?php if ($p['primary_image']): ?>
            <img src="/assets/img/products/<?= \App\Core\Security::e($p['primary_image']) ?>" alt="<?= \App\Core\Security::e($p['name']) ?>" class="product-card-img">
            <?php else: ?>
            <div class="product-card-img-placeholder">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <?php endif; ?>
        </a>
        <div class="product-card-body">
            <div class="product-card-meta"><?= \App\Core\Security::e($p['sku']) ?></div>
            <a href="/shop/<?= \App\Core\Security::e($p['slug']) ?>" class="product-card-name"><?= \App\Core\Security::e($p['name']) ?></a>
            <div class="product-card-price">
                <?php if ($price !== null): ?>
                    <?= $symbol ?><?= number_format($price, 2) ?>
                <?php else: ?>
                    <span style="color:var(--text-muted);font-size:13px;">Contact for pricing</span>
                <?php endif; ?>
            </div>
            <div class="product-card-stock <?= $inStock ? 'in-stock' : 'oos' ?>">
                <?= $inStock ? 'In stock' : 'Out of stock' ?>
            </div>
        </div>
        <?php if ($inStock && $price !== null): ?>
        <div class="product-card-footer">
            <form method="post" action="/basket/add" class="add-to-basket-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="redirect" value="/shop">
                <div class="qty-add-row">
                    <input type="number" name="quantity" value="1" min="1" max="999" class="form-control qty-input">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.add-to-basket-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = new FormData(form);
        fetch('/basket/add', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
            .then(r => r.json()).then(function(res) {
                if (res.ok) {
                    const badge = document.querySelector('.basket-badge');
                    if (badge) badge.textContent = res.count;
                    else {
                        const link = document.querySelector('.btn-basket-link');
                        if (link) link.insertAdjacentHTML('beforeend', '<span class="basket-badge">' + res.count + '</span>');
                    }
                    const btn = form.querySelector('button[type=submit]');
                    const orig = btn.textContent;
                    btn.textContent = 'Added!';
                    btn.style.background = 'var(--success)';
                    setTimeout(function() { btn.textContent = orig; btn.style.background = ''; }, 1500);
                }
            });
    });
});
</script>
