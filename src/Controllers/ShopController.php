<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\View;
use App\Models\Basket;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

class ShopController
{
    public function index(): void
    {
        Auth::requireAuth();
        $user   = Auth::user();
        $tierId = (int)($user['pricing_tier_id'] ?? 0);

        $filters = [
            'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : null,
            'search'      => trim($_GET['q'] ?? ''),
            'active_only' => true,
        ];

        $products   = Product::forCatalogue($filters, $tierId);
        $categories = Category::topLevel();
        $basketCount = Basket::itemCount((int)$user['id']);
        $activeCategory = $filters['category_id']
            ? Category::find($filters['category_id'])
            : null;

        View::render('customer/shop', [
            'title'          => 'Shop',
            'products'       => $products,
            'categories'     => $categories,
            'activeCategory' => $activeCategory,
            'search'         => $filters['search'],
            'tierId'         => $tierId,
            'basketCount'    => $basketCount,
        ]);
    }

    public function product(string $slug): void
    {
        Auth::requireAuth();
        $user    = Auth::user();
        $tierId  = (int)($user['pricing_tier_id'] ?? 0);
        $product = Product::findBySlug($slug);

        if (!$product) {
            http_response_code(404);
            View::renderRaw('errors/404', ['title' => 'Product Not Found']);
            return;
        }

        $basketCount = Basket::itemCount((int)$user['id']);
        $tierPrice   = $tierId > 0 ? ($product['prices'][$tierId]['price'] ?? null) : null;

        View::render('customer/product', [
            'title'       => $product['name'],
            'product'     => $product,
            'tierPrice'   => $tierPrice,
            'tierId'      => $tierId,
            'basketCount' => $basketCount,
        ]);
    }
}
