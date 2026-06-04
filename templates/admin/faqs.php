<?php use App\Core\Security; ?>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">

<div class="page-header">
    <h1>Manage FAQs</h1>
    <p style="font-size:13px;color:#64748b;">These appear in the customer Help &amp; Email Setup page</p>
</div>

<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start;">

    <!-- Existing FAQs -->
    <div>
        <?php if (empty($faqs)): ?>
        <div class="card"><div class="empty-state">No FAQs yet — add one using the form.</div></div>
        <?php else: ?>
        <?php foreach ($faqs as $faq): ?>
        <div class="card" style="margin-bottom:12px;">
            <div class="card-body">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:14px;margin-bottom:6px;">
                            <?= Security::e($faq['question']) ?>
                            <?php if (!$faq['is_active']): ?>
                            <span class="badge badge-inactive" style="margin-left:6px;">Hidden</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:13px;color:#64748b;line-height:1.6;" class="faq-preview">
                            <?= $faq['answer'] ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                        <a href="/admin/faqs/<?= $faq['id'] ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" action="/admin/faqs/<?= $faq['id'] ?>/delete" onsubmit="return confirm('Delete this FAQ?')">
                            <?= Security::csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger-outline">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add FAQ -->
    <div class="card" style="position:sticky;top:32px;">
        <div class="card-header"><h2>Add FAQ</h2></div>
        <div class="card-body">
            <form method="POST" action="/admin/faqs/create" id="add-faq-form">
                <?= Security::csrfField() ?>
                <div class="form-group">
                    <label>Question <span class="required">*</span></label>
                    <input type="text" name="question" required placeholder="e.g. How do I reset my email password?">
                </div>
                <div class="form-group">
                    <label>Answer <span class="required">*</span></label>
                    <div id="add-quill-editor" style="min-height:140px;border-radius:0 0 8px 8px;font-size:14px;"></div>
                    <textarea name="answer" id="add-answer-field" style="display:none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Add FAQ</button>
            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const addQuill = new Quill('#add-quill-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'header': [2, 3, false] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet'}],
            ['link'], ['blockquote'], ['clean']
        ]
    },
    placeholder: 'Write a clear, helpful answer...'
});

document.getElementById('add-faq-form').addEventListener('submit', function(e) {
    const content = addQuill.root.innerHTML;
    if (content === '<p><br></p>' || !content.trim()) {
        e.preventDefault();
        alert('Please enter an answer.');
        return;
    }
    document.getElementById('add-answer-field').value = content;
});
</script>

<style>
.ql-toolbar.ql-snow { border-radius: 8px 8px 0 0 !important; border-color: #e2e8f0 !important; background: #f8fafc; }
.ql-container.ql-snow { border-color: #e2e8f0 !important; border-top: none !important; font-family: inherit; font-size: 14px; border-radius: 0 0 8px 8px; }
.ql-editor { min-height: 120px; }
.faq-preview ul, .faq-preview ol { padding-left: 18px; }
.faq-preview p { margin-bottom: 4px; }
</style>
