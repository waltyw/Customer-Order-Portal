<?php use App\Core\Security; ?>
<div class="page-header">
    <h1>Portal Settings</h1>
</div>

<form method="POST" action="/admin/settings" enctype="multipart/form-data" id="settingsForm">
    <?= Security::csrfField() ?>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

        <!-- Left: all settings -->
        <div>

            <!-- Branding -->
            <div class="card">
                <div class="card-header"><h2>Branding</h2></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Portal Name</label>
                            <input type="text" name="app_name" value="<?= Security::e($settings['app_name'] ?? '') ?>" placeholder="My Customer Portal">
                        </div>
                        <div class="form-group">
                            <label>Support Email</label>
                            <input type="email" name="support_email" value="<?= Security::e($settings['support_email'] ?? '') ?>" placeholder="support@yourdomain.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex:3;">
                            <label>Logo <span class="hint">(PNG, JPG, SVG or WebP — max 2MB)</span></label>
                            <?php
                            $logoExt  = $settings['logo_ext'] ?? '';
                            $logoFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.' . $logoExt;
                            $hasLogo  = $logoExt && file_exists($logoFile);
                            ?>
                            <?php if ($hasLogo): ?>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <img src="/assets/img/logo.<?= Security::e($logoExt) ?>?v=<?= filemtime($logoFile) ?>"
                                     alt="Current logo" style="height:32px;width:auto;">
                                <button type="button" class="btn btn-sm btn-danger-outline"
                                    onclick="if(confirm('Remove this logo?')) document.getElementById('delete-logo-form').submit()">
                                    Remove Logo
                                </button>
                            </div>
                            <?php else: ?>
                            <p style="font-size:12px;color:#94a3b8;margin-bottom:10px;">No logo uploaded — portal name shown instead.</p>
                            <?php endif; ?>
                            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.gif,.svg,.webp">
                            <small style="color:#64748b;font-size:12px;">Leave blank to keep existing logo.</small>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Favicon <span class="hint">(PNG, SVG or ICO — max 512KB)</span></label>
                            <?php
                            $favExt  = $settings['favicon_ext'] ?? '';
                            $favFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/favicon.' . $favExt;
                            $hasFav  = $favExt && file_exists($favFile);
                            ?>
                            <?php if ($hasFav): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                                <img src="/assets/img/favicon.<?= Security::e($favExt) ?>?v=<?= filemtime($favFile) ?>"
                                     alt="Favicon" style="width:28px;height:28px;object-fit:contain;">
                                <button type="button" class="btn btn-sm btn-danger-outline"
                                    onclick="if(confirm('Remove this favicon?')) document.getElementById('delete-favicon-form').submit()">
                                    Remove
                                </button>
                            </div>
                            <?php else: ?>
                            <p style="font-size:12px;color:#94a3b8;margin-bottom:10px;">No favicon uploaded.</p>
                            <?php endif; ?>
                            <input type="file" name="favicon" accept=".png,.ico,.svg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Logo / Brand Link URL</label>
                        <input type="text" name="logo_link_url"
                               value="<?= Security::e($settings['logo_link_url'] ?? '') ?>"
                               placeholder="https://beebizzi.co.uk (leave blank for dashboard)">
                        <small style="color:#64748b;font-size:12px;">Where clicking the logo takes users. Defaults to the dashboard if blank. External URLs open in a new tab automatically.</small>
                    </div>

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;max-width:400px;">
                            <input type="checkbox" name="invoices_enabled" value="1" <?= ($settings['invoices_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width:16px;height:16px;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">Enable Invoices &amp; Payments globally</div>
                                <div style="font-size:12px;color:#64748b;">Uncheck to hide invoices from ALL customers regardless of individual settings</div>
                            </div>
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="max-width:200px;">
                            <label>Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="<?= Security::e($settings['currency_symbol'] ?? '£') ?>" maxlength="3" placeholder="£">
                            <small style="color:#64748b;font-size:12px;">e.g. £ $ €</small>
                        </div>
                        <div class="form-group">
                            <label>Font</label>
                            <?php
                            $fonts = [
                                // Sans-serif — clean & modern
                                'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins',
                                'Raleway','Nunito','Source Sans 3','Ubuntu','Josefin Sans',
                                'Quicksand','Figtree','DM Sans','Plus Jakarta Sans',
                                // Serif — traditional & editorial
                                'Playfair Display','Merriweather','PT Sans','Lora','Bitter',
                                // Add more here — use the exact name from fonts.google.com
                            ];
                            $currentFont = $settings['font_family'] ?? 'Inter';
                            ?>
                            <select name="font_family" id="font-select" onchange="previewFont(this.value)">
                                <?php foreach ($fonts as $f): ?>
                                <option value="<?= Security::e($f) ?>" <?= $f === $currentFont ? 'selected' : '' ?>><?= Security::e($f) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="font-preview" style="margin-top:8px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:18px;color:#1e293b;transition:font-family .3s;">
                                The quick brown fox jumps over the lazy dog
                            </div>
                            <small style="color:#64748b;font-size:12px;">Preview updates as you change selection. Save to apply site-wide.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom menu links -->
            <div class="card">
                <div class="card-header">
                    <h2>Custom Sidebar Links</h2>
                    <span style="font-size:12px;color:#94a3b8;">Appear in the customer sidebar below the main nav</span>
                </div>
                <div class="card-body">
                    <table style="width:100%;border-collapse:collapse;" id="links-table">
                        <thead>
                            <tr style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">
                                <th style="padding:4px 8px 8px 0;text-align:left;">Label</th>
                                <th style="padding:4px 8px 8px;text-align:left;">URL</th>
                                <th style="padding:4px 8px 8px;text-align:center;width:80px;">New Tab</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="links-body">
                        <?php
                        $existingLinks = \App\Models\Setting::menuLinks();
                        foreach ($existingLinks as $i => $link):
                        ?>
                        <tr class="link-row">
                            <td style="padding:4px 8px 4px 0;"><input type="text" name="link_label[]" value="<?= Security::e($link['label']) ?>" placeholder="e.g. Main Website" style="width:100%;"></td>
                            <td style="padding:4px 8px;"><input type="text" name="link_url[]" value="<?= Security::e($link['url']) ?>" placeholder="https://..." style="width:100%;"></td>
                            <td style="padding:4px 8px;text-align:center;"><input type="checkbox" name="link_newtab[<?= $i ?>]" value="1" <?= !empty($link['new_tab']) ? 'checked' : '' ?>></td>
                            <td style="padding:4px 0;"><button type="button" class="btn btn-sm btn-danger-outline" onclick="this.closest('tr').remove()">×</button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline btn-sm" style="margin-top:10px;" onclick="addLinkRow()">+ Add Link</button>
                    <small style="display:block;margin-top:6px;color:#64748b;font-size:12px;">Links with an external URL (starting with http) automatically open in a new tab and show an external link icon.</small>
                </div>
            </div>

            <!-- Sidebar colours -->
            <div class="card">
                <div class="card-header"><h2>Sidebar Colours</h2></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                        <?php
                        $sidebarFields = [
                            'sidebar_bg'     => ['Sidebar Background', 'Main sidebar colour'],
                            'sidebar_text'   => ['Nav Item Text',      'Unselected menu items'],
                            'sidebar_active' => ['Active Item',        'Selected menu item'],
                        ];
                        foreach ($sidebarFields as $key => [$label, $hint]):
                            $val = Security::e($settings[$key] ?? '#000000');
                        ?>
                        <div class="form-group">
                            <label><?= $label ?></label>
                            <small style="color:#64748b;font-size:11px;display:block;margin-bottom:6px;"><?= $hint ?></small>
                            <input type="color" id="<?= $key ?>" name="<?= $key ?>" value="<?= $val ?>"
                                   style="width:100%;height:40px;padding:2px 4px;border-radius:6px;cursor:pointer;border:1px solid #e2e8f0;"
                                   oninput="syncHex('<?= $key ?>')">
                            <input type="text" id="<?= $key ?>_hex" value="<?= $val ?>"
                                   maxlength="7" style="margin-top:6px;font-family:monospace;font-size:13px;"
                                   oninput="syncPicker('<?= $key ?>')">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Accent colours -->
            <div class="card">
                <div class="card-header"><h2>Accent Colours</h2></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <?php
                        $accentFields = [
                            'primary_color' => ['Primary Colour',       'Buttons, links, badges'],
                            'primary_dark'  => ['Primary Hover',        'Button hover state'],
                        ];
                        foreach ($accentFields as $key => [$label, $hint]):
                            $val = Security::e($settings[$key] ?? '#2563eb');
                        ?>
                        <div class="form-group">
                            <label><?= $label ?></label>
                            <small style="color:#64748b;font-size:11px;display:block;margin-bottom:6px;"><?= $hint ?></small>
                            <input type="color" id="<?= $key ?>" name="<?= $key ?>" value="<?= $val ?>"
                                   style="width:100%;height:40px;padding:2px 4px;border-radius:6px;cursor:pointer;border:1px solid #e2e8f0;"
                                   oninput="syncHex('<?= $key ?>')">
                            <input type="text" id="<?= $key ?>_hex" value="<?= $val ?>"
                                   maxlength="7" style="margin-top:6px;font-family:monospace;font-size:13px;"
                                   oninput="syncPicker('<?= $key ?>')">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Text & background colours -->
            <div class="card">
                <div class="card-header"><h2>Text &amp; Background Colours</h2></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px;">
                        <?php
                        $textFields = [
                            'text_color' => ['Main Text',         '#1e293b'],
                            'text_muted' => ['Muted Text',        '#64748b'],
                            'body_bg'    => ['Page Background',   '#f8fafc'],
                            'card_bg'    => ['Card Background',   '#ffffff'],
                        ];
                        foreach ($textFields as $key => [$label, $default]):
                            $val = Security::e($settings[$key] ?? $default);
                        ?>
                        <div class="form-group">
                            <label><?= $label ?></label>
                            <input type="color" id="<?= $key ?>" name="<?= $key ?>" value="<?= $val ?>"
                                   style="width:100%;height:40px;padding:2px 4px;border-radius:6px;cursor:pointer;border:1px solid #e2e8f0;"
                                   oninput="syncHex('<?= $key ?>')">
                            <input type="text" id="<?= $key ?>_hex" value="<?= $val ?>"
                                   maxlength="7" style="margin-top:6px;font-family:monospace;font-size:13px;"
                                   oninput="syncPicker('<?= $key ?>')">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="button" class="btn btn-outline" onclick="resetDefaults()">Reset Defaults</button>
            </div>
        </div>

        <!-- Right: live preview -->
        <div style="position:sticky;top:32px;">
            <div class="card" style="overflow:hidden;">
                <div class="card-header"><h2>Live Preview</h2></div>

                <!-- Mini sidebar -->
                <div id="prev-sidebar" style="padding:14px;transition:background .2s;">
                    <div style="background:#fff;border-radius:7px;padding:5px 8px;margin-bottom:12px;text-align:center;">
                        <img src="/assets/img/logo.<?= Security::e($settings['logo_ext'] ?? 'png') ?>" style="height:22px;width:auto;">
                    </div>
                    <div id="prev-active" style="display:flex;align-items:center;gap:7px;padding:7px 9px;border-radius:6px;font-size:12px;font-weight:600;color:#fff;margin-bottom:3px;transition:background .2s;">
                        <div style="width:10px;height:10px;background:rgba(255,255,255,.5);border-radius:2px;flex-shrink:0;"></div>
                        Dashboard
                    </div>
                    <div id="prev-inactive" style="display:flex;align-items:center;gap:7px;padding:7px 9px;border-radius:6px;font-size:12px;margin-bottom:3px;transition:color .2s;">
                        <div style="width:10px;height:10px;background:rgba(255,255,255,.2);border-radius:2px;flex-shrink:0;"></div>
                        <span>Tickets</span>
                    </div>
                    <div id="prev-inactive2" style="display:flex;align-items:center;gap:7px;padding:7px 9px;border-radius:6px;font-size:12px;transition:color .2s;">
                        <div style="width:10px;height:10px;background:rgba(255,255,255,.2);border-radius:2px;flex-shrink:0;"></div>
                        <span>Invoices</span>
                    </div>
                </div>

                <!-- Content area -->
                <div id="prev-body" style="padding:14px;transition:background .2s;">
                    <div id="prev-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:10px;transition:background .2s;">
                        <div id="prev-text" style="font-size:13px;font-weight:600;margin-bottom:4px;transition:color .2s;">Customer Dashboard</div>
                        <div id="prev-muted" style="font-size:11px;transition:color .2s;">Welcome back to your portal</div>
                    </div>
                    <div id="prev-btn" style="display:inline-block;padding:7px 14px;border-radius:6px;font-size:12px;font-weight:600;color:#fff;transition:background .2s;">
                        Pay Invoice
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
// Keep color picker and hex text input in sync
function syncHex(key) {
    document.getElementById(key + '_hex').value = document.getElementById(key).value;
    updatePreview();
}

function syncPicker(key) {
    const hex = document.getElementById(key + '_hex').value;
    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        document.getElementById(key).value = hex;
        updatePreview();
    }
}

function get(key) {
    return document.getElementById(key).value;
}

function updatePreview() {
    document.getElementById('prev-sidebar').style.background  = get('sidebar_bg');
    document.getElementById('prev-active').style.background   = get('sidebar_active');
    document.getElementById('prev-inactive').style.color      = get('sidebar_text');
    document.getElementById('prev-inactive2').style.color     = get('sidebar_text');
    document.getElementById('prev-body').style.background     = get('body_bg');
    document.getElementById('prev-card').style.background     = get('card_bg');
    document.getElementById('prev-text').style.color          = get('text_color');
    document.getElementById('prev-muted').style.color         = get('text_muted');
    document.getElementById('prev-btn').style.background      = get('primary_color');
}

function resetDefaults() {
    const defaults = {
        sidebar_bg:     '#0f172a',
        sidebar_text:   '#94a3b8',
        sidebar_active: '#2563eb',
        primary_color:  '#2563eb',
        primary_dark:   '#1d4ed8',
        body_bg:        '#f8fafc',
        text_color:     '#1e293b',
        text_muted:     '#64748b',
        card_bg:        '#ffffff',
    };
    Object.entries(defaults).forEach(([key, val]) => {
        const picker = document.getElementById(key);
        const hex    = document.getElementById(key + '_hex');
        if (picker) picker.value = val;
        if (hex)    hex.value    = val;
    });
    updatePreview();
}

// Run once on load to initialise preview
updatePreview();

// Custom link row counter (for unique checkbox names)
let linkRowIndex = <?= count(\App\Models\Setting::menuLinks()) ?>;

function addLinkRow() {
    const tbody = document.getElementById('links-body');
    const tr = document.createElement('tr');
    tr.className = 'link-row';
    tr.innerHTML = `
        <td style="padding:4px 8px 4px 0;"><input type="text" name="link_label[]" placeholder="e.g. WHM Login" style="width:100%;"></td>
        <td style="padding:4px 8px;"><input type="text" name="link_url[]" placeholder="https://..." style="width:100%;"></td>
        <td style="padding:4px 8px;text-align:center;"><input type="checkbox" name="link_newtab[${linkRowIndex}]" value="1" checked></td>
        <td style="padding:4px 0;"><button type="button" class="btn btn-sm btn-danger-outline" onclick="this.closest('tr').remove()">×</button></td>
    `;
    tbody.appendChild(tr);
    linkRowIndex++;
    tr.querySelector('input[type=text]').focus();
}

// Google Font live preview
const loadedFonts = new Set(['Inter']);
function previewFont(fontName) {
    const preview = document.getElementById('font-preview');
    if (!loadedFonts.has(fontName)) {
        const link = document.createElement('link');
        link.rel  = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fontName) + ':wght@400;500;600;700&display=swap';
        document.head.appendChild(link);
        loadedFonts.add(fontName);
    }
    preview.style.fontFamily = "'" + fontName + "', sans-serif";
}
// Set initial preview font
previewFont(document.getElementById('font-select').value);
</script>

<!-- Delete forms outside the main settings form to avoid nesting -->
<form id="delete-logo-form" method="POST" action="/admin/settings/delete-logo" style="display:none;">
    <?= Security::csrfField() ?>
</form>
<form id="delete-favicon-form" method="POST" action="/admin/settings/delete-favicon" style="display:none;">
    <?= Security::csrfField() ?>
</form>
