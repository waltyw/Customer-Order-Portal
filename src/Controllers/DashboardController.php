<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\View;
use App\Models\Invoice;
use App\Models\ServiceStatus;
use App\Models\Ticket;
use App\Models\User;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();
        $userId   = Auth::id();
        $services = ServiceStatus::all();

        $tickets  = array_slice(Ticket::forUser($userId), 0, 5);
        $invoices = array_slice(Invoice::forUser($userId), 0, 5);
        $stats    = User::stats($userId);

        View::render('customer/dashboard', [
            'title'         => 'Dashboard',
            'tickets'       => $tickets,
            'invoices'      => $invoices,
            'stats'         => $stats,
            'services'      => $services,
            'hasIssues'     => ServiceStatus::hasIssues($services),
            'overallStatus' => ServiceStatus::overallStatus($services),
        ]);
    }
}
