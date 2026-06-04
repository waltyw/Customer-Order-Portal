<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\Basket;
use App\Models\DeliveryRule;
use App\Models\Product;
use App\Models\Setting;

class BasketController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user    = Auth::user();
        $tierId  = (int)($user['pricing_tier_id'] ?? 0);
        $basket  = Basket::getOrCreate((int)$user['id']);
        $items   = Basket::items((int)$basket['id'], $tierId);
        $totals  = Basket::totals($items, $tierId);
        $delivery = DeliveryRule::calculate($totals['subtotal'], $tierId);

        $vatRate    = (float)(Setting::get('shop_vat_rate') ?? 20);
        $vatEnabled = Setting::get('shop_vat_enabled') !== '0';
        $vatAmount  = $vatEnabled ? round(($totals['subtotal'] + $delivery) * ($vatRate / 100), 2) : 0.0;
        $total      = round($totals['subtotal'] + $delivery + $vatAmount, 2);

        View::render('customer/basket', [
            'title'       => 'Basket',
            'basket'      => $basket,
            'items'       => $items,
            'subtotal'    => $totals['subtotal'],
            'delivery'    => $delivery,
            'vatAmount'   => $vatAmount,
            'vatEnabled'  => $vatEnabled,
            'vatRate'     => $vatRate,
            'total'       => $total,
            'tierId'      => $tierId,
            'basketCount' => array_sum(array_column($items, 'quantity')),
            'currency'    => Setting::get('shop_default_currency') ?: 'GBP',
        ]);
    }

    public function add(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user      = Auth::user();
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

        $product = Product::find($productId);
        if (!$product || !$product['is_active']) {
            Security::flash('error', 'Product not found.');
            Security::redirect($_SERVER['HTTP_REFERER'] ?? '/shop');
        }

        $basket = Basket::getOrCreate((int)$user['id']);
        Basket::addItem((int)$basket['id'], $productId, $quantity);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'    => true,
                'count' => Basket::itemCount((int)$user['id']),
            ]);
            exit;
        }

        Security::flash('success', '"' . $product['name'] . '" added to your basket.');
        Security::redirect($_POST['redirect'] ?? '/basket');
    }

    public function bulkAdd(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user   = Auth::user();
        $basket = Basket::getOrCreate((int)$user['id']);
        $added  = 0;

        // Handle quick-order form: array of sku/qty pairs
        $skus       = $_POST['sku']      ?? [];
        $quantities = $_POST['quantity'] ?? [];

        foreach ($skus as $i => $sku) {
            $sku = trim($sku);
            $qty = max(1, (int)($quantities[$i] ?? 1));
            if (!$sku) continue;
            $product = Product::findBySku($sku);
            if ($product && $product['is_active']) {
                Basket::addItem((int)$basket['id'], (int)$product['id'], $qty);
                $added++;
            }
        }

        // Handle CSV text: "SKU,QTY\nSKU,QTY"
        if (!$added && !empty($_POST['csv_input'])) {
            $lines = explode("\n", trim($_POST['csv_input']));
            foreach ($lines as $line) {
                $parts = preg_split('/[\s,;]+/', trim($line));
                $sku   = trim($parts[0] ?? '');
                $qty   = max(1, (int)($parts[1] ?? 1));
                if (!$sku) continue;
                $product = Product::findBySku($sku);
                if ($product && $product['is_active']) {
                    Basket::addItem((int)$basket['id'], (int)$product['id'], $qty);
                    $added++;
                }
            }
        }

        Security::flash($added ? 'success' : 'error',
            $added ? "{$added} item(s) added to your basket." : 'No valid SKUs found.');
        Security::redirect('/basket');
    }

    public function update(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user      = Auth::user();
        $basket    = Basket::getOrCreate((int)$user['id']);
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        Basket::setQuantity((int)$basket['id'], $productId, $quantity);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            $items    = Basket::items((int)$basket['id'], (int)($user['pricing_tier_id'] ?? 0));
            $totals   = Basket::totals($items, (int)($user['pricing_tier_id'] ?? 0));
            $delivery = DeliveryRule::calculate($totals['subtotal'], (int)($user['pricing_tier_id'] ?? 0));
            $vatRate  = (float)(Setting::get('shop_vat_rate') ?? 20);
            $vatEnabled = Setting::get('shop_vat_enabled') !== '0';
            $vatAmount  = $vatEnabled ? round(($totals['subtotal'] + $delivery) * ($vatRate / 100), 2) : 0.0;
            echo json_encode([
                'ok'       => true,
                'subtotal' => $totals['subtotal'],
                'delivery' => $delivery,
                'vat'      => $vatAmount,
                'total'    => round($totals['subtotal'] + $delivery + $vatAmount, 2),
                'count'    => array_sum(array_column($items, 'quantity')),
            ]);
            exit;
        }

        Security::redirect('/basket');
    }

    public function remove(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user      = Auth::user();
        $basket    = Basket::getOrCreate((int)$user['id']);
        $productId = (int)($_POST['product_id'] ?? 0);

        Basket::removeItem((int)$basket['id'], $productId);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'count' => Basket::itemCount((int)$user['id'])]);
            exit;
        }

        Security::redirect('/basket');
    }

    public function clear(): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user   = Auth::user();
        $basket = Basket::getOrCreate((int)$user['id']);
        Basket::clear((int)$basket['id']);

        Security::flash('info', 'Basket cleared.');
        Security::redirect('/basket');
    }

    private function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
