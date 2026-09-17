# Restock

Restock is an inventory and reorder-management app for small shops. It tracks
products, suppliers, and stock movements per shop, forecasts when a product
will run out based on recent sales velocity, and notifies shop owners when
it's time to reorder.

Built with [Laravel](https://laravel.com), [Inertia.js](https://inertiajs.com) v3,
and [Vue 3](https://vuejs.org).

## Features

- **Products** — track SKU, barcode, category, cost/sell price, current
  stock, and reorder thresholds per shop.
- **Suppliers** — store contact details and lead time (days) used in
  reorder forecasting.
- **Stock movements** — record sales, restocks, and manual adjustments
  against a product; movements drive the current stock and sales history.
- **Stock movement import** — bulk-import stock movements from a
  spreadsheet (via [maatwebsite/excel](https://laravel-excel.com)).
- **Forecasting & reorder alerts** — `ForecastService` computes daily sales
  velocity and estimated days until stockout per product, and flags a
  product for reorder once that falls within its supplier's lead time.
- **Low-stock notifications** — a scheduled command
  (`send:low-stock-notification`) checks each shop's reorder list daily and
  notifies the shop owner (mail/database) when it's non-empty.
- **Authentication** — registration, login, password reset, email
  verification, and profile/security settings via
  [Laravel Fortify](https://laravel.com/docs/fortify).

## Tech stack

- **Backend:** PHP 8.5, Laravel 13, Fortify (auth), maatwebsite/excel (imports)
- **Frontend:** Vue 3, Inertia.js v3, TypeScript, Tailwind CSS v4, Vite
- **Database:** MySQL (via [Laravel Sail](https://laravel.com/docs/sail))
- **Testing:** [Pest](https://pestphp.com)
- **Tooling:** Pint (PHP formatting), Larastan/PHPStan (static analysis),
  ESLint + Prettier (JS/TS)

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm
- Docker (if using Sail) or a local MySQL instance

## Getting started

### Using Laravel Sail (Docker)

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

The app will be available at http://localhost.

### Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* in .env for your local database
php artisan migrate
npm install
npm run dev
```

Or, once your `.env` is configured, run the whole dev setup with:

```bash
composer run setup
```

### Running the app

`composer run dev` starts the PHP server, queue listener, and Vite dev
server together (via `php artisan dev`).

## Testing & code quality

```bash
php artisan test --compact      # run the test suite (Pest)
vendor/bin/pint                 # fix PHP formatting
phpstan analyse                 # static analysis (Larastan)
npm run lint                    # lint & fix JS/TS/Vue
npm run format                  # format resources/ with Prettier
npm run types:check             # Vue/TS type checking
```

## Scheduled tasks

`send:low-stock-notification` runs daily (see `routes/console.php`) and
notifies each shop owner of products that need reordering.

## License

MIT
