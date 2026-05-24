# HomeBlend Store — Enterprise E-Commerce Platform

Production-ready Laravel 12 e-commerce platform with Filament admin, REST API, Arabic (RTL) support, and EGP currency.

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 |
| Admin | Filament 3 |
| Database | MySQL (SQLite for local dev) |
| API Auth | Laravel Sanctum |
| Auth / 2FA | Laravel Fortify |
| Permissions | Spatie Permission |
| Activity Log | Spatie Activitylog |
| Cache / Queue | Redis (Predis) |
| Payments | PayPal, Cash on Delivery, Local Provider |
| i18n | mcamara/laravel-localization |
| SEO | Spatie Sitemap, Open Graph, Schema.org |

## Architecture

```
app/
├── Concerns/           # Shared traits (HasSlug)
├── Enums/              # OrderStatus, ProductStatus, CouponType, PaymentGateway
├── Filament/           # Admin resources & widgets
├── Http/
│   ├── Controllers/Api/
│   ├── Controllers/Shop/
│   ├── Requests/
│   └── Resources/      # API transformers
├── Jobs/               # Abandoned cart recovery
├── Models/
├── Policies/
├── Repositories/       # Contract + Eloquent implementations
└── Services/           # Cart, Checkout, Coupon, Loyalty, Payment, Shipping, Tax
```

## Features

- **Catalog**: Categories (nested), products, variants, gallery, related products
- **Commerce**: Cart (guest + persistent), checkout, orders, refunds
- **Marketing**: Coupons, loyalty points, VIP levels, flash sales, bundles, gift cards
- **Payments**: PayPal, COD, configurable local gateway
- **Shipping**: Zones, rates, free shipping rules
- **Admin**: Filament CRUD, sales dashboard, roles/permissions, activity logs
- **API**: Versioned REST (`/api/v1/*`)
- **Frontend**: Responsive Tailwind shop (Arabic RTL), AJAX cart, checkout page

## اللغة (عربي بالكامل)

- اللغة الافتراضية: **العربية** (`ar`) مع واجهة **RTL**
- لوحة Filament: عربية + خط Cairo
- التحقق من الحقول ورسائل API بالعربية
- العملة: **جنيه مصري (EGP)**

تأكد من إعداد `.env`:

```env
APP_NAME="هوم بلند"
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
```

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build   # required for compiled Tailwind (optional if using shop CDN fallback)
php artisan serve
```

- **Storefront**: http://localhost:8000/ar
- **Admin**: http://localhost:8000/admin  
  - Email: `admin@homeblend.store`  
  - Password: `password`

## Environment

```env
APP_LOCALE=ar
ECOMMERCE_CURRENCY=EGP

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=homeblend

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis

PAYPAL_MODE=sandbox
PAYPAL_SANDBOX_CLIENT_ID=
PAYPAL_SANDBOX_CLIENT_SECRET=

LOCAL_PAYMENT_API_URL=
LOCAL_PAYMENT_API_KEY=
```

## API Examples

```bash
# List products
curl http://localhost:8000/api/v1/products

# Guest cart (use X-Session-Id header)
curl -X POST http://localhost:8000/api/v1/cart/items \
  -H "X-Session-Id: guest-abc" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":1}'

# Register & checkout (Bearer token)
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"t@test.com","password":"password","password_confirmation":"password"}'

# Apply coupon, shipping, addresses, reviews, refunds (auth where noted)
curl -X POST http://localhost:8000/api/v1/cart/coupon -H "Authorization: Bearer TOKEN" -d '{"code":"SAVE10"}'
curl http://localhost:8000/api/v1/shipping/rates
curl -X GET http://localhost:8000/api/v1/orders/1/invoice -H "Authorization: Bearer TOKEN"
```

### Shop pages

| Route | Description |
|-------|-------------|
| `/ar/cart` | AJAX cart (loads `/api/v1/cart`) |
| `/ar/checkout` | Checkout form → `POST /api/v1/checkout` |
| `/ar/products/{slug}` | Product detail + add to cart |

## Commands

```bash
php artisan sitemap:generate   # SEO XML sitemap
php artisan queue:work           # Process jobs (abandoned cart emails)
php artisan schedule:work        # Scheduler (sitemap + cart recovery)
```

## Tests

```bash
php artisan test
```

## Frontend assets (Vite)

Requires **Node.js 18+** (this project uses Vite 5 + Tailwind 3 for Node 18 compatibility).

```bash
# If you had a failed install, clean first:
rm -rf node_modules package-lock.json

npm install
npm run build
```

For development with hot reload:

```bash
npm run dev
```

Optional: use Node 20+ via [nvm](https://github.com/nvm-sh/nvm) (`nvm install` reads `.nvmrc`).

## Production Checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`
2. Configure MySQL + Redis
3. Run `php artisan config:cache route:cache view:cache`
4. Set up queue worker & scheduler (Supervisor)
5. Configure PayPal live credentials
6. Enable HTTPS, rate limiting, and backups

## License

MIT
