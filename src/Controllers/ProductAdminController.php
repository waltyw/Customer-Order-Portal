<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Security;
use App\Core\View;
use App\Models\Category;
use App\Models\DeliveryRule;
use App\Models\Product;
use App\Models\ProductVariation;
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

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        // Strip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            Security::flash('error', 'CSV file is empty or unreadable.');
            Security::redirect('/admin/shop/products');
        }

        // Normalise header names to lowercase, trim whitespace
        $headers = array_map(fn($h) => strtolower(trim((string)$h)), $rawHeaders);
        $col     = array_flip($headers); // column name → index

        $tiers   = PricingTier::all();
        // Build tier column map: "customer price" / "wholesaler price" etc.
        $tierColMap = [];
        foreach ($tiers as $t) {
            $tierColMap[strtolower($t['name']) . ' price'] = (int)$t['id'];
            $tierColMap[strtolower($t['name'])]            = (int)$t['id'];
        }

        $isWooCommerce = isset($col['type']); // WooCommerce exports have a Type column

        $imported   = 0;
        $varBuffer  = []; // SKU => [row data] for variation rows
        $parentMap  = []; // parent SKU => product ID

        $get = fn(array $row, string $key, string $fallback = '') => trim($row[$col[$key] ?? -1] ?? $fallback);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;

            if ($isWooCommerce) {
                $type   = strtolower($get($row, 'type'));
                $sku    = $get($row, 'sku');
                $name   = $get($row, 'name');
                $parent = $get($row, 'parent');

                if ($type === 'variation') {
                    // Buffer variations — process after all parents are created
                    $varBuffer[] = $row;
                    continue;
                }

                // simple or variable (parent)
                $productId = $this->upsertProduct([
                    'type'              => $type === 'variable' ? 'variable' : 'simple',
                    'sku'               => $sku,
                    'name'              => $name ?: $sku,
                    'description'       => $get($row, 'description'),
                    'short_description' => $get($row, 'short description'),
                    'stock'             => (int)$get($row, 'stock'),
                    'is_active'         => $get($row, 'published', '1') !== '0' ? 1 : 0,
                    'woo_id'            => (int)($row[$col['id'] ?? -1] ?? 0) ?: null,
                    'category_name'     => $get($row, 'categories'),
                    'regular_price'     => (float)$get($row, 'regular price'),
                ], $tiers, $tierColMap, $col, $row);

                if ($productId) {
                    $parentMap[$sku] = $productId;
                    $this->importWooAttributes($productId, $col, $row, $type === 'variable');
                    $imported++;
                }

            } else {
                // Native portal CSV format
                $sku  = trim($row[$col['sku'] ?? 1] ?? '');
                $name = trim($row[$col['name'] ?? 2] ?? '');
                if (!$sku || !$name) continue;

                $productId = $this->upsertProduct([
                    'type'          => 'simple',
                    'sku'           => $sku,
                    'name'          => $name,
                    'stock'         => (int)($row[$col['stock'] ?? 4] ?? 0),
                    'is_active'     => 1,
                    'regular_price' => 0,
                    'category_name' => trim($row[$col['category'] ?? 3] ?? ''),
                ], $tiers, $tierColMap, $col, $row);

                if ($productId) $imported++;
            }
        }

        // Process buffered variations
        foreach ($varBuffer as $row) {
            $parentSku   = $get($row, 'parent');
            $parentId    = $parentMap[$parentSku] ?? null;
            if (!$parentId) continue;

            $varSku   = $get($row, 'sku') ?: ($parentSku . '-var-' . uniqid());
            $price    = (float)$get($row, 'regular price');
            $stock    = (int)$get($row, 'stock');
            $wooId    = (int)($row[$col['id'] ?? -1] ?? 0) ?: null;
            $published = $get($row, 'published', '1');

            $existing = ProductVariation::findBySku($varSku);
            if ($existing) {
                ProductVariation::update((int)$existing['id'], [
                    'sku'       => $varSku,
                    'stock'     => $stock,
                    'is_active' => $published !== '0' ? 1 : 0,
                ]);
                $varId = (int)$existing['id'];
            } else {
                $varId = ProductVariation::create($parentId, [
                    'sku'       => $varSku,
                    'stock'     => $stock,
                    'is_active' => $published !== '0' ? 1 : 0,
                    'woo_id'    => $wooId,
                ]);
            }

            // Price → set for all tiers (use regular price as base; override with specific tier columns if present)
            foreach ($tiers as $tier) {
                $tierKey = strtolower($tier['name']) . ' price';
                if (isset($col[$tierKey]) && $row[$col[$tierKey]] !== '') {
                    ProductVariation::setPrice($varId, (int)$tier['id'], (float)$row[$col[$tierKey]]);
                } elseif ($price > 0) {
                    ProductVariation::setPrice($varId, (int)$tier['id'], $price);
                }
            }

            // Variation attribute values
            $attrIndex = 1;
            while (isset($col['attribute ' . $attrIndex . ' name'])) {
                $attrName = $get($row, 'attribute ' . $attrIndex . ' name');
                $termName = $get($row, 'attribute ' . $attrIndex . ' value(s)');
                if ($attrName && $termName) {
                    $attrId = ProductVariation::findOrCreateAttribute($attrName);
                    $termId = ProductVariation::findOrCreateTerm($attrId, $termName);
                    ProductVariation::setAttributeTerm($varId, $attrId, $termId);
                }
                $attrIndex++;
            }
        }

        fclose($handle);
        Security::flash('success', "{$imported} product(s) imported" . (count($varBuffer) ? ', ' . count($varBuffer) . ' variation(s) processed' : '') . '.');
        Security::redirect('/admin/shop/products');
    }

    // ── Variation management ──────────────────────────────────────────────────

    public function variations(int $productId): void
    {
        Auth::requireAdmin();
        $product = Product::find($productId);
        if (!$product) { http_response_code(404); die('Not found'); }

        View::render('admin/shop-product-variations', [
            'title'      => 'Variations: ' . $product['name'],
            'product'    => $product,
            'variations' => ProductVariation::forProduct($productId),
            'attributes' => ProductVariation::attributesForProduct($productId),
            'tiers'      => PricingTier::all(),
        ], 'admin');
    }

    public function storeVariation(int $productId): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();

        $varId = ProductVariation::create($productId, [
            'sku'        => trim($_POST['sku'] ?? ''),
            'stock'      => (int)($_POST['stock'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);

        foreach ($_POST['prices'] ?? [] as $tierId => $price) {
            if ($price !== '') ProductVariation::setPrice($varId, (int)$tierId, (float)$price);
        }

        foreach ($_POST['attr'] ?? [] as $attrId => $termName) {
            if (!$termName) continue;
            $termId = ProductVariation::findOrCreateTerm((int)$attrId, $termName);
            ProductVariation::setAttributeTerm($varId, (int)$attrId, $termId);
        }

        // Ensure parent is marked variable
        \App\Core\DB::execute("UPDATE products SET type = 'variable' WHERE id = ?", [$productId]);

        Security::flash('success', 'Variation added.');
        Security::redirect('/admin/shop/products/' . $productId . '/variations');
    }

    public function deleteVariation(int $productId, int $varId): void
    {
        Auth::requireAdmin();
        Security::checkCsrf();
        ProductVariation::delete($varId);
        Security::flash('success', 'Variation deleted.');
        Security::redirect('/admin/shop/products/' . $productId . '/variations');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function upsertProduct(array $data, array $tiers, array $tierColMap, array $col, array $row): ?int
    {
        $sku  = $data['sku'] ?? '';
        $name = $data['name'] ?? '';
        if (!$sku || !$name) return null;

        // Resolve category
        $categoryId = null;
        if (!empty($data['category_name'])) {
            $catName = explode('>', $data['category_name'])[0];
            $catName = trim(explode(',', $catName)[0]);
            if ($catName) {
                $catSlug = Category::slugify($catName);
                $cat     = \App\Core\DB::fetchOne('SELECT id FROM product_categories WHERE slug = ?', [$catSlug]);
                if (!$cat) {
                    $catId = Category::create(['name' => $catName, 'slug' => $catSlug, 'is_active' => 1]);
                } else {
                    $catId = (int)$cat['id'];
                }
                $categoryId = $catId;
            }
        }

        $payload = [
            'type'              => $data['type'] ?? 'simple',
            'category_id'       => $categoryId,
            'sku'               => $sku,
            'name'              => $name,
            'slug'              => Product::slugify($name),
            'description'       => $data['description'] ?? '',
            'short_description' => $data['short_description'] ?? '',
            'stock'             => $data['stock'] ?? 0,
            'is_active'         => $data['is_active'] ?? 1,
            'woo_id'            => $data['woo_id'] ?? null,
        ];

        $existing = Product::findBySku($sku);
        if ($existing) {
            $payload['low_stock_threshold'] = (int)$existing['low_stock_threshold'];
            $payload['sort_order'] = (int)$existing['sort_order'];
            Product::update((int)$existing['id'], $payload);
            $productId = (int)$existing['id'];
        } else {
            $payload['low_stock_threshold'] = 5;
            $payload['sort_order'] = 0;
            $productId = Product::create($payload);
        }

        // Set tier prices — check for explicit tier columns first, fall back to regular price
        $basePrice = $data['regular_price'] ?? 0;
        foreach ($tiers as $tier) {
            $tierKey = strtolower($tier['name']) . ' price';
            $idx     = $col[$tierKey] ?? -1;
            if ($idx >= 0 && isset($row[$idx]) && $row[$idx] !== '') {
                Product::setPrice($productId, (int)$tier['id'], (float)$row[$idx]);
            } elseif ($basePrice > 0) {
                Product::setPrice($productId, (int)$tier['id'], $basePrice);
            }
        }

        return $productId;
    }

    private function importWooAttributes(int $productId, array $col, array $row, bool $isVariable): void
    {
        $get = fn(string $key) => trim($row[$col[$key] ?? -1] ?? '');
        $attrIndex = 1;
        while (isset($col['attribute ' . $attrIndex . ' name'])) {
            $attrName     = $get('attribute ' . $attrIndex . ' name');
            $attrValues   = $get('attribute ' . $attrIndex . ' value(s)');
            $isVariationAttr = $get('attribute ' . $attrIndex . ' global') !== '0';

            if (!$attrName || !$attrValues) { $attrIndex++; continue; }

            $attrId  = ProductVariation::findOrCreateAttribute($attrName);
            $termIds = [];
            foreach (explode('|', $attrValues) as $val) {
                $val = trim($val);
                if ($val) $termIds[] = ProductVariation::findOrCreateTerm($attrId, $val);
            }
            if ($termIds) {
                ProductVariation::mapAttributeToProduct($productId, $attrId, $termIds, $isVariable && $isVariationAttr);
            }
            $attrIndex++;
        }
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
