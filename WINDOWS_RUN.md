# Running Unify V9 on Windows (local test)

The repo is **single-server mode**: the built React app is already inside
`unify-backend/public/`, so you only need **PHP + Composer** to run it — no
Node.js required (Node is only needed to rebuild the frontend).

## 1. Install PHP + Composer (one-time)

Option A — **XAMPP** (easiest):
1. Download & install XAMPP from https://www.apachefriends.org (any recent version, PHP 8.2+).
2. Run XAMPP Control Panel → make sure **Apache** and **MySQL** are NOT required for this test — we use PHP's built-in server + SQLite.

Option B — **Laragon** (recommended): https://laragon.org — comes with PHP + Composer.

Make sure `php` works in your terminal:
```
php -v
```
If not, add PHP to your PATH (e.g. `C:\xampp\php`) or use the "Open Terminal" button in Laragon/XAMPP shell.

Install Composer if it isn't bundled: https://getcomposer.org/download/

## 2. Get the project

Copy / clone the repo to e.g. `C:\Unify-v3`.

## 3. Install dependencies + configure

Open a terminal in `unify-backend`:

```bat
cd C:\Unify-v3\unify-backend
composer install
copy .env.example .env
php artisan key:generate
```

That's it — the default `.env.example` uses **SQLite** (a pre-seeded
`database/database.sqlite` is already in the repo with demo accounts), so no
database server or config is needed.

> If you'd rather use MySQL: edit `.env` → `DB_CONNECTION=mysql` + fill the
> MySQL fields, then run `php artisan migrate --seed`.

## 4. Start the server

```bat
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://localhost:8000** in your browser.

## 5. Login

| Role    | Username     | Password         |
|---------|--------------|------------------|
| Student | `400100001`  | `TempStudent!2026` |
| Owner   | `990000001`  | `TempOwner!2026`   |
| Professor | `P1001`    | `TempProf!2026`   |
| Expert  | `300000001`  | `TempExpert!2026` |
| Head    | `400000001`  | `TempHead!2026`   |
| Admin   | `500000001`  | `TempAdmin!2026`  |

On first login you'll be asked to set a name + new password (IT-handout flow).
**Important:** after changing the password, log in with the **new** password.
If you ever need to reset demo accounts, run:
```bat
php artisan db:seed --force
```

## 6. Useful commands

```bat
php artisan migrate --force          # apply migrations (already applied to the committed DB)
php artisan db:seed --force          # re-seed demo data
php artisan tinker                   # interactive console
```

## Notes

- **Storage-blocked iframes aren't a problem in a normal browser tab** — the
  session persists normally on Windows/Chrome/Edge.
- The service worker is registered for PWA; hard-refresh (`Ctrl+Shift+R`) once
  after first load to make sure you get the latest build.
- To rebuild the frontend after code changes (needs Node):
  ```bat
  cd C:\Unify-v3\frontend
  npm install
  npm run build
  xcopy /E /I dist C:\Unify-v3\unify-backend\public
  ```
