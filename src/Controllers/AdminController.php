<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Email\Mailer;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\ServiceStatus;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Website;

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireAdmin();
        $ticketCounts  = Ticket::counts();
        $invoiceCounts = Invoice::counts();
        $recentTickets = array_slice(Ticket::all(), 0, 10);

        View::render('admin/dashboard', [
            'title'          => 'Admin Dashboard',
            'ticketCounts'   => $ticketCounts,
            'invoiceCounts'  => $invoiceCounts,
            'recentTickets'  => $recentTickets,
            'opcacheEnabled' => function_exists('opcache_reset'),
        ], 'admin');
    }

    public function systemStatus(): void
    {
        Auth::requireAdmin();

        $status = [];

        // Database
        try {
            \App\Core\DB::fetchOne('SELECT 1');
            $status['database'] = ['ok' => true, 'msg' => 'Connected'];
        } catch (\Exception $e) {
            $status['database'] = ['ok' => false, 'msg' => $e->getMessage()];
        }

        // Storage writable
        $storageBase = dirname(__DIR__, 2) . '/storage';
        $status['storage_attachments'] = [
            'ok'  => is_writable($storageBase . '/attachments'),
            'msg' => is_writable($storageBase . '/attachments') ? 'Writable' : 'Not writable — chmod 755',
        ];
        $status['storage_logs'] = [
            'ok'  => is_writable($storageBase . '/logs'),
            'msg' => is_writable($storageBase . '/logs') ? 'Writable' : 'Not writable — chmod 755',
        ];

        // PHP version
        $status['php'] = [
            'ok'  => version_compare(PHP_VERSION, '8.1.0', '>='),
            'msg' => PHP_VERSION,
        ];

        // OPcache
        $status['opcache'] = [
            'ok'  => false,
            'msg' => function_exists('opcache_reset') ? 'Enabled' : 'Not enabled (files reload instantly — no action needed)',
        ];

        // SMTP configured
        $status['smtp'] = [
            'ok'  => !empty($_ENV['SMTP_HOST']) && !empty($_ENV['SMTP_PASS']),
            'msg' => !empty($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] . ':' . ($_ENV['SMTP_PORT'] ?? '465') : 'Not configured in .env',
        ];

        // Stripe configured
        $status['stripe'] = [
            'ok'  => !empty($_ENV['STRIPE_SECRET_KEY']),
            'msg' => !empty($_ENV['STRIPE_SECRET_KEY']) ? 'Key set' : 'Not configured — payments disabled',
        ];

        View::render('admin/system-status', [
            'title'  => 'System Status',
            'status' => $status,
        ], 'admin');
    }

    // ── Customers ────────────────────────────────────────────────────────────

    public function customers(): void
    {
        Auth::requireAdmin();
        View::render('admin/customers', [
            'title'     => 'Customers',
            'customers' => User::customers(),
        ], 'admin');
    }

    public function createCustomer(): void
    {
        Auth::requireAdmin();
        View::render('admin/customer-create', ['title' => 'Add Customer'], 'admin');
    }

    public function storeCustomer(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $email   = strtolower(trim($_POST['email'] ?? ''));
        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');

        if (!$email || !$name) {
            Security::flash('error', 'Name and email are required.');
            Security::redirect('/admin/customers/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Security::flash('error', 'Invalid email address.');
            Security::redirect('/admin/customers/create');
        }

        if (User::findByEmail($email)) {
            Security::flash('error', 'A customer with this email already exists.');
            Security::redirect('/admin/customers/create');
        }

        $tempPassword = bin2hex(random_bytes(8));
        $userId = User::create([
            'email'       => $email,
            'password'    => $tempPassword,
            'name'        => $name,
            'company'     => $company,
            'phone'       => $phone,
            'website_url' => trim($_POST['website_url'] ?? ''),
        ]);

        Mailer::sendWelcome(['email' => $email, 'name' => $name], $tempPassword);

        Security::flash('success', "Customer {$name} created. Welcome email sent.");
        Security::redirect('/admin/customers/' . $userId);
    }

    public function viewCustomer(int $id): void
    {
        Auth::requireAdmin();
        $customer = User::find($id);
        if (!$customer || $customer['role'] !== 'customer') {
            Security::redirect('/admin/customers');
        }

        $stats    = User::stats($id);
        $tickets  = Ticket::forUser($id);
        $invoices = Invoice::forUser($id);
        try {
            $websites = Website::forUser($id);
        } catch (\Throwable) {
            $websites = [];
        }

        View::render('admin/customer-view', [
            'title'    => $customer['name'],
            'customer' => $customer,
            'stats'    => $stats,
            'tickets'  => $tickets,
            'invoices' => $invoices,
            'websites' => $websites,
        ], 'admin');
    }

    public function importForm(): void
    {
        Auth::requireAdmin();
        View::render('admin/customer-import', ['title' => 'Import Customers'], 'admin');
    }

    public function downloadTemplate(): void
    {
        Auth::requireAdmin();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="customer-import-template.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // UTF-8 BOM so Excel opens it correctly
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'email', 'company', 'phone', 'website_url']);
        fputcsv($out, ['Jane Smith', 'jane@example.com', 'Smith Ltd', '07700 000001', 'https://smithltd.co.uk']);
        fputcsv($out, ['Bob Jones', 'bob@example.com', 'Jones & Co', '07700 000002', '']);
        fclose($out);
        exit;
    }

    public function importCsv(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            Security::flash('error', 'Please choose a CSV file to upload.');
            Security::redirect('/admin/customers/import');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['csv']['tmp_name']);
        if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'])) {
            Security::flash('error', 'File must be a CSV.');
            Security::redirect('/admin/customers/import');
        }

        $sendEmails = isset($_POST['send_emails']);
        $handle     = fopen($_FILES['csv']['tmp_name'], 'r');

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $headers  = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $required = ['name', 'email'];

        foreach ($required as $col) {
            if (!in_array($col, $headers)) {
                fclose($handle);
                Security::flash('error', "CSV is missing required column: '{$col}'. Please use the template.");
                Security::redirect('/admin/customers/import');
            }
        }

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (count($data) < 2 || implode('', $data) === '') continue;

            $record = [];
            foreach ($headers as $i => $header) {
                $record[$header] = trim($data[$i] ?? '');
            }

            $name  = $record['name']  ?? '';
            $email = strtolower($record['email'] ?? '');

            if (!$name) {
                $errors[] = "Row {$row}: name is empty — skipped.";
                continue;
            }

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$row}: '{$email}' is not a valid email — skipped.";
                continue;
            }

            if (User::findByEmail($email)) {
                $errors[] = "Row {$row}: {$email} already exists — skipped.";
                $skipped++;
                continue;
            }

            $tempPassword = bin2hex(random_bytes(8));
            $userId = User::create([
                'email'       => $email,
                'password'    => $tempPassword,
                'name'        => $name,
                'company'     => $record['company']     ?? '',
                'phone'       => $record['phone']       ?? '',
                'website_url' => $record['website_url'] ?? '',
            ]);

            if ($sendEmails) {
                Mailer::sendWelcome(['email' => $email, 'name' => $name], $tempPassword);
            }

            $imported++;
        }

        fclose($handle);

        $_SESSION['import_result'] = compact('imported', 'skipped', 'errors');
        Security::flash('success', "{$imported} customer(s) imported" . ($skipped ? ", {$skipped} skipped (already exist)" : '') . '.');
        Security::redirect('/admin/customers/import');
    }

    public function updateCustomer(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            Security::flash('error', 'Name cannot be empty.');
            Security::redirect('/admin/customers/' . $id);
        }

        User::update($id, [
            'name'        => $name,
            'company'     => trim($_POST['company'] ?? ''),
            'phone'       => trim($_POST['phone'] ?? ''),
            'website_url' => trim($_POST['website_url'] ?? ''),
            'is_active'   => 1,
        ]);

        Security::flash('success', 'Customer details updated.');
        Security::redirect('/admin/customers/' . $id);
    }

    public function addWebsite(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $url   = trim($_POST['url'] ?? '');
        $label = trim($_POST['label'] ?? '');

        if ($url) {
            Website::add($id, $url, $label);
            Security::flash('success', 'Website added.');
        }
        Security::redirect('/admin/customers/' . $id);
    }

    public function removeWebsite(int $customerId, int $websiteId): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        Website::remove($websiteId, $customerId);
        Security::flash('success', 'Website removed.');
        Security::redirect('/admin/customers/' . $customerId);
    }

    public function toggleCustomer(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        User::toggleActive($id);
        Security::flash('success', 'Customer status updated.');
        Security::redirect('/admin/customers/' . $id);
    }

    public function toggleInvoices(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        try {
            // Add column if it doesn't exist yet
            $col = \App\Core\DB::fetchOne(
                "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'show_invoices'"
            );

            if ((int)($col['cnt'] ?? 0) === 0) {
                \App\Core\DB::execute(
                    'ALTER TABLE users ADD COLUMN show_invoices TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active'
                );
            }

            \App\Core\DB::execute(
                'UPDATE users SET show_invoices = NOT show_invoices WHERE id = ?', [$id]
            );

            $customer = User::find($id);
            $msg = ($customer['show_invoices'] ?? 1)
                ? 'Invoices are now visible to this customer.'
                : 'Invoices are now hidden from this customer.';
            Security::flash('success', $msg);

        } catch (\Throwable $e) {
            error_log('toggleInvoices error: ' . $e->getMessage());
            Security::flash('error', 'Could not update invoice visibility. Error: ' . $e->getMessage());
        }

        Security::redirect('/admin/customers/' . $id);
    }

    // ── Tickets ───────────────────────────────────────────────────────────────

    public function tickets(): void
    {
        Auth::requireAdmin();
        $filters = [];
        if (!empty($_GET['status']))   $filters['status']   = $_GET['status'];
        if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];

        View::render('admin/tickets', [
            'title'   => 'All Tickets',
            'tickets' => Ticket::all($filters),
            'filters' => $filters,
        ], 'admin');
    }

    public function viewTicket(int $id): void
    {
        Auth::requireAdmin();
        $ticket = Ticket::find($id);
        if (!$ticket) Security::redirect('/admin/tickets');

        $messages = Ticket::messages($id, true);

        View::render('admin/ticket-view', [
            'title'    => "Ticket #{$ticket['reference']}",
            'ticket'   => $ticket,
            'messages' => $messages,
        ], 'admin');
    }

    public function replyTicket(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $ticket  = Ticket::find($id);
        if (!$ticket) Security::redirect('/admin/tickets');

        $message    = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['internal']);

        if (!$message) {
            Security::flash('error', 'Reply cannot be empty.');
            Security::redirect('/admin/tickets/' . $id);
        }

        Ticket::addMessage($id, Auth::id(), $message, $isInternal);

        if (!$isInternal) {
            Ticket::updateStatus($id, 'waiting_customer');
            $customer = User::find($ticket['user_id']);
            if ($customer) {
                Mailer::sendTicketReply(
                    $ticket,
                    ['message' => $message, 'sender_name' => Auth::user()['name']],
                    $customer,
                    true
                );
            }
        }

        Security::flash('success', $isInternal ? 'Internal note added.' : 'Reply sent to customer.');
        Security::redirect('/admin/tickets/' . $id);
    }

    public function updateTicketStatus(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'])) {
            Security::redirect('/admin/tickets/' . $id);
        }

        Ticket::updateStatus($id, $status);
        Security::flash('success', 'Status updated.');
        Security::redirect('/admin/tickets/' . $id);
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    public function invoices(): void
    {
        Auth::requireAdmin();
        Invoice::updateOverdue();
        View::render('admin/invoices', [
            'title'    => 'Invoices',
            'invoices' => Invoice::all(),
        ], 'admin');
    }

    public function createInvoice(): void
    {
        Auth::requireAdmin();
        View::render('admin/invoice-create', [
            'title'     => 'Create Invoice',
            'customers' => User::customers(),
            'nextNum'   => Invoice::nextNumber(),
        ], 'admin');
    }

    public function storeInvoice(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $userId    = (int)($_POST['user_id'] ?? 0);
        $lineItems = [];

        $descriptions = $_POST['item_desc'] ?? [];
        $qtys         = $_POST['item_qty'] ?? [];
        $prices       = $_POST['item_price'] ?? [];

        foreach ($descriptions as $i => $desc) {
            if (trim($desc) && isset($qtys[$i], $prices[$i])) {
                $lineItems[] = [
                    'description' => trim($desc),
                    'qty'         => (float)$qtys[$i],
                    'unit_price'  => (float)$prices[$i],
                ];
            }
        }

        if (!$userId || !$lineItems) {
            Security::flash('error', 'Customer and at least one line item are required.');
            Security::redirect('/admin/invoices/create');
        }

        $invoiceId = Invoice::create([
            'user_id'        => $userId,
            'invoice_number' => $_POST['invoice_number'] ?? Invoice::nextNumber(),
            'vat_rate'       => (float)($_POST['vat_rate'] ?? 20),
            'due_date'       => $_POST['due_date'] ?? null,
            'notes'          => trim($_POST['notes'] ?? ''),
            'line_items'     => $lineItems,
        ]);

        if (isset($_POST['send_email'])) {
            $invoice  = Invoice::find($invoiceId);
            $customer = User::find($userId);
            if ($invoice && $customer) Mailer::sendInvoiceReady($invoice, $customer);
        }

        Security::flash('success', 'Invoice created.');
        Security::redirect('/admin/invoices');
    }

    // ── Service Status ────────────────────────────────────────────────────────

    public function serviceStatus(): void
    {
        Auth::requireAdmin();
        View::render('admin/service-status', [
            'title'    => 'Service Status',
            'services' => ServiceStatus::all(),
        ], 'admin');
    }

    public function updateServiceStatus(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $status  = $_POST['status'] ?? 'operational';
        $message = trim($_POST['message'] ?? '');

        if (!in_array($status, ['operational', 'degraded', 'outage', 'maintenance'])) {
            $status = 'operational';
        }

        ServiceStatus::update($id, $status, $message, Auth::id());
        Security::flash('success', 'Service status updated.');
        Security::redirect('/admin/service-status');
    }

    public function addService(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $name    = trim($_POST['service_name'] ?? '');
        $status  = $_POST['status'] ?? 'operational';
        $message = trim($_POST['message'] ?? '');

        if (!$name) {
            Security::flash('error', 'Service name is required.');
            Security::redirect('/admin/service-status');
        }

        ServiceStatus::create($name, $status, $message, Auth::id());
        Security::flash('success', "Service '{$name}' added.");
        Security::redirect('/admin/service-status');
    }

    public function deleteService(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        ServiceStatus::delete($id);
        Security::flash('success', 'Service removed.');
        Security::redirect('/admin/service-status');
    }

    // ── FAQs ──────────────────────────────────────────────────────────────────

    public function faqs(): void
    {
        Auth::requireAdmin();
        View::render('admin/faqs', [
            'title' => 'Manage FAQs',
            'faqs'  => Faq::all(),
        ], 'admin');
    }

    public function storeFaq(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer'] ?? '');

        if (!$question || !$answer) {
            Security::flash('error', 'Question and answer are both required.');
            Security::redirect('/admin/faqs');
        }

        Faq::create($question, $answer, Faq::nextOrder());
        Security::flash('success', 'FAQ added.');
        Security::redirect('/admin/faqs');
    }

    public function editFaq(int $id): void
    {
        Auth::requireAdmin();
        $faq = Faq::find($id);
        if (!$faq) Security::redirect('/admin/faqs');

        View::render('admin/faq-edit', [
            'title' => 'Edit FAQ',
            'faq'   => $faq,
        ], 'admin');
    }

    public function updateFaq(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        Faq::update(
            $id,
            trim($_POST['question'] ?? ''),
            trim($_POST['answer'] ?? ''),
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['is_active'])
        );

        Security::flash('success', 'FAQ updated.');
        Security::redirect('/admin/faqs');
    }

    public function deleteFaq(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        Faq::delete($id);
        Security::flash('success', 'FAQ deleted.');
        Security::redirect('/admin/faqs');
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function settings(): void
    {
        Auth::requireAdmin();
        View::render('admin/settings', [
            'title'    => 'Portal Settings',
            'settings' => Setting::all(),
        ], 'admin');
    }

    public function deleteLogo(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $imgDir = dirname(__DIR__, 2) . '/public/assets/img/';
        $ext    = Setting::get('logo_ext') ?: 'png';
        $file   = $imgDir . 'logo.' . $ext;

        if (file_exists($file)) {
            unlink($file);
        }
        Setting::set('logo_ext', '');

        Security::flash('success', 'Logo removed.');
        Security::redirect('/admin/settings');
    }

    public function deleteFavicon(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $imgDir = dirname(__DIR__, 2) . '/public/assets/img/';
        $ext    = Setting::get('favicon_ext') ?: 'png';
        $file   = $imgDir . 'favicon.' . $ext;

        if (file_exists($file)) {
            unlink($file);
        }
        Setting::set('favicon_ext', '');

        Security::flash('success', 'Favicon removed.');
        Security::redirect('/admin/settings');
    }

    public function saveSettings(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        Setting::saveMany([
            'app_name'        => trim($_POST['app_name'] ?? ''),
            'primary_color'   => $_POST['primary_color']   ?? '#2563eb',
            'primary_dark'    => $_POST['primary_dark']    ?? '#1d4ed8',
            'sidebar_bg'      => $_POST['sidebar_bg']      ?? '#0f172a',
            'sidebar_text'    => $_POST['sidebar_text']    ?? '#94a3b8',
            'sidebar_active'  => $_POST['sidebar_active']  ?? '#2563eb',
            'body_bg'         => $_POST['body_bg']         ?? '#f8fafc',
            'text_color'      => $_POST['text_color']      ?? '#1e293b',
            'text_muted'      => $_POST['text_muted']      ?? '#64748b',
            'card_bg'         => $_POST['card_bg']         ?? '#ffffff',
            'support_email'   => trim($_POST['support_email'] ?? ''),
            'currency_symbol'  => trim($_POST['currency_symbol'] ?? '£') ?: '£',
            'font_family'      => trim($_POST['font_family'] ?? 'Inter') ?: 'Inter',
            'invoices_enabled' => isset($_POST['invoices_enabled']) ? '1' : '0',
            'logo_link_url'    => trim($_POST['logo_link_url'] ?? ''),
        ]);

        // Save custom menu links as JSON
        $labels   = $_POST['link_label'] ?? [];
        $urls     = $_POST['link_url']   ?? [];
        $newTabs  = $_POST['link_newtab'] ?? [];
        $links    = [];
        foreach ($labels as $i => $label) {
            $label = trim($label);
            $url   = trim($urls[$i] ?? '');
            if ($label && $url) {
                $links[] = [
                    'label'   => $label,
                    'url'     => $url,
                    'new_tab' => isset($newTabs[$i]),
                ];
            }
        }
        Setting::set('menu_links', json_encode($links));

        // Project root = portal_private/ = 2 levels up from src/Controllers/
        $imgDir = dirname(__DIR__, 2) . '/public/assets/img/';

        // Handle favicon upload
        if (!empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'ico', 'svg'];
            if (in_array($ext, $allowed) && $_FILES['favicon']['size'] <= 524288) {
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], $imgDir . 'favicon.' . $ext)) {
                    Setting::set('favicon_ext', $ext);
                } else {
                    Security::flash('error', 'Favicon upload failed — check folder permissions on public/assets/img/');
                    Security::redirect('/admin/settings');
                }
            }
        }

        // Handle logo upload
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
            $finfo   = new \finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($_FILES['logo']['tmp_name']);

            if (in_array($mime, $allowed) && $_FILES['logo']['size'] <= 2097152) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $imgDir . 'logo.' . $ext)) {
                    Setting::set('logo_ext', $ext);
                } else {
                    Security::flash('error', 'Logo upload failed — check folder permissions on public/assets/img/');
                    Security::redirect('/admin/settings');
                }
            } else {
                Security::flash('error', 'Logo must be PNG, JPG, SVG or WebP under 2MB.');
                Security::redirect('/admin/settings');
            }
        }

        Security::flash('success', 'Settings saved.');
        Security::redirect('/admin/settings');
    }
}
