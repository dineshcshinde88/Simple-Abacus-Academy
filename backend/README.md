# Laravel Backend Migration

This backend has been rewritten from Node/Express to Laravel-style PHP API code to support shared hosting environments without Node.js support.

## What Changed

- API endpoints moved to Laravel route file: `routes/api.php`
- JWT auth implemented via `firebase/php-jwt`
- Role/subscription middleware ported to Laravel
- Eloquent models created for all previous Prisma entities
- Single migration file added for schema creation
- Old Node/Express source files were neutralized

## API Base

- Routes are defined in `routes/api.php` and use Laravel's `/api` prefix.
- Example health URL: `/api/health`

## Setup Steps

1. Ensure server has PHP 8.2+ and Composer.
2. In `backend/`, run:
   - `composer install`
3. Copy env file:
   - `cp .env.example .env`
4. Configure DB/JWT/mail variables in `.env`.
5. Generate app key:
   - `php artisan key:generate`
6. Run migrations:
   - `php artisan migrate --force`
7. Point your domain/subfolder document root to Laravel `public/`.

## Important

- This machine does not have PHP/Composer CLI, so runtime commands were not executed here.
- If your hosting is cPanel shared hosting, deploy this as a PHP app (not Passenger Node app).

