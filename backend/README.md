# Plain PHP Backend

This backend now runs as plain PHP (no Laravel runtime boot required).

## Entry Points

- Primary: `index.php` (project root)
- Public fallback: `public/index.php` (loads root `index.php`)

## API Base

- All APIs are under `/api/...`
- Health endpoint: `/api/health`

## Required Server Setup

1. PHP 8.2+
2. Apache rewrite enabled (`.htaccess` in backend root)
3. `vendor/` available (for `firebase/php-jwt`)
4. Writable upload directory:
   - `uploads/`

## Minimal Deploy Steps

1. Upload backend files to your domain document root.
2. Ensure these files exist at the root:
   - `index.php`
   - `.htaccess`
   - `.env`
   - `vendor/`
3. Update `.env` with:
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `JWT_SECRET`
   - optional: `CORS_ORIGIN`, `BASE_URL`, mail notification addresses
4. Test:
   - `/api/health`
