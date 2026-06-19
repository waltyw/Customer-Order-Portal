# WVM Customer Portal — Project Status

Last updated: 2026-06-19

---

## What's Built

### Core Portal
- PHP 8.1 MVC, no framework — routing in `public/index.php`, `View::render()` for templates
- MySQL/InnoDB via PDO (`src/Core/DB.php`)
- Session auth with role separation (customer / admin)
- CSRF protection on all POST routes
- PHPMailer for transactional email (SMTP)

### Admin
- Dashboard: order stats bar, recent orders table, recent tickets — no invoice widget
- Customers: list with Branch No, City, Postcode, Tier, Status columns
- Customer create: full form with account details, billing address, delivery address, pricing tier, checkout method
- Customer view/edit: three-column view (Account | Billing | Delivery), inline edit form with all fields including tier and checkout method assignment
- Customer CSV import (18 columns): name, email, company, phone, branch_number, billing address (6 fields), delivery_same_as_billing, delivery address (6 fields) — WooCommerce column name aliases supported
- Customer CSV export template
- Tickets: list, view, reply (with email notification), status update, internal notes
- Invoices: list, create (with line items and email send), view
- Settings: branding, colours, logo/favicon upload, VAT, currency, support email, custom menu links
- System status page: DB connectivity, storage writable, PHP version, SMTP configured, Stripe configured

### Shop — Admin
- Products: list, create/edit (simple + variable types), delete, image upload, SKU, stock, WooCommerce CSV import/export
- Product variations: per-product variations with attribute selectors and per-tier pricing
- Categories: full CRUD with parent/child hierarchy
- Pricing Tiers: Customer, Wholesaler, Trade, Dignity, Dignity 2, Custom — seeded by migration
- Delivery Rules: flat rate and free-over-threshold types, per-tier targeting
- Orders: list with status/search filters, view order detail, update status, export CSV

### Shop — Customer
- Product catalogue with category filtering, search, images
- Product detail page with tier pricing displayed
- Basket: add, update quantities, bulk SKU entry, CSV paste, item count badge in nav
- Checkout: pre-fills billing/delivery from profile; dual checkout method (Stripe card OR Purchase Order)
- Order confirmation: Stripe payment verified on success redirect, order status updated to confirmed
- Order confirmation email sent on both PO and Stripe orders (with CC to cc_email_1/2)
- Order history: list with reorder link, order detail view

### Customer Account
- Update name, company, phone, CC email 1+2
- Update billing address and delivery address (same-as-billing toggle)
- Password reset via email

### Email
- Ticket reply notifications (customer and admin directions)
- Invoice ready notification
- Welcome email with temp password on customer create
- Order confirmation email (PO and Stripe, with CC email support)

### Database Migrations (run in order)
1. `database.sql` — core tables
2. `database-shop.sql` — shop tables
3. `database-wvm.sql` — WVM customisations: address fields, CC emails, branch_number, pricing tier names, Dignity invoice hiding, product variation tables

---

## Pricing Tiers

| ID | Name | Invoices Hidden |
|----|------|----------------|
| 1 | Customer | No |
| 2 | Wholesaler | No |
| 3 | Trade | No |
| 4 | Dignity | Yes |
| 5 | Dignity 2 | Yes |
| 6 | Custom | No |

Dignity and Dignity 2 customers do not see the Invoices nav item or the invoice widget. This is enforced in `templates/layouts/main.php` via `pricing_tiers.hide_invoices`.

---

## File Structure

```
wvm-portal/
├── config/config.php          — DB, autoloader, .env loader
├── src/
│   ├── Auth/Auth.php
│   ├── Controllers/
│   │   ├── AccountController.php
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── BasketController.php
│   │   ├── CheckoutController.php
│   │   ├── DashboardController.php
│   │   ├── HelpController.php
│   │   ├── InvoiceController.php
│   │   ├── OrderController.php
│   │   ├── PaymentController.php
│   │   ├── ProductAdminController.php
│   │   ├── ShopAdminController.php
│   │   ├── ShopController.php
│   │   ├── TicketController.php
│   │   └── WebhookController.php
│   ├── Core/DB.php, Security.php, View.php, Stripe.php, EnvLoader.php
│   ├── Email/Mailer.php
│   └── Models/
│       ├── Address.php, Basket.php, Category.php, DeliveryRule.php
│       ├── Invoice.php, Payment.php, PricingTier.php, Product.php
│       ├── ProductVariation.php, Setting.php, ShopOrder.php
│       ├── Ticket.php, User.php
│       └── Website.php (unused by WVM — kept for base portal compatibility)
├── templates/
│   ├── layouts/main.php, admin.php
│   ├── auth/, customer/, admin/, errors/
├── public/
│   ├── index.php              — web entry point
│   ├── .htaccess
│   └── assets/css, js, img/
├── storage/attachments/, logs/
├── vendor-standalone/PHPMailer/
├── database.sql
├── database-shop.sql
├── database-wvm.sql
├── DEPLOY.md
└── PROJECT-STATUS.md
```

---

## Known Leftover Files (not harmful, not routed)

These files exist from the base Beebizzi portal and are not accessible via any route. They can be deleted if you want a cleaner tree, but leaving them is harmless:

- `templates/admin/faqs.php`
- `templates/admin/faq-edit.php`
- `templates/admin/service-status.php`
- `templates/admin/xero.php`
- `src/Controllers/XeroController.php`
- `src/Core/XeroAPI.php`
- `src/Models/Faq.php`
- `src/Models/ServiceStatus.php`

---

## What's NOT Yet Built (remaining from brief)

### 1. Accounting Export — Sage / QuickBooks
The settings keys `sage_enabled` and `quickbooks_enabled` are seeded in the database (both `0`). No export logic is implemented yet. When ready:
- Add export format (CSV/XML) matching the accounting software's import spec
- Wire a button in `/admin/shop/orders` to download

### 2. cXML Punchout
Not started. Requires a separate endpoint to handle cXML purchase requests from procurement systems (Coupa, Ariba, SAP).

### 3. Admin Order Filter by Customer
The customer view links to `/admin/shop/orders?user_id=X` but `ShopAdminController::orders()` only filters by `status` and search query `q`. The `user_id` filter is not implemented — it will show all orders instead of just that customer's.

### 4. Customer-Facing Invoice PDF Download
Invoices are stored in the DB with line items JSON. No PDF generation is implemented. Would need a library like Dompdf.

---

## Environment Variables Required

```ini
APP_NAME=
APP_URL=
APP_DEBUG=false

DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=

SMTP_HOST=
SMTP_USER=
SMTP_PASS=
SMTP_PORT=465
SMTP_FROM_EMAIL=
SMTP_FROM_NAME=

STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=
```

---

## Server Checklist (before go-live)

- [ ] PHP 8.1+ confirmed
- [ ] MySQL 8+ or MariaDB 10.5+ confirmed
- [ ] `vendor/` installed via Composer (`composer install --no-dev`)
- [ ] `.env` filled in and `chmod 600`
- [ ] All three SQL files imported in order
- [ ] Admin password changed from `password`
- [ ] `storage/` and `public/assets/img/products/` are writable (`chmod 755`)
- [ ] HTTPS with valid SSL certificate
- [ ] Stripe webhook endpoint configured and secret saved in `.env`
- [ ] Test order placed end-to-end (PO + Stripe)
- [ ] Test welcome email delivered
- [ ] Test order confirmation email delivered
