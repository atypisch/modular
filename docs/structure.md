# Modular CMS — Projectstructuur & database

Overzicht van mappen, conventies en het **canonical database-schema** voor dit project.

Voor het volledige systeemplaatje: [system_overview.md](./system_overview.md).

---

## Overzicht

Modular is een custom PHP-CMS zonder WordPress-runtime. De structuur scheidt **admin**, **core** en **public** strikt. Composer is optioneel (dev-tools); productie draait op plain PHP + PDO.

```
modular/
├── .htaccess              # URL-rewrites naar index.php
├── index.php              # Root entry (install-check)
├── composer.json          # Dev: PHPUnit, PHPStan, PHPCS
├── CLAUDE.md              # Projectregels (Simuze)
├── admin/                 # CMS-backend
├── core/                  # Engine, fields, migrations, system modules
├── public/                # Frontend, themes, modules, uploads
└── docs/                  # Documentatie
```

---

## Mappenstructuur

### `/admin/` — CMS-backend

Beheert UI, configuratie en installatie.

```
admin/
├── assets/          # css/, js/, images/, vendor/
├── auth/            # login, register, password reset
├── config/          # config.php (lokaal, niet in git)
├── setup/           # step1.php, step2.php — installatiewizard
├── bootstrap.php    # Core-load, ModuleManager, security headers
├── templates/       # header.php, sidebar.php, footer.php
├── dashboard/
├── posts/           # CRUD, quick-save, edit
├── post-types/      # CPT + veldschema
├── media/
├── users/           # users + roles/
├── taxonomies/      # taxonomies + terms/
├── forms/
├── actions/         # automatisering
├── modules/         # modulebeheer
├── settings/
├── export-import/
├── api/
└── sql/             # legacy schema.sql (zie Database)
```

### `/core/` — Engine

```
core/
├── classes/         # Module, ModuleManager, Hooks, MediaController, …
├── fields/          # Veldtypes (TextField, RelationField, …)
├── functions/       # auth, database, hooks, http, field-save, simezu_auth, …
├── hooks/
├── api/
├── migrations/      # ★ full_schema_simuze.sql (canonical)
├── system/          # Systeemmodules (module.json + bootstrap.php)
│   ├── activity/
│   ├── media/
│   ├── notifications/
│   ├── search/
│   ├── seo/
│   └── users/
├── config/          # simuze.php, update.php
├── storage/         # cache, logs, backups
├── seeds/
└── validation/
```

### `/public/` — Frontend

```
public/
├── index.php        # Frontend router
├── themes/
│   ├── simuze/      # Actief Simuze-thema
│   └── default/
├── modules/         # Publieke modules
│   ├── simuze-community/
│   ├── example-module/
│   └── …
├── uploads/
│   ├── media/
│   ├── music/
│   ├── cache/
│   └── logs/
├── api/             # simezu-ajax.php, auth-proxy, …
└── tests/           # PHPUnit
```

---

## CMS-installatie

Als `admin/config/config.php` ontbreekt of kritieke tabellen ontbreken:

1. **Stap 1** — Databasegegevens → `config.php`
2. **Stap 2** — Admin-gebruiker aanmaken → dashboard

Voor Simuze: voer daarna (of in plaats van legacy SQL) **`core/migrations/full_schema_simuze.sql`** uit in phpMyAdmin.

---

## Database — schema-bestanden

| Bestand | Gebruik |
|---------|---------|
| **`core/migrations/full_schema_simuze.sql`** | **Canonical** — Modular core + Simuze + events/ticketing. Idempotent, phpMyAdmin-vriendelijk. |
| `core/migrations/full_schema.sql` | Alleen Modular core (geen `simuze_*`) |
| `admin/sql/schema.sql` | **Legacy** install-wizard: `fields`, `post_meta`, `options`; afwijkend van huidige core |
| `admin/sql/init_database.sql`, `update_schema.sql` | Oudere hulpbestanden; niet primair |

**Projectregel:** alle schemawijzigingen gaan in **`full_schema_simuze.sql`** (één bestand, `CREATE TABLE IF NOT EXISTS`, veilig herhaalbaar).

---

## Database — secties in `full_schema_simuze.sql`

### Sectie 1 — Modular core

| Tabel | Beschrijving |
|-------|----------------|
| `users` | Gebruikers; soft delete (`deleted_at`); 2FA-kolommen |
| `roles` | admin, editor, author, user |
| `capabilities` | Granulaire rechten (`post:edit:any`, …) |
| `role_capabilities` | Koppeling rol ↔ capability |
| `user_roles` | Koppeling user ↔ rol |
| `post_types` | CPT; `schema` JSON; `mode` (fixed/flexible); `settings` JSON |
| `posts` | Content; `component_data`, `meta_data`, `data`, `layout` JSON |
| `component_definitions` | Veld/component-definities per post type |
| `media` | Bestanden, varianten, metadata |
| `relationships` | Relaties tussen posts/entiteiten |
| `taxonomies` | Taxonomie-definities |
| `terms` | Termen per taxonomie |
| `post_terms` | Post ↔ term |
| `post_type_taxonomies` | Post type ↔ taxonomie |
| `forms` | Formulierdefinities (JSON fields) |
| `form_submissions` | Inzendingen + spam-score |
| `actions` | Triggers/automatisering |
| `action_queue` | Achtergrondjobs |
| `settings` | Site-opties (`option_name` / `option_value`) |
| `post_revisions` | Revisiehistorie |
| `activity_log` | Audit (user, model, old/new JSON) |
| `schema_migrations` | Uitgevoerde migratieversies |
| `modules` | Geïnstalleerde modules (`module_type`, `active`, `settings`) |

### Sectie 2 — Standaard rollen & capabilities

`INSERT IGNORE` voor rollen, capabilities en admin → alle capabilities.

### Sectie 3 — Post types (seed)

| slug | Naam |
|------|------|
| `post` | Post |
| `page` | Page |
| `album` | Album |
| `track` | Track |
| `playlist` | Playlist |
| `event` | Event |

### Sectie 4 — Genre-taxonomie

- Taxonomie `genre` gekoppeld aan post type `track`.

### Sectie 5 — Schema-updates track/playlist

- `track`: relationship-veld `album` in `post_types.schema`
- `playlist`: relationship-veld `tracks`

### Sectie 6 — Simuze Community (kern)

| Tabel | Beschrijving |
|-------|----------------|
| `simuze_groups` | Artist/community-groepen |
| `simuze_group_members` | Lidmaatschap + rol |
| `simuze_group_types` | Groeptype-definities |
| `simuze_conversations` | DM/groepsgesprekken |
| `simuze_conversation_participants` | Deelnemers + last_read |
| `simuze_messages` | Berichten |
| `simuze_reviews` | Content reviews (moderatie) |
| `simuze_reports` | Meldingen |
| `simuze_activity` | Activity feed |

### Sectie 7 — Events / shows / ticketing

| Tabel | Beschrijving |
|-------|----------------|
| `simuze_cities` | Steden (geo) |
| `simuze_venues` | Locaties |
| `simuze_events` | Shows/events (ticketvelden, status, genres JSON) |
| `simuze_event_slots` | Lineup-slots |
| `simuze_show_slot_pool` | Beschikbaarheid bands per regio |
| `simuze_event_slot_invitations` | Uitnodigingen voor slots |
| `simuze_event_orders` | Ticketorders |
| `simuze_event_tickets` | Tickets + QR |
| `simuze_activity_posts` | Community “zoek ik…”-posts |
| `simuze_event_attendance` | “Ik ga” |
| `simuze_event_comments` | Reacties op events |
| `simuze_event_guestlist` | Gastenlijst organisator |
| `simuze_show_invites` | Show-uitnodigingen |

### Sectie 8 — Idempotente kolommen

Stored procedure `simuze_add_columns_safe` voegt ontbrekende kolommen toe op bestaande installs (`ticket_capacity`, `mood`, `header_image_url`, `scope` op activity_posts, `buyer_name` op orders), daarna verwijderd.

---

## Legacy: `admin/sql/schema.sql`

Oudere installatie gebruikt o.a.:

| Tabel | Opmerking |
|-------|-----------|
| `fields` | Losse velddefinities (vervangen door `post_types.schema` in nieuwe core) |
| `field_groups` | Veldgroepen |
| `post_meta` | Waarden gekoppeld aan `fields.id` |
| `options` | i.p.v. `settings` |
| `user_sessions` | Sessietokens |
| `import_log` | Import-audit |

**Nieuwe omgevingen:** gebruik `full_schema_simuze.sql`, niet `admin/sql/schema.sql`.

---

## Auth

| Laag | Implementatie |
|------|----------------|
| Admin CMS | `core/functions/auth.php`, sessies |
| Simuze site | JWT (`core/functions/simezu_auth.php`), cookies `accessToken` / `refreshToken` |
| Config | `core/config/simuze.php` |

---

## Routing

- **Publiek:** schone URLs via `.htaccess` → `index.php` → `public/index.php`; slug-resolutie op `posts.slug` / post type
- **Admin:** directe paden (`admin/posts/index.php`, …)

---

## Thema & CSS

- Frontend-CSS in `public/themes/{theme}/css/`
- Laden via thema-`header.php` / `md_asset_url`
- Actief thema: DB `settings.option_name = 'active_theme'` (Simuze: `simuze`)

---

## Uitbreidbaarheid

- Publieke modules: `public/modules/[naam]/`
- Systeemmodules: `core/system/[naam]/`
- Hooks: `core/functions/hooks.php`
- Geen core-wijzigingen nodig voor standaard uitbreidingen

---

## Gerelateerde documenten

| Document | Inhoud |
|----------|--------|
| [system_overview.md](./system_overview.md) | Compleet systeembeeld |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Technische architectuur |
| [MODULES_SYSTEM.md](./MODULES_SYSTEM.md) | Modules bouwen & beheren |
