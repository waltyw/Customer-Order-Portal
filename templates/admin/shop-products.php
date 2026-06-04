<?php $e = fn($v) => \App\Core\Security::e($v); ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle">
            <?= (int)$counts['total'] ?> total &bull;
            <?= (int)$counts['active'] ?> active &bull;
            <span style="color:var(--warning)"><?= (int)$counts['low_stock'] ?> low stock</span>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="/admin/shop/products/create" class="btn btn-primary">+ Add Product</a>
        <a href="/admin/shop/stock" class="btn btn-secondary">Stock</a>
        <a href="/admin/shop/products/export" class="btn btn-secondary">Export CSV</a>
        <form method="post" action="/admin/shop/products/import" enctype="multipart/form-data" style="display:flex;gap:6px;">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::csrfToken() ?>">
            <input type="file" name="csv" accept=".csv" class="form-control" style="max-width:200px;" required>
            <button type="submit" class="btn btn-secondary">Import</button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px;">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <input type="text" name="q" value="<?= $e($search) ?>" placeholder="Search by name or SKU…" class="form-control" style="max-width:280px;">
            <select name="category" class="form-control" style="max-width:200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= $e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || !empty($_GET['category'])): ?>
            <a href="/admin/shop/products" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($products)): ?>
        <div class="empty-state" style="padding:40px;">
            <p style="color:var(--text-muted);">No products found. <a href="/admin/shop/products/create">Add your first product</a>.</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:56px;"></th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th style="width:80px;text-align:center;">Stock</th>
                    <th style="width:70px;text-align:center;">Active</th>
                    <th style="width:100px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <?php $imgs = \App\Models\Product::images((int)$p['id']);
                    $primary = null;
                    foreach ($imgs as $img) { if ($img['is_primary']) { $primary = $img; break; } }
                    if (!$primary && !empty($imgs)) $primary = $imgs[0];
                    ?>
                    <?php if ($primary): ?>
                    <img src="/assets/img/products/<?= $e($primary['filename']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                    <?php else: ?>
                    <div style="width:40px;height:40px;background:var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/admin/shop/products/<?= (int)$p['id'] ?>/edit" style="font-weight:600;"><?= $e($p['name']) ?></a>
                    <?php if ((int)$p['stock'] <= (int)$p['low_stock_threshold']): ?>
                    <span class="badge badge-warning" style="margin-left:6px;font-size:11px;">Low stock</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:13px;font-family:monospace;"><?= $e($p['sku']) ?></td>
                <td style="color:var(--text-muted);font-size:13px;"><?= $e($p['category_name'] ?? '—') ?></td>
                <td style="text-align:center;">
                    <span style="font-weight:600;<?= (int)$p['stock'] <= 0 ? 'color:var(--danger)' : ((int)$p['stock'] <= (int)$p['low_stock_threshold'] ? 'color:var(--warning)' : '') ?>">
                        <?= (int)$p['stock'] ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <?php if ($p['is_active']): ?>
                    <span style="color:var(--success);">●</span>
                    <?php else: ?>
                    <span style="color:var(--text-muted);">●</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="/admin/shop/products/<?= (int)$p['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
                        <form method="post" action="/admin/shop/products/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Delete this product?')">
                            <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::csrfToken() ?>">
                            <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;">Del</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
