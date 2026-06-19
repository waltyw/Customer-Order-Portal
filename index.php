<?php

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';

use App\Core\Security;
use App\Core\View;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\TicketController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\WebhookController;
use App\Controllers\AdminController;
use App\Controllers\AccountController;
use App\Controllers\HelpController;
use App\Controllers\XeroController;
use App\Controllers\ShopController;
use App\Controllers\BasketController;
use App\Controllers\CheckoutController;
use App\Controllers\OrderController;
use App\Controllers\ProductAdminController;
use App\Controllers\ShopAdminController;

// Stripe webhook must read raw body before session/output start
$isWebhook = ($_SERVER['REQUEST_METHOD'] === 'POST'
    && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/webhook/stripe');

if (!$isWebhook) {
    session_start();
    Security::setHeaders();
}

View::init(__DIR__ . '/templates');

// ── Routing ──────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$path   = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if ($path !== '/' && str_ends_with($path, '/')) {
    Security::redirect(rtrim($path, '/'));
}

$routes = [
    'GET /'                        => [AuthController::class,      'loginForm'],
    'GET /login'                   => [AuthController::class,      'loginForm'],
    'POST /login'                  => [AuthController::class,      'login'],
    'GET /logout'                  => [AuthController::class,      'logout'],
    'GET /forgot-password'         => [AuthController::class,      'forgotForm'],
    'POST /forgot-password'        => [AuthController::class,      'forgotSubmit'],
    'GET /reset-password'          => [AuthController::class,      'resetForm'],
    'POST /reset-password'         => [AuthController::class,      'resetSubmit'],
    'GET /dashboard'               => [DashboardController::class, 'index'],
    'GET /tickets'                 => [TicketController::class,    'index'],
    'GET /tickets/create'          => [TicketController::class,    'create'],
    'POST /tickets/create'         => [TicketController::class,    'store'],
    'GET /invoices'                => [InvoiceController::class,   'index'],
    'GET /account'                 => [AccountController::class,   'index'],
    'POST /account'                => [AccountController::class,   'update'],
    'POST /account/address'        => [AccountController::class,   'updateAddress'],
    'GET /help'                    => [HelpController::class,      'index'],
    'GET /payment/success'         => [PaymentController::class,   'success'],
    'GET /payment/cancelled'       => [PaymentController::class,   'cancelled'],
    'POST /webhook/stripe'         => [WebhookController::class,   'stripe'],
    'GET /admin'                   => [AdminController::class,     'dashboard'],
    'GET /admin/system-status'     => [AdminController::class,     'systemStatus'],
    'GET /admin/customers'            => [AdminController::class,  'customers'],
    'GET /admin/customers/import'     => [AdminController::class,  'importForm'],
    'POST /admin/customers/import'    => [AdminController::class,  'importCsv'],
    'GET /admin/customers/template'   => [AdminController::class,  'downloadTemplate'],
    'GET /admin/customers/create'     => [AdminController::class,  'createCustomer'],
    'POST /admin/customers/create'    => [AdminController::class,  'storeCustomer'],
    'GET /admin/tickets'           => [AdminController::class,     'tickets'],
    'GET /admin/invoices'          => [AdminController::class,     'invoices'],
    'GET /admin/invoices/create'   => [AdminController::class,     'createInvoice'],
    'POST /admin/invoices/create'  => [AdminController::class,     'storeInvoice'],
    'GET /admin/settings'              => [AdminController::class,  'settings'],
    'POST /admin/settings'             => [AdminController::class,  'saveSettings'],
    'POST /admin/settings/delete-logo'    => [AdminController::class,  'deleteLogo'],
    'POST /admin/settings/delete-favicon' => [AdminController::class,  'deleteFavicon'],

    // ── Shop — Customer ────────────────────────────────────────────────────────
    'GET /shop'                   => [ShopController::class,     'index'],
    'GET /basket'                 => [BasketController::class,   'index'],
    'POST /basket/add'            => [BasketController::class,   'add'],
    'POST /basket/bulk-add'       => [BasketController::class,   'bulkAdd'],
    'POST /basket/update'         => [BasketController::class,   'update'],
    'POST /basket/remove'         => [BasketController::class,   'remove'],
    'POST /basket/clear'          => [BasketController::class,   'clear'],
    'GET /checkout'               => [CheckoutController::class, 'index'],
    'POST /checkout/process'      => [CheckoutController::class, 'process'],
    'GET /orders'                 => [OrderController::class,    'index'],

    // ── Shop — Admin ──────────────────────────────────────────────────────────
    'GET /admin/shop/products'          => [ProductAdminController::class, 'index'],
    'GET /admin/shop/products/create'   => [ProductAdminController::class, 'create'],
    'POST /admin/shop/products/create'  => [ProductAdminController::class, 'store'],
    'GET /admin/shop/products/export'   => [ProductAdminController::class, 'exportCsv'],
    'POST /admin/shop/products/import'  => [ProductAdminController::class, 'importCsv'],
    'GET /admin/shop/stock'             => [ProductAdminController::class, 'stock'],
    'GET /admin/shop/orders'            => [ShopAdminController::class,    'orders'],
    'GET /admin/shop/orders/export'     => [ShopAdminController::class,    'exportOrders'],
    'GET /admin/shop/categories'        => [ShopAdminController::class,    'categories'],
    'POST /admin/shop/categories/create'=> [ShopAdminController::class,    'storeCategory'],
    'GET /admin/shop/tiers'             => [ShopAdminController::class,    'tiers'],
    'POST /admin/shop/tiers/create'     => [ShopAdminController::class,    'storeTier'],
    'GET /admin/shop/delivery'          => [ShopAdminController::class,    'delivery'],
    'POST /admin/shop/delivery/create'  => [ShopAdminController::class,    'storeDelivery'],
];

$key = $method . ' ' . $path;
if (isset($routes[$key])) {
    [$class, $action] = $routes[$key];
    (new $class())->$action();
    exit;
}

$dynamicRoutes = [
    '#^GET /tickets/(\d+)$#'                 => [TicketController::class,  'show'],
    '#^POST /tickets/(\d+)/reply$#'          => [TicketController::class,  'reply'],
    '#^GET /invoices/(\d+)$#'               => [InvoiceController::class, 'show'],
    '#^GET /invoices/(\d+)/pay$#'           => [PaymentController::class, 'showPay'],
    '#^POST /invoices/(\d+)/pay$#'          => [PaymentController::class, 'processStripe'],
    '#^GET /admin/customers/(\d+)$#'        => [AdminController::class,   'viewCustomer'],
    '#^POST /admin/customers/(\d+)/update$#'        => [AdminController::class, 'updateCustomer'],
    '#^POST /admin/customers/(\d+)/add-website$#'   => [AdminController::class, 'addWebsite'],
    '#^POST /admin/customers/(\d+)/remove-website/(\d+)$#' => [AdminController::class, 'removeWebsite'],
    '#^POST /admin/customers/(\d+)/toggle$#'        => [AdminController::class, 'toggleCustomer'],
    '#^POST /admin/customers/(\d+)/toggle-invoices$#' => [AdminController::class, 'toggleInvoices'],
    // website routes removed — WVM does not use website management
    '#^GET /admin/tickets/(\d+)$#'          => [AdminController::class,   'viewTicket'],
    '#^POST /admin/tickets/(\d+)/reply$#'   => [AdminController::class,   'replyTicket'],
    '#^POST /admin/tickets/(\d+)/status$#'  => [AdminController::class,   'updateTicketStatus'],

    // ── Shop dynamic routes — Customer ────────────────────────────────────────
    '#^GET /shop/([a-z0-9\-]+)$#'                  => [ShopController::class,     'product'],
    '#^GET /orders/(\d+)$#'                        => [OrderController::class,    'show'],
    '#^POST /orders/(\d+)/reorder$#'               => [OrderController::class,    'reorder'],
    '#^GET /orders/(\d+)/confirmation$#'            => [CheckoutController::class, 'confirmation'],

    // ── Shop dynamic routes — Admin ───────────────────────────────────────────
    '#^GET /admin/shop/products/(\d+)/edit$#'                          => [ProductAdminController::class, 'edit'],
    '#^POST /admin/shop/products/(\d+)/update$#'                       => [ProductAdminController::class, 'update'],
    '#^POST /admin/shop/products/(\d+)/delete$#'                       => [ProductAdminController::class, 'delete'],
    '#^POST /admin/shop/products/(\d+)/image/(\d+)/delete$#'           => [ProductAdminController::class, 'deleteImage'],
    '#^POST /admin/shop/stock/(\d+)/update$#'                          => [ProductAdminController::class, 'updateStock'],
    '#^GET /admin/shop/products/(\d+)/variations$#'                    => [ProductAdminController::class, 'variations'],
    '#^POST /admin/shop/products/(\d+)/variations/create$#'            => [ProductAdminController::class, 'storeVariation'],
    '#^POST /admin/shop/products/(\d+)/variations/(\d+)/delete$#'      => [ProductAdminController::class, 'deleteVariation'],
    '#^GET /admin/shop/orders/(\d+)$#'                                 => [ShopAdminController::class,    'viewOrder'],
    '#^POST /admin/shop/orders/(\d+)/status$#'                         => [ShopAdminController::class,    'updateOrderStatus'],
    '#^POST /admin/shop/categories/(\d+)/update$#'                     => [ShopAdminController::class,    'updateCategory'],
    '#^POST /admin/shop/categories/(\d+)/delete$#'                     => [ShopAdminController::class,    'deleteCategory'],
    '#^POST /admin/shop/tiers/(\d+)/update$#'                          => [ShopAdminController::class,    'updateTier'],
    '#^POST /admin/shop/tiers/(\d+)/delete$#'                          => [ShopAdminController::class,    'deleteTier'],
    '#^POST /admin/shop/customers/(\d+)/tier$#'                        => [ShopAdminController::class,    'assignTier'],
    '#^POST /admin/shop/delivery/(\d+)/update$#'                       => [ShopAdminController::class,    'updateDelivery'],
    '#^POST /admin/shop/delivery/(\d+)/delete$#'                       => [ShopAdminController::class,    'deleteDelivery'],
];

$requestLine = $method . ' ' . $path;
foreach ($dynamicRoutes as $pattern => [$class, $action]) {
    if (preg_match($pattern, $requestLine, $matches)) {
        $args = array_map('intval', array_slice($matches, 1));
        (new $class())->$action(...$args);
        exit;
    }
}

http_response_code(404);
View::renderRaw('errors/404', ['title' => 'Page Not Found']);
