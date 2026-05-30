# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running the App

```bash
# Start all services (PHP-FPM, Nginx, PostgreSQL)
docker compose up -d --build

# App is available at http://localhost:8000
# Default admin: admin@budgetflow.local / password

# Useful commands
docker compose logs -f              # stream logs
docker compose exec postgres psql -U budgetflow -d budgetflow   # DB shell
docker compose down -v && docker compose up -d --build          # reset DB
```

There is no test suite, no linter, and no build step — PHP files are served directly.

## Architecture

**Custom MVC framework in pure PHP 8.3, no third-party framework.**

### Request lifecycle

1. **Nginx** receives all requests and proxies PHP to PHP-FPM.
2. **`public/index.php`** is the sole entry point — it `require_once`s every core class, model, and controller manually (no autoloader), then registers all routes and calls `$router->dispatch()`.
3. **`Router`** matches `$_SERVER['REQUEST_METHOD']` + `$_SERVER['REQUEST_URI']` to a handler (closure or `[ControllerClass, 'method']`). Routes do not support URL parameters — IDs are passed via query string (e.g. `?id=5`).
4. **Controllers** instantiate models directly, collect `$_GET`/`$_POST`, call model methods, and render views via `require`.
5. **Views** are plain PHP templates. They use two shared layouts: `app/views/layouts/app.php` (authenticated pages) and `app/views/layouts/guest.php` (login/register). Controllers pass data as local variables before requiring the view file.

### Core classes (`core/`)

| Class | Purpose |
|---|---|
| `Database` | PDO singleton for PostgreSQL. Call `Database::getInstance()`. |
| `Router` | GET/POST route dispatcher. Throws `RedirectException` to halt after redirects. |
| `Session` | Starts session, provides `setFlash()`/`getFlash()` for one-time messages. |
| `Auth` | Static helpers: `isLoggedIn()`, `getUser()`, `requireRole('user'|'admin')`. |
| `CSRF` | Token generation/validation. Use `CSRF::getTokenField()` in forms, validate in POST handlers. |

### Adding a new route

1. Register in `public/index.php` with `$router->get()` or `$router->post()`.
2. Wrap with `Auth::requireRole('user')` for protected routes.
3. For POST handlers, always validate the CSRF token: `CSRF::validateToken($_POST['csrf_token'] ?? '')`.

### Database

PostgreSQL 16. Five tables: `users`, `categories`, `budgets`, `transactions`, `budget_members`. Schema is at `database/schema.sql` — it runs automatically on first Docker volume creation. To apply schema changes in development, reset the volume (`docker compose down -v`).

All queries use PDO prepared statements via `Database::getInstance()`. Models receive the PDO instance in their constructor.

### Configuration

All environment-sensitive values come from `config/config.php`, which reads from `getenv()` with hardcoded fallbacks. Docker Compose sets the env vars. The DB host inside Docker must be `postgres` (the Compose service name), not `localhost`.

### Frontend

Bootstrap 5.3 + Bootstrap Icons, loaded from CDN. Chart.js 4 for dashboard charts. Custom styles in `public/style.css`, custom JS in `public/script.js`. No build toolchain — edit CSS/JS directly.

### Security conventions

- All POST forms must include `<?= CSRF::getTokenField() ?>` and validate the token server-side.
- All output to HTML must use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Access control: call `Auth::requireRole()` at the top of every protected route handler.
- Passwords: `password_hash()` on register, `password_verify()` on login.
