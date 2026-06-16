# Modular CMS — Architectuur

Technische architectuur van Modular in deze repository (PHP CMS + Simuze-community).

---

## Systeemoverzicht

Modular is een **component- en module-gebaseerd CMS** gebouwd met **PHP 8.1+**, **MySQL 8+** en **vanilla JavaScript** in de admin. De frontend rendert PHP-templates uit thema’s; modules kunnen routes, hooks en eigen templates toevoegen.

Voor een volledig overzicht (taal, auth, paden, stack): zie [system_overview.md](./system_overview.md).

---

## Laagmodel

```
┌─────────────────────────────────────────────────────────┐
│  Presentatie: admin/*.php, public/themes/, module tpl   │
├─────────────────────────────────────────────────────────┤
│  Uitbreiding: public/modules/, core/system/             │
├─────────────────────────────────────────────────────────┤
│  Core: core/functions/, core/fields/, core/classes/     │
├─────────────────────────────────────────────────────────┤
│  Data: MySQL (PDO), full_schema_simuze.sql             │
└─────────────────────────────────────────────────────────┘
```

---

## Directory-structuur

```
modular/
├── index.php                 # Entry: install-check → public/index.php
├── admin/                    # CMS-backend
│   ├── api/                  # Admin API entry
│   ├── assets/               # CSS, JS, vendor (o.a. TinyMCE)
│   ├── auth/                 # Login, register, password reset
│   ├── config/               # config.php (DB, BASE_PATH, SITE_URL)
│   ├── setup/                # Installatiewizard (stap 1–2)
│   ├── bootstrap.php         # Core bootstrap + ModuleManager + init hook
│   ├── dashboard/, posts/, post-types/, media/, users/, settings/, …
│   ├── modules/              # Modulebeheer UI
│   ├── sql/                  # Legacy schema (install-wizard); zie structure.md
│   └── templates/            # Admin layout (header, sidebar, footer)
├── core/
│   ├── classes/              # Module, ModuleManager, Hooks, MediaController, …
│   ├── fields/               # Veldtypes (TextField, RelationField, …)
│   ├── functions/            # auth, database (md_db_*), hooks, http, simezu_auth, …
│   ├── hooks/                # Hook-infrastructuur
│   ├── api/                  # Core API-onderdelen
│   ├── migrations/           # full_schema.sql, full_schema_simuze.sql (canonical)
│   ├── system/               # Systeemmodules (altijd actief)
│   │   ├── activity/
│   │   ├── media/
│   │   ├── notifications/
│   │   ├── search/
│   │   ├── seo/
│   │   └── users/
│   ├── config/               # o.a. simuze.php, update.php
│   └── storage/              # cache, logs, backups
├── public/
│   ├── index.php             # Frontend router + thema-load
│   ├── themes/               # simuze, default, …
│   ├── modules/              # Publieke modules (simuze-community, …)
│   ├── uploads/              # media, music, cache, logs
│   ├── api/                  # simezu-ajax.php, simezu-auth-proxy, …
│   └── tests/                # PHPUnit
└── docs/                     # Deze documentatie
```

Er is **geen** top-level `/modules`-map meer als primaire plugin-locatie; publieke modules staan onder **`public/modules/`**.

---

## Bootstrap & initialisatie

1. **`index.php`** (root): laadt config, controleert kritieke tabellen (`users`, `post_types`), stuurt door naar `public/index.php` of installatielanding.
2. **`public/index.php`**: sessie, paden, `admin/bootstrap.php`, optioneel Simuze-community includes, thema uit `settings.active_theme`, routing.
3. **`admin/bootstrap.php`**:
   - Core functions (auth, csrf, cache, admin, hooks, common, template-helpers)
   - `simezu_auth.php` (JWT → globals)
   - `ModuleManager`: systeemmodules → discover public modules → laad actieve modules → `do_action('init')`

---

## Database

**Bron van waarheid (Simuze-project):** `core/migrations/full_schema_simuze.sql`

- Idempotent (`CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE`, veilige kolom-procedures)
- Secties: core → rollen/capabilities → post types → genre-taxonomie → Simuze-tabellen → events/ticketing → idempotente ALTERs

**Alleen core (zonder Simuze):** `core/migrations/full_schema.sql`

**Legacy:** `admin/sql/schema.sql` — ouder veldenmodel (`fields`, `post_meta`, `options` i.p.v. `settings`); gebruik voor nieuwe omgevingen het migrationsbestand hierboven.

Volledige tabellijst: [structure.md](./structure.md).

### Belangrijkste core-tabellen

| Tabel | Doel |
|-------|------|
| `users` | Accounts, soft delete |
| `roles`, `capabilities`, `role_capabilities`, `user_roles` | RBAC |
| `post_types` | CPT-definitie incl. `schema` JSON, `mode` (fixed/flexible) |
| `posts` | Content; JSON: `component_data`, `meta_data`, `data`, `layout` |
| `component_definitions` | Component/veld-definities per post type |
| `media` | Bestanden + varianten/metadata |
| `relationships` | Relaties tussen entiteiten |
| `taxonomies`, `terms`, `post_terms`, `post_type_taxonomies` | Taxonomieën |
| `forms`, `form_submissions` | Formulieren |
| `actions`, `action_queue` | Automatisering / achtergrondtaken |
| `settings` | Key-value opties (o.a. `active_theme`) |
| `post_revisions` | Revisies |
| `activity_log` | Audit trail |
| `modules` | Geïnstalleerde modules (`module_type`: system \| module) |
| `schema_migrations` | Uitgevoerde migratieversies |

---

## Veld- & contentmodel

- **Post type schema:** JSON in `post_types.schema` — velden zoals `text`, `relationship`, `repeater`, … (zie `core/fields/`).
- **Opslag op post:** veldwaarden in `posts.data` / `meta_data` / `component_data` afhankelijk van editor en sync (o.a. `atypisch-sync-visual-schema.php`).
- **Fixed vs flexible:** `post_types.mode` — fixed = schema-gestuurd; flexible = layout/composer (waar ingeschakeld).

Veldsave-logica: o.a. `core/functions/field-save.php`.

---

## Module-architectuur

Zie [MODULES_SYSTEM.md](./MODULES_SYSTEM.md).

| Type | Locatie | Manifest | Admin UI |
|------|---------|----------|----------|
| Systeem | `core/system/{slug}/` | `module.json` | Nee (altijd aan) |
| Publiek | `public/modules/{slug}/` | `module.php` header | Ja |

`Modular\ModuleManager` laadt systeemmodules via `bootstrap.php`, publieke modules via `module.php` alleen als actief in DB.

---

## Routing (publiek)

`public/index.php` bepaalt:

- **Home:** geen pad of `/`
- **Archive:** één segment = post type slug → `archive-{type}.php` of `archive.php`
- **Single:** slug(s) + optioneel `?type=&slug=` of PATH_INFO

Admin gebruikt directe PHP-paden en queryparameters (geen centrale front controller voor alle admin-pagina’s).

Systeemmodules kunnen eigen routes registreren via `routes.php` in `bootstrap.php` (bijv. search, activity).

---

## API

- **Admin API:** `admin/api/index.php` (interne endpoints)
- **Publiek / Simuze:** `public/api/simezu-ajax.php`, `public/api/simezu-auth-proxy.php`
- REST-achtige paden uit oudere docs (`/api/v1/`) zijn niet de primaire router in de huidige tree; module-specifieke AJAX gebruikt het hook-dispatchpatroon.

---

## Authenticatie & security

| Context | Mechanisme |
|---------|------------|
| Admin CMS | Sessie + `core/functions/auth.php` |
| Simuze frontend | JWT cookies, refresh via Simezu API, `simezu_auth.php` |
| CSRF | `md_csrf_*` in `core/functions/csrf.php` |
| Output | `md_esc_html`, `md_esc_attr`, `md_esc_url`, `md_sanitize_text` |
| HTTP headers | Security headers in `admin/bootstrap.php` (CSP, X-Frame-Options, …) |
| Database | Prepared statements via PDO / `md_db_*` |

Privacyregel: e-mailadressen niet in publieke output (zie `.cursor/rules/privacy-no-emails.mdc`).

---

## Performance & achtergrondwerk

- Indexen op veelgebruikte queries in schema
- `action_queue` voor geplande acties
- `core/functions/cache.php` — cachinghelpers (waar gebruikt)
- Media-varianten in `media.variants` (JSON)

Geplande/gedeeltelijke features uit oudere docs (globale cache-laag, rate limiting) zijn niet overal geïmplementeerd; verifieer in code vóór je ervan uitgaat.

---

## Simuze-specifieke architectuur

- **Thema:** `public/themes/simuze/`
- **Module:** `public/modules/simuze-community/`
- **Schema:** `simuze_*`-tabellen in `full_schema_simuze.sql` (groups, events, tickets, messages, …)
- **Muziek-CPT’s:** `album`, `track`, `playlist`, `event` als post types + genre-taxonomie

---

## Gerelateerde documenten

- [system_overview.md](./system_overview.md)
- [structure.md](./structure.md)
- [MODULES_SYSTEM.md](./MODULES_SYSTEM.md)
