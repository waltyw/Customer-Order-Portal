# Deployment Guide — WVM Funeral Supplies Customer Portal

## Server Requirements
- PHP 8.1+ with extensions: PDO, PDO_MySQL, mbstring, fileinfo, openssl
- MySQL 8.0+ or MariaDB 10.5+
- Apache with mod_rewrite (or Nginx with equivalent rewrite rules)
- Composer (or pre-install vendor/ locally and upload it)
- HTTPS (required — Stripe will not process payments without it)

---

## Step-by-Step Deployment

### 1. Upload files

The project root sits **outside** the web root for security. Only the `public/` folder is served.

```
/home/yourusername/
  wvm-portal/               ← project root (private, NOT web-accessible)
    config/
    src/
    templates/
    storage/
    vendor-standalone/      ← PHPMailer (already included)
    vendor/                 ← Stripe + autoloader (composer install)
    .env
    database.sql
    database-shop.sql
    database-wvm.sql
  public_html/              ← or the domain's document root
    index.php               ← copy from public/index.php
    .htaccess               ← copy from public/.htaccess
    assets/                 ← copy public/assets/
```

**Recommended (cPanel/WHM with addon domain):**
Set the addon domain document root to `/home/yourusername/wvm-portal/public`
Then the entire project root is private and only `public/` is served.

Upload all files via FTP/SFTP or git clone.

---

### 2. Install PHP dependencies

```bash
cd /home/yourusername/wvm-portal
composer install --no-dev --optimize-autoloader
```

If Composer is not on the server, run it locally first then upload the `vendor/` folder.

---

### 3. Create and configure the `.env` file

```bash
cp .env.example .env
nano .env
```

Fill in every value:

```ini
APP_NAME="WVM Funeral Supplies Portal"
APP_URL=https://portal.wvmfuneralsupplies.com
APP_DEBUG=false

DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_db_user
DB_PASS=your_db_password

SMTP_HOST=mail.wvmfuneralsupplies.com
SMTP_USER=portal@wvmfuneralsupplies.com
SMTP_PASS=your_email_password
SMTP_PORT=465
SMTP_FROM_EMAIL=portal@wvmfuneralsupplies.com
SMTP_FROM_NAME="WVM Funeral Supplies"

STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Generate a random APP_KEY:
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

### 4. Import the database

Run all three SQL files **in order**:

```bash
mysql -u your_db_user -p your_database_name < database.sql
mysql -u your_db_user -p your_database_name < database-shop.sql
mysql -u your_db_user -p your_database_name < database-wvm.sql
```

- `database.sql` — core tables (users, tickets, invoices, settings)
- `database-shop.sql` — shop tables (products, basket, orders, delivery rules, pricing tiers)
- `database-wvm.sql` — WVM customisations (address fields, tier names, Dignity invoice hiding)

All three are **safe to re-run** on an existing database (idempotent).

---

### 5. Set folder permissions

```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/attachments/
chmod 755 public/assets/img/products/
chmod 600 .env
```

---

### 6. Configure Stripe Webhook

In Stripe Dashboard → Developers → Webhooks:
- Add endpoint: `https://portal.wvmfuneralsupplies.com/webhook/stripe`
- Events: `checkout.session.completed`
- Copy the signing secret into `.env` as `STRIPE_WEBHOOK_SECRET`

---

### 7. First login

Default admin credentials (set in `database.sql`):
- URL: `https://portal.wvmfuneralsupplies.com/login`
- Email: `ian@beebizzi.co.uk`
- Password: `password`

**Change the password immediately after first login** using the forgot-password flow, or via MySQL:

```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
```
```sql
UPDATE users SET password_hash = '$2y$12$...' WHERE email = 'ian@beebizzi.co.uk';
```

---

### 8. Admin setup checklist (first time)

After logging in as admin:

1. **Settings** (`/admin/settings`) — set portal name, logo, support email, VAT rate, currency
2. **Pricing Tiers** (`/admin/shop/tiers`) — Customer, Wholesaler, Trade, Dignity, Dignity 2, Custom are pre-seeded
3. **Delivery Rules** (`/admin/shop/delivery`) — Standard Delivery (£6.95) and Free over £150 are pre-seeded
4. **Add customers** (`/admin/customers/create`) — assign pricing tier and checkout method (Stripe or PO) per customer
5. **Import existing customers** (`/admin/customers/import`) — use the CSV template

---

## Security Checklist

- [ ] HTTPS enabled with valid SSL certificate
- [ ] Admin password changed from `password`
- [ ] `.env` not web-accessible (test: `curl https://your-domain/.env` should return 403/404)
- [ ] `storage/` not web-accessible
- [ ] `APP_DEBUG=false` in production
- [ ] Stripe webhook secret set and verified
- [ ] `public/assets/img/products/` is writable (product image uploads)
- [ ] `storage/attachments/` and `storage/logs/` are writable

---

## Nginx Configuration (if not using Apache)

```nginx
server {
    listen 443 ssl;
    server_name portal.wvmfuneralsupplies.com;
    root /home/yourusername/wvm-portal/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. { deny all; }
}
```
