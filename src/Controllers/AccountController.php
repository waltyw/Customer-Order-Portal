<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\User;

class AccountController
{
    public function index(): void
    {
        Auth::requireAuth();
        View::render('customer/account', [
            'title' => 'My Account',
            'user'  => User::find(Auth::id()),
        ]);
    }

    public function update(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            Security::flash('error', 'Name cannot be empty.');
            Security::redirect('/account');
        }

        User::update(Auth::id(), [
            'name'       => $name,
            'company'    => trim($_POST['company']    ?? ''),
            'phone'      => trim($_POST['phone']      ?? ''),
            'cc_email_1' => trim($_POST['cc_email_1'] ?? ''),
            'cc_email_2' => trim($_POST['cc_email_2'] ?? ''),
            'is_active'  => 1,
        ]);

        $_SESSION['user_name'] = $name;

        Security::flash('success', 'Your details have been updated.');
        Security::redirect('/account');
    }

    public function updateAddress(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $same = isset($_POST['delivery_same_as_billing']) ? 1 : 0;

        User::update(Auth::id(), [
            'billing_address_1'        => trim($_POST['billing_address_1']  ?? ''),
            'billing_address_2'        => trim($_POST['billing_address_2']  ?? ''),
            'billing_city'             => trim($_POST['billing_city']       ?? ''),
            'billing_county'           => trim($_POST['billing_county']     ?? ''),
            'billing_postcode'         => trim($_POST['billing_postcode']   ?? ''),
            'billing_country'          => trim($_POST['billing_country']    ?? 'United Kingdom'),
            'delivery_same_as_billing' => $same,
            'delivery_address_1'       => $same ? null : trim($_POST['delivery_address_1']  ?? ''),
            'delivery_address_2'       => $same ? null : trim($_POST['delivery_address_2']  ?? ''),
            'delivery_city'            => $same ? null : trim($_POST['delivery_city']       ?? ''),
            'delivery_county'          => $same ? null : trim($_POST['delivery_county']     ?? ''),
            'delivery_postcode'        => $same ? null : trim($_POST['delivery_postcode']   ?? ''),
            'delivery_country'         => $same ? null : trim($_POST['delivery_country']    ?? 'United Kingdom'),
            'is_active'                => 1,
        ]);

        Security::flash('success', 'Address updated.');
        Security::redirect('/account');
    }
}
