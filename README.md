# Customer Portal

A self-hosted PHP customer portal with invoicing, support tickets, and Stripe payments.

## Features

- Customer login with password reset
- Invoice management and PDF viewing
- Online payments via Stripe Checkout
- Bank transfer payment option
- Support ticket system with file attachments
- Admin dashboard (manage customers, invoices, tickets)

## Requirements

- PHP 8.1+
- MySQL 8+ or MariaDB 10.5+
- Apache with `mod_rewrite` enabled
- Composer
- HTTPS (required for Stripe)

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/waltyw/BB-Customer-portal.git
cd BB-Customer-portal
```

### 2. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure environment

```bash
cp .env.example .env
```

Open `.env` and fill in your values — see [Environment Variables](#environment-variables) below.

Generate a secure `APP_KEY`:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### 4. Create the database

Create a MySQL database, then import the schema:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < schema.sql
```

### 5. Set file permissions

```bash
chmod 755 storage/
chmod 755 storage/logs/
chmod 755 storage/attachments/
chmod 600 .env
```

### 6. Configure your web server

**Option A — Subdomain with its own document root (recommended)**

Set the document root for your subdomain to the `public/` directory:

```
/home/youruser/customer-portal/          ← project root (private)
/home/youruser/customer-portal/public/   ← document root
```

**Option B — Subdirectory of an existing site**

Upload the project root outside `public_html`, then copy/symlink only the `public/` contents into your web-accessible path.

### 7. Configure Stripe webhook

In **Stripe Dashboard → Developers → Webhooks**:

- Add endpoint: `https://yourdomain.com/webhook/stripe`
- Event to listen for: `checkout.session.completed`
- Copy the signing secret into `.env` as `STRIPE_WEBHOOK_SECRET`

### 8. First login

After setup, log in with the admin account created during installation.

> **Important:** Change the default admin password immediately after first login.

---

## Environment Variables

Copy `.env.example` to `.env`. Never commit `.env` to version control.

| Variable | Description |
|---|---|
| `APP_NAME` | Display name of the portal |
| `APP_URL` | Full URL including `https://` |
| `APP_ENV` | `production` or `development` |
| `APP_DEBUG` | `false` in production, `true` for debugging |
| `APP_KEY` | Random 32-byte hex key — generate with `php -r "echo bin2hex(random_bytes(32));"` |
| `DB_HOST` | Database host (usually `localhost`) |
| `DB_NAME` | Database name |
| `DB_USER` | Database username |
| `DB_PASS` | Database password |
| `SMTP_HOST` | SMTP server hostname |
| `SMTP_PORT` | SMTP port (`587` for STARTTLS, `465` for SSL) |
| `SMTP_USER` | SMTP username / email address |
| `SMTP_PASS` | SMTP password |
| `SMTP_FROM_NAME` | Sender name shown in emails |
| `SMTP_FROM_EMAIL` | Sender email address |
| `STRIPE_SECRET_KEY` | Stripe secret key (`sk_live_...`) — from Stripe Dashboard → API Keys |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key (`pk_live_...`) |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook signing secret (`whsec_...`) |
| `BANK_NAME` | Bank name shown on bank transfer instructions |
| `BANK_ACCOUNT_NAME` | Account name |
| `BANK_SORT_CODE` | Sort code |
| `BANK_ACCOUNT_NUMBER` | Account number |
| `BANK_REFERENCE_PREFIX` | Prefix added to invoice reference for bank transfers |
| `SESSION_NAME` | Cookie name for sessions |
| `SESSION_LIFETIME` | Session duration in seconds (default `7200` = 2 hours) |

---

## Security Checklist

Before going live:

- [ ] HTTPS enabled and forced
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_KEY` set to a unique random value
- [ ] Admin password changed from default
- [ ] `.env` returns 403 when accessed via browser — test with `curl https://yourdomain.com/.env`
- [ ] `storage/` directory not accessible via web
- [ ] Stripe webhook secret configured
- [ ] Database user has only the permissions it needs (no `GRANT`, `DROP`, etc.)

---

## Project Structure

```
├── config/
│   └── config.php          # Bootstrap — loads .env, sessions, Stripe
├── public/                 # Document root — only this folder is web-accessible
│   ├── index.php           # Front controller
│   ├── .htaccess           # Rewrite rules
│   └── assets/             # CSS, JS, images
├── schema.sql              # Database schema
├── src/
│   ├── Auth/               # Authentication logic
│   ├── Controllers/        # Route handlers
│   ├── Core/               # DB, View, Security helpers
│   ├── Email/              # Mailer wrapper
│   └── Models/             # User, Invoice, Payment, Ticket
├── storage/
│   ├── logs/               # Application and PHP error logs
│   └── attachments/        # Ticket file uploads
└── templates/
    ├── admin/              # Admin panel views
    ├── auth/               # Login, forgot/reset password
    ├── customer/           # Customer dashboard views
    ├── errors/             # Error pages
    └── layouts/            # Shared page layouts
```

---

## Roadmap

- [ ] Xero integration for invoice sync
- [ ] WHM/cPanel integration for hosting account management
- [ ] Email notification templates
