<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\Basket;
use App\Models\ShopOrder;

class OrderController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user   = Auth::user();
        $orders = ShopOrder::forUser((int)$user['id']);

        View::render('customer/orders', [
            'title'       => 'My Orders',
            'orders'      => $orders,
            'basketCount' => Basket::itemCount((int)$user['id']),
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $user  = Auth::user();
        $order = ShopOrder::findForUser($id, (int)$user['id']);

        if (!$order) {
            http_response_code(404);
            View::renderRaw('errors/404', ['title' => 'Order Not Found']);
            return;
        }

        View::render('customer/order-view', [
            'title'       => 'Order ' . $order['reference'],
            'order'       => $order,
            'basketCount' => Basket::itemCount((int)$user['id']),
        ]);
    }

    public function reorder(int $id): void
    {
        Auth::requireAuth();
        Security::checkCsrf();

        $user   = Auth::user();
        $order  = ShopOrder::findForUser($id, (int)$user['id']);
        if (!$order) {
            Security::flash('error', 'Order not found.');
            Security::redirect('/orders');
        }

        $basket = Basket::getOrCreate((int)$user['id']);
        foreach ($order['items'] as $item) {
            if ($item['product_id']) {
                Basket::addItem((int)$basket['id'], (int)$item['product_id'], (int)$item['quantity']);
            }
        }

        Security::flash('success', 'Items from order ' . $order['reference'] . ' added to your basket.');
        Security::redirect('/basket');
    }
}
