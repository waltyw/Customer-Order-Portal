<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\Category;
use App\Models\DeliveryRule;
use App\Models\PricingTier;
use App\Models\Setting;
use App\Models\ShopOrder;
use App\Models\User;

class ShopAdminController
{
    // ── Orders ────────────────────────────────────────────────────────────────

    public function orders(): void
    {
        Auth::requireAdmin();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => trim($_GET['q'] ?? ''),
        ];

        View::render('admin/shop-orders', [
            'title'   => 'Shop Orders',
            'orders'  => ShopOrder::all($filters),
            'counts'  => ShopOrder::counts(),
            'filters' => $filters,
        ], 'admin');
    }

    public function viewOrder(int $id): void
    {
        Auth::requireAdmin();
        $order = ShopOrder::find($id);
        if (!$order) { http_response_code(404); die('Not found'); }

        View::render('admin/shop-order-view', [
            'title' => 'Order ' . $order['reference'],
            'order' => $order,
        ], 'admin');
    }

    public function updateOrderStatus(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $status  = $_POST['status'] ?? '';
        $allowed = ['pending','confirmed','processing','dispatched','delivered','cancelled'];
        if (!in_array($status, $allowed)) {
            Security::flash('error', 'Invalid status.');
            Security::redirect('/admin/shop/orders/' . $id);
        }

        ShopOrder::updateStatus($id, $status);
        Security::flash('success', 'Order status updated to ' . ShopOrder::statusLabel($status) . '.');
        Security::redirect('/admin/shop/orders/' . $id);
    }

    public function exportOrders(): void
    {
        Auth::requireAdmin();
        $orders = ShopOrder::all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Reference', 'Date', 'Customer', 'Email', 'Method', 'Status', 'Subtotal', 'Delivery', 'VAT', 'Total', 'PO Number']);
        foreach ($orders as $o) {
            fputcsv($out, [
                $o['reference'],
                $o['created_at'],
                $o['customer_name'],
                $o['customer_email'],
                strtoupper($o['checkout_method']),
                ShopOrder::statusLabel($o['status']),
                $o['subtotal'],
                $o['delivery_charge'],
                $o['vat_amount'],
                $o['total'],
                $o['po_number'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categories(): void
    {
        Auth::requireAdmin();
        View::render('admin/shop-categories', [
            'title'      => 'Categories',
            'categories' => Category::all(),
        ], 'admin');
    }

    public function storeCategory(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        Category::create([
            'parent_id'   => $_POST['parent_id'] ?? null,
            'name'        => trim($_POST['name'] ?? ''),
            'slug'        => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ]);

        Security::flash('success', 'Category created.');
        Security::redirect('/admin/shop/categories');
    }

    public function updateCategory(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        Category::update($id, [
            'parent_id'   => $_POST['parent_id'] ?? null,
            'name'        => trim($_POST['name'] ?? ''),
            'slug'        => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ]);

        Security::flash('success', 'Category updated.');
        Security::redirect('/admin/shop/categories');
    }

    public function deleteCategory(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        Category::delete($id);
        Security::flash('success', 'Category deleted.');
        Security::redirect('/admin/shop/categories');
    }

    // ── Pricing Tiers ─────────────────────────────────────────────────────────

    public function tiers(): void
    {
        Auth::requireAdmin();
        View::render('admin/shop-tiers', [
            'title'     => 'Pricing Tiers',
            'tiers'     => PricingTier::all(),
            'customers' => User::customers(),
        ], 'admin');
    }

    public function storeTier(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        PricingTier::create([
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ]);

        Security::flash('success', 'Tier created.');
        Security::redirect('/admin/shop/tiers');
    }

    public function updateTier(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        PricingTier::update($id, [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ]);

        Security::flash('success', 'Tier updated.');
        Security::redirect('/admin/shop/tiers');
    }

    public function deleteTier(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        PricingTier::delete($id);
        Security::flash('success', 'Tier deleted.');
        Security::redirect('/admin/shop/tiers');
    }

    public function assignTier(int $userId): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $tierId  = $_POST['pricing_tier_id'] ?: null;
        $method  = $_POST['checkout_method'] ?? 'stripe';

        \App\Core\DB::execute(
            'UPDATE users SET pricing_tier_id = ?, checkout_method = ? WHERE id = ?',
            [$tierId, $method, $userId]
        );

        Security::flash('success', 'Customer tier & checkout method updated.');
        Security::redirect('/admin/customers/' . $userId);
    }

    // ── Delivery Rules ────────────────────────────────────────────────────────

    public function delivery(): void
    {
        Auth::requireAdmin();
        View::render('admin/shop-delivery', [
            'title' => 'Delivery Rules',
            'rules' => DeliveryRule::all(),
            'tiers' => PricingTier::all(),
        ], 'admin');
    }

    public function storeDelivery(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        DeliveryRule::create([
            'name'            => trim($_POST['name'] ?? ''),
            'type'            => $_POST['type'] ?? 'flat',
            'pricing_tier_id' => $_POST['pricing_tier_id'] ?? null,
            'flat_rate'       => $_POST['flat_rate']       ?? null,
            'free_threshold'  => $_POST['free_threshold']  ?? null,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
        ]);

        Security::flash('success', 'Delivery rule created.');
        Security::redirect('/admin/shop/delivery');
    }

    public function updateDelivery(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        DeliveryRule::update($id, [
            'name'            => trim($_POST['name'] ?? ''),
            'type'            => $_POST['type'] ?? 'flat',
            'pricing_tier_id' => $_POST['pricing_tier_id'] ?? null,
            'flat_rate'       => $_POST['flat_rate']       ?? null,
            'free_threshold'  => $_POST['free_threshold']  ?? null,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'      => (int)($_POST['sort_order'] ?? 0),
        ]);

        Security::flash('success', 'Delivery rule updated.');
        Security::redirect('/admin/shop/delivery');
    }

    public function deleteDelivery(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        DeliveryRule::delete($id);
        Security::flash('success', 'Delivery rule deleted.');
        Security::redirect('/admin/shop/delivery');
    }
}
