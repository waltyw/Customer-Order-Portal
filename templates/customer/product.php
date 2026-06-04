<?php
$currency  = \App\Models\Setting::get('shop_default_currency') ?: 'GBP';
$symbol    = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
$csrfToken = \App\Core\Security::csrfToken();
$primaryImg = null;
foreach ($product['images'] as $img) {
    if ($img['is_primary']) { $primaryImg = $img; break; }
}
if (!$primaryImg && !empty($product['images'])) {
    $primaryImg = $product['images'][0];
}
$inStock = (int)$product['stock'] > 0;
?>
<div class="page-header">
    <div>
        <a href="/shop" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Shop
        </a>
        <h1 class="page-title"><?= \App\Core\Security::e($product['name']) ?></h1>
    </div>
    <a href="/basket" class="btn btn-primary btn-basket-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Basket
        <?php if ($basketCount > 0): ?>
        <span class="basket-badge"><?= (int)$basketCount ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="product-detail-grid">
    <!-- Images -->
    <div class="product-images">
        <?php if ($primaryImg): ?>
        <div class="product-main-img-wrap">
            <img id="mainImg" src="/assets/img/products/<?= \App\Core\Security::e($primaryImg['filename']) ?>" alt="<?= \App\Core\Security::e($primaryImg['alt_text'] ?: $product['name']) ?>" class="product-main-img">
        </div>
        <?php if (count($product['images']) > 1): ?>
        <div class="product-thumbs">
            <?php foreach ($product['images'] as $img): ?>
            <img src="/assets/img/products/<?= \App\Core\Security::e($img['filename']) ?>"
                 alt="<?= \App\Core\Security::e($img['alt_text'] ?: $product['name']) ?>"
                 class="product-thumb <?= $img['id'] === $primaryImg['id'] ? 'active' : '' ?>"
                 onclick="document.getElementById('mainImg').src=this.src;document.querySelectorAll('.product-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active');">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="product-img-placeholder-lg">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="product-info">
        <div class="product-meta-row">
            <span class="badge badge-secondary">SKU: <?= \App\Core\Security::e($product['sku']) ?></span>
            <?php if ($product['category_name']): ?>
            <a href="/shop?category=<?= (int)$product['category_id'] ?>" class="badge badge-outline"><?= \App\Core\Security::e($product['category_name']) ?></a>
            <?php endif; ?>
            <span class="badge <?= $inStock ? 'badge-success' : 'badge-danger' ?>">
                <?= $inStock ? 'In Stock' : 'Out of Stock' ?>
            </span>
        </div>

        <div class="product-price-display">
            <?php if ($tierPrice !== null): ?>
            <span class="price-large"><?= $symbol ?><?= number_format((float)$tierPrice, 2) ?></span>
            <span style="color:var(--text-muted);font-size:13px;">ex. VAT</span>
            <?php else: ?>
            <span style="color:var(--text-muted);">Pricing not available — contact us.</span>
            <?php endif; ?>
        </div>

        <?php if ($product['description']): ?>
        <div class="product-description">
            <?= nl2br(\App\Core\Security::e($product['description'])) ?>
        </div>
        <?php endif; ?>

        <?php if ($inStock && $tierPrice !== null): ?>
        <form method="post" action="/basket/add" class="add-to-basket-large">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <input type="hidden" name="redirect" value="/basket">
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <label style="font-weight:600;">Quantity</label>
                <input type="number" name="quantity" value="1" min="1" max="999" class="form-control" style="width:100px;">
                <button type="submit" class="btn btn-primary btn-lg">Add to Basket</button>
            </div>
        </form>
        <?php elseif (!$inStock): ?>
        <p style="color:var(--danger);font-weight:600;margin-top:16px;">Currently out of stock.</p>
        <?php endif; ?>
    </div>
</div>
