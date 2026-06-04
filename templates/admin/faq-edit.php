<?php use App\Core\Security; ?>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">

<div class="page-header">
    <h1>Edit FAQ</h1>
    <a href="/admin/faqs" class="btn btn-outline">&larr; Back</a>
</div>

<div class="card" style="max-width:760px;">
    <div class="card-body">
        <form method="POST" action="/admin/faqs/<?= $faq['id'] ?>/update" id="faq-form">
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label>Question <span class="required">*</span></label>
                <input type="text" name="question" required value="<?= Security::e($faq['question']) ?>">
            </div>
            <div class="form-group">
                <label>Answer <span class="required">*</span></label>
                <div id="quill-editor" style="min-height:180px;border:1px solid #e2e8f0;border-radius:0 0 8px 8px;font-size:14px;"></div>
                <textarea name="answer" id="answer-field" style="display:none;"><?= Security::e($faq['answer']) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Sort Order <span class="hint">(lower = higher up)</span></label>
                    <input type="number" name="sort_order" value="<?= (int)$faq['sort_order'] ?>" min="0" step="10">
                </div>
                <div class="form-group" style="justify-content:flex-end;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:24px;">
                        <input type="checkbox" name="is_active" value="1" <?= $faq['is_active'] ? 'checked' : '' ?>>
                        Show on Help page
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/admin/faqs" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const quill = new Quill('#quill-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'header': [2, 3, false] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['link'],
            ['blockquote'],
            ['clean']
        ]
    },
    placeholder: 'Write your answer here...'
});

// Load existing content
const existing = document.getElementById('answer-field').value;
if (existing) {
    quill.root.innerHTML = existing;
}

// Before submit, copy Quill HTML into the hidden textarea
document.getElementById('faq-form').addEventListener('submit', function () {
    document.getElementById('answer-field').value = quill.root.innerHTML;
});
</script>

<style>
.ql-toolbar { border-radius: 8px 8px 0 0; border-color: #e2e8f0 !important; background: #f8fafc; }
.ql-container { border-color: #e2e8f0 !important; border-top: none !important; font-family: inherit; font-size: 14px; }
.ql-editor { min-height: 160px; }
</style>
