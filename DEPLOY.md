# Deployment Guide — portal.bbizzi.co.uk

## Server Requirements
- PHP 8.1+
- MySQL 8+ or MariaDB 10.5+
- Apache with mod_rewrite
- Composer (or run locally and upload vendor/)

## Step-by-Step Deployment

### 1. Upload files
Upload everything EXCEPT the `public/` folder to a directory **outside** your web root.
Recommended structure on the server:

```
/home/yourusername/
  customer-portal/          ← upload project root here (private)
    config/
    src/
    templates/
    storage/
    vendor/
    .env
  public_html/
    customer-portal/        ← ONLY upload contents of public/ here
      index.php
      .htaccess
      assets/
```

Or, if `portal.bbizzi.co.uk` has its own document root in WHM:
- Set document root to `/home/yourusername/customer-portal/public`
- Keep everything else at `/home/yourusername/customer-portal/`

### 2. Install dependencies
```bash
cd /home/yourusername/customer-portal
composer install --no-dev --optimize-autoloader
```

### 3. Configure environment
```bash
cp .env.example .env
nano .env
```
Fill in all values: DB credentials, SMTP, Stripe keys.

Generate APP_KEY:
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 4. Import database schema
```bash
mysql -u yourusername -p your_database_name < schema.sql
```

### 5. Set permissions
```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/attachments/
chmod 600 .env
```

### 6. Configure Stripe Webhook
In your Stripe Dashboard → Developers → Webhooks:
- Add endpoint: `https://portal.bbizzi.co.uk/webhook/stripe`
- Events to listen: `checkout.session.completed`
- Copy the webhook signing secret into `.env` as `STRIPE_WEBHOOK_SECRET`

### 7. First login
- URL: https://portal.bbizzi.co.uk/login
- Email: admin@beebizzi.co.uk
- Password: **ChangeMe123!**  ← CHANGE THIS IMMEDIATELY

To change: log in, then use the forgot password flow or update via MySQL:
```sql
UPDATE users SET password_hash = '$2y$12$...' WHERE email = 'admin@beebizzi.co.uk';
-- Generate hash with: php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
```

## Security Checklist
- [ ] HTTPS enabled (required — Stripe won't work without it)
- [ ] Admin password changed from default
- [ ] .env file not accessible via web (verify: curl https://portal.bbizzi.co.uk/.env returns 403)
- [ ] storage/ directory not accessible via web
- [ ] Stripe webhook secret set in .env
- [ ] APP_DEBUG=false in production

## Phase 2 — Xero Integration
When ready, install the Xero SDK:
```bash
composer require xeroapi/xero-php-oauth2
```
Then implement `src/Invoices/XeroSync.php` to pull invoices via OAuth2.

## Phase 3 — WHM Integration
The WHM API is accessible at `localhost:2087` (local socket) using your WHM credentials.
Install via: `composer require gufy/cpanel-whm`
