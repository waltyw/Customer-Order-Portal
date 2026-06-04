<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\Category;
use App\Models\DeliveryRule;
use App\Models\Product;
use App\Models\PricingTier;

class ProductAdminController
{
    private string $imageDir;

    public function __construct()
    {
        $this->imageDir = dirname(__DIR__, 2) . '/public/assets/img/products';
        if (!is_dir($this->imageDir)) {
            mkdir($this->imageDir, 0755, true);
        }
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $filters = [
            'category_id' => isset($_GET['category']) ? (int)$_GET['category'] : null,
            'search'      => trim($_GET['q'] ?? ''),
        ];

        View::render('admin/shop-products', [
            'title'      => 'Products',
            'products'   => Product::all($filters),
            'categories' => Category::all(),
            'tiers'      => PricingTier::all(),
            'counts'     => Product::counts(),
            'search'     => $filters['search'],
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        View::render('admin/shop-product-edit', [
            'title'      => 'Add Product',
            'product'    => null,
            'categories' => Category::all(),
            'tiers'      => PricingTier::all(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $id = Product::create([
            'category_id'         => $_POST['category_id'] ?? null,
            'sku'                 => trim($_POST['sku']  ?? ''),
            'name'                => trim($_POST['name'] ?? ''),
            'slug'                => trim($_POST['slug'] ?? ''),
            'description'         => trim($_POST['description'] ?? ''),
            'stock'               => (int)($_POST['stock'] ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 5),
            'is_active'           => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'          => (int)($_POST['sort_order'] ?? 0),
        ]);

        $this->savePrices($id);
        $this->handleImageUpload($id, true);

        Security::flash('success', 'Product created.');
        Security::redirect('/admin/shop/products/' . $id . '/edit');
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $product = Product::find($id);
        if (!$product) { http_response_code(404); die('Not found'); }

        View::render('admin/shop-product-edit', [
            'title'      => 'Edit: ' . $product['name'],
            'product'    => $product,
            'categories' => Category::all(),
            'tiers'      => PricingTier::all(),
        ], 'admin');
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $product = Product::find($id);
        if (!$product) { http_response_code(404); die('Not found'); }

        Product::update($id, [
            'category_id'         => $_POST['category_id'] ?? null,
            'sku'                 => trim($_POST['sku']  ?? ''),
            'name'                => trim($_POST['name'] ?? ''),
            'slug'                => trim($_POST['slug'] ?? ''),
            'description'         => trim($_POST['description'] ?? ''),
            'stock'               => (int)($_POST['stock'] ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 5),
            'is_active'           => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'          => (int)($_POST['sort_order'] ?? 0),
        ]);

        $this->savePrices($id);
        $this->handleImageUpload($id);

        Security::flash('success', 'Product updated.');
        Security::redirect('/admin/shop/products/' . $id . '/edit');
    }

    public function deleteImage(int $productId, int $imageId): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $images = Product::images($productId);
        foreach ($images as $img) {
            if ((int)$img['id'] === $imageId) {
                $file = $this->imageDir . '/' . $img['filename'];
                if (file_exists($file)) unlink($file);
                Product::deleteImage($imageId);
                break;
            }
        }

        Security::flash('success', 'Image deleted.');
        Security::redirect('/admin/shop/products/' . $productId . '/edit');
    }

    public function delete(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        foreach (Product::images($id) as $img) {
            $file = $this->imageDir . '/' . $img['filename'];
            if (file_exists($file)) unlink($file);
        }
        Product::delete($id);

        Security::flash('success', 'Product deleted.');
        Security::redirect('/admin/shop/products');
    }

    public function stock(): void
    {
        Auth::requireAdmin();
        View::render('admin/shop-stock', [
            'title'     => 'Stock Management',
            'products'  => Product::all(),
            'lowStock'  => Product::lowStock(),
        ], 'admin');
    }

    public function updateStock(int $id): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $stock = (int)($_POST['stock'] ?? 0);
        Product::updateStock($id, $stock);

        Security::flash('success', 'Stock updated.');
        Security::redirect('/admin/shop/stock');
    }

    public function exportCsv(): void
    {
        Auth::requireAdmin();
        $products = Product::all();
        $tiers    = PricingTier::all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        $headers = ['ID', 'SKU', 'Name', 'Category', 'Stock', 'Active', 'Sort Order'];
        foreach ($tiers as $tier) {
            $headers[] = $tier['name'] . ' Price';
        }
        fputcsv($out, $headers);

        foreach ($products as $p) {
            $prices   = Product::prices((int)$p['id']);
            $row      = [$p['id'], $p['sku'], $p['name'], $p['category_name'] ?? '', $p['stock'], $p['is_active'] ? 'Yes' : 'No', $p['sort_order']];
            foreach ($tiers as $tier) {
                $row[] = $prices[(int)$tier['id']]['price'] ?? '';
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function importCsv(): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        if (empty($_FILES['csv']['tmp_name'])) {
            Security::flash('error', 'No file uploaded.');
            Security::redirect('/admin/shop/products');
        }

        $tiers   = PricingTier::all();
        $tierMap = [];
        foreach ($tiers as $t) {
            $tierMap[strtolower($t['name'])] = (int)$t['id'];
        }

        $handle  = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;
            $sku  = trim($row[1] ?? '');
            $name = trim($row[2] ?? '');
            if (!$sku || !$name) continue;

            $existing = Product::findBySku($sku);
            if ($existing) {
                Product::update((int)$existing['id'], [
                    'category_id'         => $existing['category_id'],
                    'sku'                 => $sku,
                    'name'                => $name,
                    'slug'                => Product::slugify($name),
                    'description'         => $existing['description'],
                    'stock'               => isset($row[4]) ? (int)$row[4] : (int)$existing['stock'],
                    'low_stock_threshold' => (int)$existing['low_stock_threshold'],
                    'is_active'           => 1,
                    'sort_order'          => (int)($row[6] ?? $existing['sort_order']),
                ]);
                $productId = (int)$existing['id'];
            } else {
                $productId = Product::create([
                    'sku'   => $sku,
                    'name'  => $name,
                    'slug'  => Product::slugify($name),
                    'stock' => isset($row[4]) ? (int)$row[4] : 0,
                    'is_active' => 1,
                ]);
            }

            // Import tier prices (columns 7+)
            foreach ($tiers as $i => $tier) {
                $col = 7 + $i;
                if (isset($row[$col]) && $row[$col] !== '') {
                    Product::setPrice($productId, (int)$tier['id'], (float)$row[$col]);
                }
            }
            $imported++;
        }
        fclose($handle);

        Security::flash('success', "{$imported} product(s) imported.");
        Security::redirect('/admin/shop/products');
    }

    private function savePrices(int $productId): void
    {
        $prices = $_POST['prices'] ?? [];
        foreach ($prices as $tierId => $price) {
            if ($price !== '') {
                Product::setPrice($productId, (int)$tierId, (float)$price);
            }
        }
    }

    private function handleImageUpload(int $productId, bool $firstIsPrimary = false): void
    {
        if (empty($_FILES['images']['name'][0])) return;

        $files  = $_FILES['images'];
        $isPrimary = $firstIsPrimary;

        foreach ($files['name'] as $i => $name) {
            if (!$name || $files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) continue;

            $filename = 'prod-' . $productId . '-' . uniqid() . '.' . $ext;
            $dest     = $this->imageDir . '/' . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                Product::addImage($productId, $filename, $name, $isPrimary);
                $isPrimary = false;
            }
        }
    }
}
