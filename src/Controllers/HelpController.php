<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\View;
use App\Models\Faq;
use App\Models\ServiceStatus;
use App\Models\User;

class HelpController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user       = User::find(Auth::id());
        $mailServer = User::mailServer($user['website_url'] ?? null);
        $services   = ServiceStatus::all();

        View::render('customer/help', [
            'title'         => 'Help & Email Setup',
            'user'          => $user,
            'mailServer'    => $mailServer ?: 'mail.yourdomain.com',
            'hasMailServer' => (bool)$mailServer,
            'services'      => $services,
            'overallStatus' => ServiceStatus::overallStatus($services),
            'hasIssues'     => ServiceStatus::hasIssues($services),
            'faqs'          => Faq::all(true),
        ]);
    }
}
