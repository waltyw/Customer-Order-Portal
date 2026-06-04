<?php
use App\Core\Security;
use App\Models\ServiceStatus;
?>
<div class="page-header">
    <h1>Help &amp; Email Setup Guides</h1>
    <a href="#raise-ticket" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Raise a Support Ticket
    </a>
</div>

<!-- Service Status Board -->
<?php if (!empty($services)): ?>
<?php
$overallBg     = ServiceStatus::statusBg($overallStatus);
$overallColour = ServiceStatus::statusColour($overallStatus);
$overallLabel  = ServiceStatus::statusLabel($overallStatus);
$icons = ['operational'=>'✅','degraded'=>'⚠️','outage'=>'🔴','maintenance'=>'🔧'];
?>
<div class="card" style="margin-bottom:24px;border-color:<?= $overallColour ?>40;">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;background:<?= $overallBg ?>;border-bottom:1px solid <?= $overallColour ?>30;border-radius:10px 10px 0 0;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:20px;"><?= $icons[$overallStatus] ?? '?' ?></span>
            <div>
                <div style="font-weight:700;color:<?= $overallColour ?>;font-size:15px;"><?= $overallLabel ?></div>
                <div style="font-size:12px;color:#64748b;">Updated <?= date('j F Y, H:i') ?></div>
            </div>
        </div>
    </div>
    <div style="padding:0;">
        <?php foreach ($services as $svc): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid #f1f5f9;">
            <span style="font-size:14px;font-weight:500;"><?= Security::e($svc['service_name']) ?></span>
            <div style="text-align:right;">
                <span style="background:<?= ServiceStatus::statusBg($svc['status']) ?>;color:<?= ServiceStatus::statusColour($svc['status']) ?>;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                    <?= ServiceStatus::statusLabel($svc['status']) ?>
                </span>
                <?php if ($svc['message']): ?>
                <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= Security::e($svc['message']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!$hasMailServer): ?>
<div class="alert alert-info">
    Your mail server details haven't been set up on your account yet. The guides below show placeholder values —
    <a href="/tickets/create">raise a support ticket</a> and we'll add your email settings.
</div>
<?php endif; ?>

<!-- Email settings reference card -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#0f172a,#1e3a5f);border:none;">
    <div class="card-body">
        <h2 style="color:#fff;font-size:16px;margin-bottom:16px;">Your Email Server Settings</h2>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
            <div>
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Incoming Mail (IMAP)</div>
                <div class="settings-row"><span>Server</span><code><?= Security::e($mailServer) ?></code></div>
                <div class="settings-row"><span>Port</span><code>993</code></div>
                <div class="settings-row"><span>Security</span><code>SSL/TLS</code></div>
                <div class="settings-row"><span>Username</span><code>your full email address</code></div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Outgoing Mail (SMTP)</div>
                <div class="settings-row"><span>Server</span><code><?= Security::e($mailServer) ?></code></div>
                <div class="settings-row"><span>Port</span><code>465</code></div>
                <div class="settings-row"><span>Security</span><code>SSL/TLS</code></div>
                <div class="settings-row"><span>Username</span><code>your full email address</code></div>
            </div>
        </div>
    </div>
</div>

<!-- Setup guides accordion -->
<div class="faq-list">

    <!-- Outlook Windows -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#0078d4;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M21.5 3h-19C1.7 3 1 3.7 1 4.5v15c0 .8.7 1.5 1.5 1.5h19c.8 0 1.5-.7 1.5-1.5v-15c0-.8-.7-1.5-1.5-1.5zm-10 13L4 10.5l1.4-1.4L11.5 15l7.1-7.1L20 9.3 11.5 16z"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">Microsoft Outlook (Windows)</div>
                    <div style="font-size:12px;color:#64748b;">Outlook 2016, 2019, 2021 &amp; Microsoft 365</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <ol class="guide-steps">
                <li>Open Outlook and click <strong>File → Add Account</strong></li>
                <li>Enter your full email address and click <strong>Advanced options</strong> → tick <strong>Let me set up my account manually</strong> → <strong>Connect</strong></li>
                <li>Choose <strong>IMAP</strong></li>
                <li>Fill in the <strong>Incoming Mail</strong> settings:
                    <div class="settings-block">
                        <div class="settings-row"><span>Server</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Port</span><code>993</code></div>
                        <div class="settings-row"><span>Encryption</span><code>SSL/TLS</code></div>
                    </div>
                </li>
                <li>Fill in the <strong>Outgoing Mail</strong> settings:
                    <div class="settings-block">
                        <div class="settings-row"><span>Server</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Port</span><code>465</code></div>
                        <div class="settings-row"><span>Encryption</span><code>SSL/TLS</code></div>
                    </div>
                </li>
                <li>Click <strong>Next</strong>, enter your email password and click <strong>Connect</strong></li>
            </ol>
        </div>
    </div>

    <!-- Apple Mail Mac -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#555;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.4c1.39.07 2.37.74 3.18.8 1.2-.24 2.37-.93 3.64-.84 1.55.12 2.73.72 3.5 1.84-3.22 1.93-2.68 5.9.48 7.09-.64 1.68-1.47 3.32-2.8 4zm-3.2-17.5c-.06 2.24-1.63 4.02-3.63 3.87-.3-1.97 1.6-4.05 3.63-3.87z"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">Apple Mail (Mac)</div>
                    <div style="font-size:12px;color:#64748b;">macOS Mail app</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <ol class="guide-steps">
                <li>Open <strong>Mail</strong> → click <strong>Mail</strong> in the menu bar → <strong>Add Account</strong></li>
                <li>Select <strong>Other Mail Account…</strong> and click <strong>Continue</strong></li>
                <li>Enter your name, email address and password → click <strong>Sign In</strong></li>
                <li>If it can't auto-detect the settings, enter them manually:
                    <div class="settings-block">
                        <div class="settings-row"><span>Account Type</span><code>IMAP</code></div>
                        <div class="settings-row"><span>Incoming Server</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Outgoing Server</span><code><?= Security::e($mailServer) ?></code></div>
                    </div>
                </li>
                <li>Click <strong>Sign In</strong> — Mail will verify and set up the account</li>
                <li>To confirm ports: go to <strong>Mail → Preferences → Accounts</strong> → select your account → <strong>Server Settings</strong>
                    <div class="settings-block">
                        <div class="settings-row"><span>IMAP Port</span><code>993</code></div>
                        <div class="settings-row"><span>SMTP Port</span><code>465</code></div>
                        <div class="settings-row"><span>TLS</span><code>Enabled</code></div>
                    </div>
                </li>
            </ol>
        </div>
    </div>

    <!-- iPhone / iPad -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#1c1c1e;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><rect x="7" y="1" width="10" height="22" rx="2" ry="2"/><circle cx="12" cy="19" r="1"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">iPhone &amp; iPad (iOS / iPadOS)</div>
                    <div style="font-size:12px;color:#64748b;">Built-in Mail app</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <ol class="guide-steps">
                <li>Go to <strong>Settings → Mail → Accounts → Add Account</strong></li>
                <li>Tap <strong>Other</strong> at the bottom of the list</li>
                <li>Tap <strong>Add Mail Account</strong></li>
                <li>Enter your name, email, password and a description → tap <strong>Next</strong></li>
                <li>Make sure <strong>IMAP</strong> is selected at the top, then fill in:
                    <div class="settings-block">
                        <div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:4px;">Incoming Mail Server</div>
                        <div class="settings-row"><span>Host Name</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Username</span><code>your full email address</code></div>
                        <div style="font-size:12px;font-weight:600;color:#64748b;margin:8px 0 4px;">Outgoing Mail Server</div>
                        <div class="settings-row"><span>Host Name</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Username</span><code>your full email address</code></div>
                    </div>
                </li>
                <li>Tap <strong>Next</strong> — iOS will verify the settings automatically</li>
                <li>To confirm ports, go back to <strong>Settings → Mail → Accounts</strong> → tap your account → <strong>Account → Advanced</strong>:
                    <div class="settings-block">
                        <div class="settings-row"><span>IMAP Port</span><code>993</code></div>
                        <div class="settings-row"><span>Use SSL</span><code>On</code></div>
                    </div>
                </li>
            </ol>
        </div>
    </div>

    <!-- Android -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#3ddc84;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85a.637.637 0 0 0-.83.22l-1.88 3.24a11.46 11.46 0 0 0-8.94 0L5.65 5.67a.643.643 0 0 0-.87-.2c-.28.18-.37.54-.2.83L6.4 9.48A10.78 10.78 0 0 0 1 18h22a10.78 10.78 0 0 0-5.4-8.52zM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">Android</div>
                    <div style="font-size:12px;color:#64748b;">Gmail app or built-in Mail</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <ol class="guide-steps">
                <li>Open the <strong>Gmail</strong> app (or your device's Mail app) → tap the menu (☰) → <strong>Settings → Add account</strong></li>
                <li>Choose <strong>Other</strong> (not Google or Exchange)</li>
                <li>Enter your email address → tap <strong>Manual Setup</strong></li>
                <li>Choose <strong>IMAP</strong></li>
                <li>Fill in the <strong>incoming server</strong> settings:
                    <div class="settings-block">
                        <div class="settings-row"><span>IMAP Server</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Security</span><code>SSL/TLS</code></div>
                        <div class="settings-row"><span>Port</span><code>993</code></div>
                    </div>
                </li>
                <li>Fill in the <strong>outgoing server</strong> settings:
                    <div class="settings-block">
                        <div class="settings-row"><span>SMTP Server</span><code><?= Security::e($mailServer) ?></code></div>
                        <div class="settings-row"><span>Security</span><code>SSL/TLS</code></div>
                        <div class="settings-row"><span>Port</span><code>465</code></div>
                        <div class="settings-row"><span>Require Sign-in</span><code>Yes</code></div>
                    </div>
                </li>
                <li>Enter your password when prompted → tap <strong>Next</strong></li>
            </ol>
        </div>
    </div>

    <!-- Webmail -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#2563eb;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">Webmail (any browser)</div>
                    <div style="font-size:12px;color:#64748b;">Access email without setting anything up</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <p style="margin-bottom:12px;">You can access your email from any browser without any setup — just visit your webmail address:</p>
            <div class="settings-block">
                <div class="settings-row"><span>Webmail URL</span><code><?= $hasMailServer ? 'https://' . Security::e($mailServer) : 'https://mail.yourdomain.com' ?></code></div>
                <div class="settings-row"><span>Username</span><code>your full email address</code></div>
            </div>
            <p style="margin-top:12px;color:#64748b;font-size:13px;">Roundcube is the recommended webmail client. If you see multiple options, choose Roundcube.</p>
        </div>
    </div>

    <!-- General FAQ -->
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#7c3aed;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <div style="font-weight:600;">Common Questions</div>
                    <div style="font-size:12px;color:#64748b;">IMAP vs POP3, passwords, troubleshooting</div>
                </div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <div class="mini-faq">
                <div class="mini-faq-q">Should I use IMAP or POP3?</div>
                <div class="mini-faq-a">Always use <strong>IMAP</strong>. IMAP keeps your emails on the server so they sync across all your devices. POP3 downloads emails to one device and deletes them from the server — if you lose that device, you lose your emails.</div>
            </div>
            <div class="mini-faq">
                <div class="mini-faq-q">My emails aren't sending — what should I check?</div>
                <div class="mini-faq-a">Check that your <strong>SMTP port is 465</strong> with <strong>SSL/TLS</strong> enabled, and that you're using your full email address as the username. Some ISPs block port 465 — if so, try port 587 with STARTTLS instead.</div>
            </div>
            <div class="mini-faq">
                <div class="mini-faq-q">I've forgotten my email password</div>
                <div class="mini-faq-a">Email passwords are managed separately from this portal. Please <a href="/tickets/create">raise a support ticket</a> and we'll reset it for you.</div>
            </div>
            <div class="mini-faq">
                <div class="mini-faq-q">Outlook is asking me to accept a security certificate</div>
                <div class="mini-faq-a">This can happen if the mail server name doesn't exactly match the SSL certificate. Try using <code><?= Security::e($mailServer) ?></code> as the server name. If the warning persists, <a href="/tickets/create">contact support</a>.</div>
            </div>
            <div class="mini-faq">
                <div class="mini-faq-q">How much email storage do I have?</div>
                <div class="mini-faq-a">Storage limits depend on your hosting plan. <a href="/tickets/create">Raise a support ticket</a> if you need to check or increase your mailbox quota.</div>
            </div>
        </div>
    </div>

</div>

<!-- Custom FAQs from admin -->
<?php if (!empty($faqs)): ?>
<div class="faq-list" style="margin-top:8px;">
    <?php foreach ($faqs as $faq): ?>
    <div class="faq-item">
        <button class="faq-trigger" onclick="toggleFaq(this)">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="faq-icon" style="background:#f1f5f9;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div style="font-weight:600;"><?= Security::e($faq['question']) ?></div>
            </div>
            <svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="faq-body">
            <div style="font-size:14px;color:#374151;line-height:1.7;" class="faq-rich-content"><?= $faq['answer'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Still need help? -->
<div class="card" id="raise-ticket" style="margin-top:24px;text-align:center;">
    <div class="card-body">
        <h2 style="margin-bottom:8px;">Still need help?</h2>
        <p style="color:#64748b;margin-bottom:20px;">Our support team is happy to walk you through the setup over a call or via ticket.</p>
        <a href="/tickets/create" class="btn btn-primary">Raise a Support Ticket</a>
    </div>
</div>

<style>
.settings-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,.08);
    font-size: 13px;
    color: #cbd5e1;
}
.settings-row:last-child { border-bottom: none; }
.settings-row code {
    background: rgba(255,255,255,.1);
    color: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.settings-block {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    margin: 10px 0;
}
.settings-block .settings-row {
    color: #374151;
    border-bottom-color: #e2e8f0;
}
.settings-block .settings-row code {
    background: #e2e8f0;
    color: #0f172a;
}

.faq-list { display: flex; flex-direction: column; gap: 8px; max-width: 800px; }
.faq-rich-content ul, .faq-rich-content ol { padding-left: 20px; margin: 6px 0; }
.faq-rich-content li { margin-bottom: 4px; }
.faq-rich-content p { margin-bottom: 6px; }
.faq-rich-content strong { font-weight: 600; }
.faq-rich-content h2, .faq-rich-content h3 { font-weight: 600; margin: 10px 0 4px; }
.faq-rich-content blockquote { border-left: 3px solid #e2e8f0; padding-left: 12px; color: #64748b; margin: 8px 0; }
.faq-rich-content a { color: #2563eb; }
.faq-item { background: white; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.faq-trigger {
    width: 100%; display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; background: none; border: none; cursor: pointer;
    text-align: left; gap: 12px; transition: background .15s;
}
.faq-trigger:hover { background: #f8fafc; }
.faq-chevron { flex-shrink: 0; color: #94a3b8; transition: transform .25s; }
.faq-trigger.open .faq-chevron { transform: rotate(180deg); }
.faq-body { display: none; padding: 0 20px 20px; border-top: 1px solid #f1f5f9; }
.faq-body.open { display: block; }
.faq-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

.guide-steps { margin: 16px 0 0 16px; }
.guide-steps li { margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #374151; }

.mini-faq { padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
.mini-faq:last-child { border-bottom: none; padding-bottom: 0; }
.mini-faq-q { font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #0f172a; }
.mini-faq-a { font-size: 13px; color: #475569; line-height: 1.6; }
</style>

<script>
function toggleFaq(btn) {
    const body = btn.nextElementSibling;
    const isOpen = btn.classList.contains('open');
    btn.classList.toggle('open', !isOpen);
    body.classList.toggle('open', !isOpen);
}
</script>
