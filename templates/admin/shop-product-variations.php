<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
$symbol = \App\Models\Setting::get('currency_symbol') ?: '£';
?>
<div class="page-header">
    <div>
        <a href="/admin/shop/products/<?= (int)$product['id'] ?>/edit" style="color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px;margin-bottom:8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            <?= $e($product['name']) ?>
        </a>
        <h1 class="page-title">Variations</h1>
    </div>
</div>

<!-- Existing variations -->
<?php if (!empty($variations)): ?>
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Existing Variations (<?= count($variations) ?>)</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Attributes</th>
                    <?php foreach ($tiers as $tier): ?>
                    <th style="text-align:right;"><?= $e($tier['name']) ?></th>
                    <?php endforeach; ?>
                    <th style="width:80px;text-align:center;">Stock</th>
                    <th style="width:70px;text-align:center;">Active</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($variations as $var):
                $varPrices = \App\Models\ProductVariation::prices((int)$var['id']);
            ?>
            <tr>
                <td style="font-family:monospace;font-size:13px;"><?= $e($var['sku']) ?></td>
                <td style="font-size:13px;">
                    <?php foreach ($var['attributes'] as $a): ?>
                    <span class="badge badge-outline" style="margin-right:4px;"><?= $e($a['attribute_name']) ?>: <?= $e($a['term_name']) ?></span>
                    <?php endforeach; ?>
                </td>
                <?php foreach ($tiers as $tier): ?>
                <td style="text-align:right;font-size:13px;">
                    <?= isset($varPrices[(int)$tier['id']]) ? $symbol . number_format((float)$varPrices[(int)$tier['id']]['price'], 2) : '—' ?>
                </td>
                <?php endforeach; ?>
                <td style="text-align:center;"><?= (int)$var['stock'] ?></td>
                <td style="text-align:center;"><?= $var['is_active'] ? '<span style="color:var(--success)">●</span>' : '<span style="color:var(--text-muted)">●</span>' ?></td>
                <td>
                    <form method="post" action="/admin/shop/products/<?= (int)$product['id'] ?>/variations/<?= (int)$var['id'] ?>/delete" onsubmit="return confirm('Delete variation?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add variation -->
<div class="card">
    <div class="card-header"><h3 class="card-title">Add Variation</h3></div>
    <div class="card-body">
        <form method="post" action="/admin/shop/products/<?= (int)$product['id'] ?>/variations/create">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">SKU <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="sku" class="form-control" required placeholder="e.g. PROD-001-RED-L">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" value="0" min="0">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:6px;">
                        <input type="checkbox" name="is_active" value="1" checked> Active
                    </label>
                </div>
            </div>

            <?php if (!empty($attributes)): ?>
            <div style="margin-bottom:16px;">
                <label class="form-label" style="margin-bottom:8px;">Attribute Values</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
                    <?php foreach ($attributes as $attr): ?>
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;"><?= $e($attr['attr_name']) ?></label>
                        <?php
                        $terms = \App\Core\DB::fetchAll(
                            'SELECT pat.* FROM product_attribute_terms pat
                             JOIN product_attribute_maps pam ON pam.attribute_id = pat.attribute_id
                             WHERE pam.product_id = ? AND pam.attribute_id = ?
                             ORDER BY pat.sort_order ASC',
                            [(int)$product['id'], (int)$attr['attribute_id']]
                        );
                        // If no mapped terms, show text input
                        if (empty($terms)): ?>
                        <input type="text" name="attr[<?= (int)$attr['attribute_id'] ?>]" class="form-control" placeholder="Enter value">
                        <?php else: ?>
                        <select name="attr[<?= (int)$attr['attribute_id'] ?>]" class="form-control">
                            <option value="">— Select —</option>
                            <?php foreach ($terms as $term): ?>
                            <option value="<?= $e($term['name']) ?>"><?= $e($term['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">
                No attributes set on this product. <a href="/admin/shop/products/<?= (int)$product['id'] ?>/edit">Add attributes</a> first to define variation options.
            </p>
            <?php endif; ?>

            <div style="margin-bottom:16px;">
                <label class="form-label" style="margin-bottom:8px;">Tier Prices</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
                    <?php foreach ($tiers as $tier): ?>
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px;"><?= $e($tier['name']) ?></label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);"><?= $symbol ?></span>
                            <input type="number" name="prices[<?= (int)$tier['id'] ?>]" step="0.0001" min="0" class="form-control" style="padding-left:24px;" placeholder="0.00">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Add Variation</button>
        </form>
    </div>
</div>
