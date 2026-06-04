<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Stock Management</h1>
        <p class="page-subtitle"><?= count($lowStock) ?> product(s) at or below low-stock threshold</p>
    </div>
    <a href="/admin/shop/products/export" class="btn btn-secondary">Export CSV</a>
</div>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning" style="margin-bottom:24px;">
    <strong>Low Stock Alert:</strong> <?= count($lowStock) ?> product(s) need restocking.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th style="width:120px;text-align:center;">Current Stock</th>
                    <th style="width:120px;text-align:center;">Threshold</th>
                    <th style="width:200px;">Update Stock</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p):
                $isLow = (int)$p['stock'] <= (int)$p['low_stock_threshold'];
            ?>
            <tr class="<?= $isLow ? 'row-warning' : '' ?>">
                <td>
                    <strong><?= $e($p['name']) ?></strong>
                    <?php if ($isLow): ?>
                    <span class="badge badge-warning" style="margin-left:6px;font-size:11px;">Low stock</span>
                    <?php endif; ?>
                </td>
                <td style="font-family:monospace;font-size:13px;color:var(--text-muted);"><?= $e($p['sku']) ?></td>
                <td style="text-align:center;">
                    <strong style="<?= (int)$p['stock'] <= 0 ? 'color:var(--danger)' : ($isLow ? 'color:var(--warning)' : '') ?>">
                        <?= (int)$p['stock'] ?>
                    </strong>
                </td>
                <td style="text-align:center;color:var(--text-muted);"><?= (int)$p['low_stock_threshold'] ?></td>
                <td>
                    <form method="post" action="/admin/shop/stock/<?= (int)$p['id'] ?>/update" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" class="form-control" style="max-width:90px;">
                        <button type="submit" class="btn btn-sm btn-primary">Set</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
