<?php
$e    = fn($v) => \App\Core\Security::e($v);
$csrf = \App\Core\Security::csrfToken();
// Build parent options (top-level only)
$topLevel = array_filter($categories, fn($c) => !$c['parent_id']);
?>
<div class="page-header">
    <h1 class="page-title">Product Categories</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;">
    <!-- Category list -->
    <div class="card">
        <div class="card-body" style="padding:0;">
            <?php if (empty($categories)): ?>
            <div class="empty-state" style="padding:32px;"><p style="color:var(--text-muted);">No categories yet.</p></div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Name</th><th>Parent</th><th style="width:60px;text-align:center;">Active</th><th style="width:60px;text-align:center;">Sort</th><th style="width:100px;"></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <strong><?= $e($cat['name']) ?></strong>
                        <div style="color:var(--text-muted);font-size:12px;font-family:monospace;"><?= $e($cat['slug']) ?></div>
                    </td>
                    <td style="color:var(--text-muted);"><?= $e($cat['parent_name'] ?? '—') ?></td>
                    <td style="text-align:center;"><?= $cat['is_active'] ? '<span style="color:var(--success);">●</span>' : '<span style="color:var(--text-muted);">●</span>' ?></td>
                    <td style="text-align:center;"><?= (int)$cat['sort_order'] ?></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button onclick="editCat(<?= htmlspecialchars(json_encode($cat)) ?>)" class="btn btn-sm btn-secondary">Edit</button>
                            <form method="post" action="/admin/shop/categories/<?= (int)$cat['id'] ?>/delete" onsubmit="return confirm('Delete category?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
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

    <!-- Add/Edit form -->
    <div>
        <div class="card">
            <div class="card-header"><h3 class="card-title" id="catFormTitle">Add Category</h3></div>
            <div class="card-body">
                <form method="post" id="catForm" action="/admin/shop/categories/create">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="catName" class="form-control" required oninput="autoCatSlug(this)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" id="catSlug" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" id="catParent" class="form-control">
                            <option value="">— Top level —</option>
                            <?php foreach ($topLevel as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= $e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="catDesc" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="catSort" class="form-control" value="0">
                        </div>
                        <div class="form-group" style="display:flex;align-items:flex-end;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:6px;">
                                <input type="checkbox" name="is_active" id="catActive" value="1" checked> Active
                            </label>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary">Save Category</button>
                        <button type="button" class="btn btn-secondary" onclick="resetCatForm()">New</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function autoCatSlug(input) {
    const s = document.getElementById('catSlug');
    if (!s.dataset.manual) s.value = input.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
document.getElementById('catSlug').addEventListener('input', function(){ this.dataset.manual='1'; });

function editCat(cat) {
    document.getElementById('catFormTitle').textContent = 'Edit Category';
    document.getElementById('catForm').action = '/admin/shop/categories/' + cat.id + '/update';
    document.getElementById('catName').value   = cat.name || '';
    document.getElementById('catSlug').value   = cat.slug || '';
    document.getElementById('catSlug').dataset.manual = '1';
    document.getElementById('catParent').value = cat.parent_id || '';
    document.getElementById('catDesc').value   = cat.description || '';
    document.getElementById('catSort').value   = cat.sort_order || 0;
    document.getElementById('catActive').checked = !!parseInt(cat.is_active);
}

function resetCatForm() {
    document.getElementById('catFormTitle').textContent = 'Add Category';
    document.getElementById('catForm').action = '/admin/shop/categories/create';
    document.getElementById('catForm').reset();
    delete document.getElementById('catSlug').dataset.manual;
}
</script>
