<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\View;
use App\Models\Invoice;

class InvoiceController
{
    public function index(): void
    {
        Auth::requireAuth();
        Invoice::updateOverdue();
        $invoices = Invoice::forUser(Auth::id());
        View::render('customer/invoices', ['title' => 'Invoices', 'invoices' => $invoices]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $invoice = Invoice::findForUser($id, Auth::id());
        if (!$invoice) { http_response_code(404); die('Invoice not found.'); }

        $lineItems = json_decode($invoice['line_items'] ?? '[]', true) ?: [];
        View::render('customer/invoice-view', [
            'title'     => 'Invoice ' . $invoice['invoice_number'],
            'invoice'   => $invoice,
            'lineItems' => $lineItems,
        ]);
    }
}
