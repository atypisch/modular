# Security Changelog – Modular CMS

Security-maatregelen en wijzigingen per release/sessie. Zie ook audit TODO-lijst in `SIMUZE_WP_TO_MODULAR_AUDIT.md`.

---

## 2025-02 (audit execution)

### CSRF protection (P1 – done)
- **core/functions/csrf.php:** `csrf_token()`, `csrf_field()`, `csrf_verify()`. Session-based token; ondersteuning voor POST en header `X-CSRF-TOKEN`.
- **admin/bootstrap.php:** `csrf.php` geladen.
- **admin/auth/login.php, register.php:** `csrf_field()` in form; form action via `ADMIN_URL`.
- **admin/auth/process_login.php, process_register.php:** Aan begin van POST: `csrf_verify()`; bij ongeldig token redirect met foutmelding.

### Rate limiting (P1 – done for auth)
- **admin/auth/process_login.php:** `RateLimiter::check('login_' . IP, 10, 900)` — 10 pogingen per 15 min per IP.
- **admin/auth/process_register.php:** `RateLimiter::check('register_' . IP, 5, 3600)` — 5 pogingen per uur per IP.
- **admin/auth/process_forgot_password.php:** `RateLimiter::check('forgot_password_' . IP, 5, 3600)` — 5 pogingen per uur per IP.
- API had al rate limiting (admin/api/index.php).

### Front-end templating (P1 – partial)
- **public/index.php:** Single-post template hierarchy: eerst `single-{post_type}.php`, dan `single.php` per theme. Gebruikt `$post['post_type']` uit PostTypeResolver.

### Upload base path (P1 – done)
- **admin/config/config.php:** `UPLOADS_PATH` (filesystem), `UPLOADS_WEB_PATH` (URL path) toegevoegd; afgeleid van `PUBLIC_PATH` en `BASE_PATH`.
- **core/classes/MediaController.php:** Gebruikt `UPLOADS_PATH` en `UPLOADS_WEB_PATH` voor directory en opgeslagen `file_path`/variant paths.
- **admin/media/upload-handler.php:** Idem; geen hardcoded `/modular/public/uploads/` meer.

### CSRF uitgebreid (P1 – done)
- **Posts:** save.php + edit form; **Users:** save.php + edit form; **Settings:** index, security, appearance (verify + csrf_field in forms).
- **Auth:** forgot_password (form + process), reset_password (form + process).
- **Taxonomies:** save.php + edit; **Terms:** save.php + terms/edit; **Post types:** save.php + edit; **Users/roles:** save.php + edit.
- **Media:** upload-handler.php + upload form; media/edit.php form.
- **Modules:** activate/deactivate POST + inline forms (csrf_verify + csrf_field).

### Security headers (P1 – done)
- **admin/bootstrap.php:** X-Content-Type-Options: nosniff; X-Frame-Options: SAMEORIGIN; X-XSS-Protection: 1; mode=block; Referrer-Policy: strict-origin-when-cross-origin; Content-Security-Policy (default-src 'self'; script/style/img/font; frame-ancestors 'self'). Verzonden vóór output bij elke admin-pagina.

### Helper
- **core/functions/admin.php:** `is_module_active($slug)` voor module-check (o.a. simuze-community module.php).

### Nog te doen (audit)
- Rate limiting op forgot_password en eventueel andere auth flows.
- File upload: MIME/extensie strikter, max size, directory traversal check.
- Logging van auth events en permission changes.
