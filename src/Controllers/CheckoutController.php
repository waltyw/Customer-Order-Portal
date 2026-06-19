<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\Stripe;
use App\Core\View;
use App\Email\Mailer;
use App\Models\Address;
use App\Models\Basket;
use App\Models\DeliveryRule;
use App\Models\Setting;
use App\Models\ShopOrder;
use App\Models\User;

class CheckoutController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user   = Auth::user();
        $tierId = (int)($user['pricing_tier_id'] ?? 0);
        $basket = Basket::getOrCreate((int)$user['id']);
        $items  = Basket::items((int)$basket['id'], $tierId);

        if (empty($items)) {
            Security::flash('info', 'Your basket is empty.');
            Security::redirect('/basket');
        }

        $totals     = Basket::totals($items, $tierId);
        $delivery   = DeliveryRule::calculate($totals['subtotal'], $tierId);
        $vatRate    = (float)(Setting::get('shop_vat_rate') ?? 20);
        $vatEnabled = Setting::get('shop_vat_enabled') !== '0';
        $vatAmount  = $vatEnabled ? round(($totals['subtotal'] + $delivery) * ($vatRate / 100), 2) : 0.0;
        $total      = round($totals['subtotal'] + $delivery + $vatAmount, 2);

        $method = $user['checkout_method'] ?? 'stripe';

        // Build default addresses from the user's profile
        $profileBilling = array_filter([
            'line1'    => $user['billing_address_1'] ?? '',
            'line2'    => $user['billing_address_2'] ?? '',
            'city'     => $user['billing_city']      ?? '',
            'county'   => $user['billing_county']    ?? '',
            'postcode' => $user['billing_postcode']  ?? '',
            'country'  => $user['billing_country']   ?? 'United Kingdom',
            'company'  => $user['company']           ?? '',
        ]);

        $sameDelivery = (int)($user['delivery_same_as_billing'] ?? 1);
        $profileDelivery = $sameDelivery ? $profileBilling : array_filter([
            'line1'    => $user['delivery_address_1'] ?? '',
            'line2'    => $user['delivery_address_2'] ?? '',
            'city'     => $user['delivery_city']      ?? '',
            'county'   => $user['delivery_county']    ?? '',
            'postcode' => $user['delivery_postcode']  ?? '',
            'country'  => $user['delivery_country']   ?? 'United Kingdom',
            'company'  => $user['company']            ?? '',
        ]);

        View::render('customer/checkout', [
            'title'           => 'Checkout',
            'items'           => $items,
            'subtotal'        => $totals['subtotal'],
            'delivery'        => $delivery,
            'vatAmount'       => $vatAmount,
            'vatEnabled'      => $vatEnabled,
            'vatRate'         => $vatRate,
            'total'           => $total,
            'tierId'          => $tierId,
            'profileBilling'  => $profileBilling,
            'profileDelivery' => $profileDelivery,
            'method'          => $method,
            'currency'        => Setting::get('shop_default_currency') ?: 'GBP',
            'basketCount'     => array_sum(array_column($items, 'quantity')),
            'user'        => $user,
        ]);
    }

    public function process(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user   = Auth::user();
        $tierId = (int)($user['pricing_tier_id'] ?? 0);
        $basket = Basket::getOrCreate((int)$user['id']);
        $items  = Basket::items((int)$basket['id'], $tierId);

        if (empty($items)) {
            Security::flash('error', 'Your basket is empty.');
            Security::redirect('/basket');
        }

        $totals     = Basket::totals($items, $tierId);
        $delivery   = DeliveryRule::calculate($totals['subtotal'], $tierId);
        $vatRate    = (float)(Setting::get('shop_vat_rate') ?? 20);
        $vatEnabled = Setting::get('shop_vat_enabled') !== '0';
        $vatAmount  = $vatEnabled ? round(($totals['subtotal'] + $delivery) * ($vatRate / 100), 2) : 0.0;
        $total      = round($totals['subtotal'] + $delivery + $vatAmount, 2);
        $method     = $user['checkout_method'] ?? 'stripe';

        // Resolve addresses
        $billingId  = (int)($_POST['billing_address_id']  ?? 0);
        $shippingId = (int)($_POST['shipping_address_id'] ?? 0);
        $billing    = $billingId  ? Address::findForUser($billingId,  (int)$user['id']) : null;
        $shipping   = $shippingId ? Address::findForUser($shippingId, (int)$user['id']) : null;

        // Allow inline address entry if no saved address
        if (!$billing) {
            $billing = $this->buildAddressFromPost('billing', $user);
        }
        if (!$shipping) {
            $shipping = $_POST['ship_to_billing'] ? $billing : $this->buildAddressFromPost('shipping', $user);
        }

        $orderData = [
            'user_id'         => (int)$user['id'],
            'pricing_tier_id' => $tierId ?: null,
            'checkout_method' => $method,
            'subtotal'        => $totals['subtotal'],
            'delivery_charge' => $delivery,
            'vat_amount'      => $vatAmount,
            'total'           => $total,
            'notes'           => trim($_POST['notes'] ?? ''),
            'billing_name'    => $billing['line1'] ? ($user['name']) : null,
            'billing_company' => $billing['company'] ?? $user['company'] ?? null,
            'billing_line1'   => $billing['line1'] ?? null,
            'billing_line2'   => $billing['line2'] ?? null,
            'billing_city'    => $billing['city']  ?? null,
            'billing_county'  => $billing['county'] ?? null,
            'billing_postcode'=> $billing['postcode'] ?? null,
            'billing_country' => $billing['country']  ?? 'United Kingdom',
            'shipping_name'   => $user['name'],
            'shipping_company'=> $shipping['company'] ?? $user['company'] ?? null,
            'shipping_line1'  => $shipping['line1'] ?? null,
            'shipping_line2'  => $shipping['line2'] ?? null,
            'shipping_city'   => $shipping['city']  ?? null,
            'shipping_county' => $shipping['county'] ?? null,
            'shipping_postcode'=> $shipping['postcode'] ?? null,
            'shipping_country' => $shipping['country']  ?? 'United Kingdom',
        ];

        if ($method === 'po') {
            $this->processPo($orderData, $items, $basket);
        } else {
            $this->processStripe($orderData, $items, $basket);
        }
    }

    private function processPo(array $orderData, array $items, array $basket): void
    {
        $poNumber = trim($_POST['po_number'] ?? '');
        if (!$poNumber) {
            Security::flash('error', 'Please enter a PO number.');
            Security::redirect('/checkout');
        }

        $orderData['po_number'] = $poNumber;
        $orderData['po_notes']  = trim($_POST['po_notes'] ?? '');
        $orderData['status']    = 'confirmed';

        $orderId = ShopOrder::create($orderData);
        $this->saveOrderItems($orderId, $items);
        Basket::clear((int)$basket['id']);

        // Send confirmation email
        $order = ShopOrder::find($orderId);
        $user  = Auth::user();
        if ($order && $user) {
            Mailer::sendOrderConfirmation($order, User::find((int)$user['id']) ?? $user);
        }

        Security::redirect('/orders/' . $orderId . '/confirmation');
    }

    private function processStripe(array $orderData, array $items, array $basket): void
    {
        $user       = Auth::user();
        $currencyLc = strtolower(Setting::get('shop_default_currency') ?: 'gbp');

        try {
            $lineItems = [];
            foreach ($items as $item) {
                $price = (float)($item['tier_price'] ?? 0);
                if ($price <= 0) continue;
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => $currencyLc,
                        'unit_amount'  => (int)round($price * 100),
                        'product_data' => ['name' => $item['name'] . ' (' . $item['sku'] . ')'],
                    ],
                    'quantity' => (int)$item['quantity'],
                ];
            }

            if ($orderData['delivery_charge'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => $currencyLc,
                        'unit_amount'  => (int)round($orderData['delivery_charge'] * 100),
                        'product_data' => ['name' => 'Delivery'],
                    ],
                    'quantity' => 1,
                ];
            }

            if ($orderData['vat_amount'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => $currencyLc,
                        'unit_amount'  => (int)round($orderData['vat_amount'] * 100),
                        'product_data' => ['name' => 'VAT'],
                    ],
                    'quantity' => 1,
                ];
            }

            // Create pending order first to get reference for metadata
            $orderData['status'] = 'pending';
            $orderId = ShopOrder::create($orderData);
            $this->saveOrderItems($orderId, $items);

            $order   = ShopOrder::find($orderId);
            $session = Stripe::createCheckoutSession([
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'customer_email'       => $user['email'],
                'success_url'          => $_ENV['APP_URL'] . '/orders/' . $orderId . '/confirmation?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => $_ENV['APP_URL'] . '/checkout',
                'metadata'             => [
                    'order_id'   => $orderId,
                    'order_ref'  => $order['reference'],
                    'user_id'    => (int)$user['id'],
                ],
            ]);

            // Store Stripe session ID on order
            \App\Core\DB::execute(
                'UPDATE shop_orders SET stripe_session_id = ? WHERE id = ?',
                [$session['id'], $orderId]
            );

            Basket::clear((int)$basket['id']);
            Security::redirect($session['url']);

        } catch (\RuntimeException $e) {
            error_log('Shop Stripe error: ' . $e->getMessage());
            Security::flash('error', 'Payment could not be started. Please try again.');
            Security::redirect('/checkout');
        }
    }

    public function confirmation(int $orderId): void
    {
        Auth::requireAuth();
        $user  = Auth::user();
        $order = ShopOrder::findForUser($orderId, (int)$user['id']);

        if (!$order) {
            http_response_code(404);
            View::renderRaw('errors/404', ['title' => 'Order Not Found']);
            return;
        }

        // Confirm Stripe payment if session_id passed
        if (!empty($_GET['session_id']) && $order['checkout_method'] === 'stripe') {
            try {
                $session = Stripe::retrieveSession($_GET['session_id']);
                if (($session['payment_status'] ?? '') === 'paid' && $order['status'] !== 'confirmed') {
                    ShopOrder::updateStripePayment(
                        $orderId,
                        $session['payment_intent'] ?? '',
                        'confirmed'
                    );
                    $order['status'] = 'confirmed';
                    // Send confirmation email on first successful payment
                    $fullUser = User::find((int)$user['id']);
                    if ($fullUser) {
                        Mailer::sendOrderConfirmation(array_merge($order, ['id' => $orderId]), $fullUser);
                    }
                }
            } catch (\Exception $e) {
                error_log('Stripe session retrieve error: ' . $e->getMessage());
            }
        }

        View::render('customer/checkout-success', [
            'title'       => 'Order Confirmed',
            'order'       => $order,
            'basketCount' => 0,
        ]);
    }

    private function saveOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $price = (float)($item['tier_price'] ?? 0);
            ShopOrder::addItem($orderId, [
                'product_id' => (int)$item['product_id'],
                'sku'        => $item['sku'],
                'name'       => $item['name'],
                'quantity'   => (int)$item['quantity'],
                'unit_price' => $price,
                'total'      => round($price * (int)$item['quantity'], 2),
            ]);
        }
    }

    private function buildAddressFromPost(string $prefix, array $user): array
    {
        return [
            'company'  => $user['company'] ?? '',
            'line1'    => trim($_POST[$prefix . '_line1']  ?? ''),
            'line2'    => trim($_POST[$prefix . '_line2']  ?? ''),
            'city'     => trim($_POST[$prefix . '_city']   ?? ''),
            'county'   => trim($_POST[$prefix . '_county'] ?? ''),
            'postcode' => trim($_POST[$prefix . '_postcode'] ?? ''),
            'country'  => trim($_POST[$prefix . '_country']  ?? 'United Kingdom'),
        ];
    }
}
