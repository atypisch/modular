# Modular CMS — Modulesysteem

Het modulesysteem breidt Modular uit zonder core-bestanden te wijzigen. Het volgt een **WordPress-achtig** patroon (hooks, `module.php`-header), maar modules draaien in **`/public/modules/`** en **systeemmodules** in **`/core/system/`**.

---

## Twee soorten modules

| | Systeemmodule | Publieke module |
|---|---------------|-----------------|
| **Pad** | `core/system/{slug}/` | `public/modules/{slug}/` |
| **Manifest** | `module.json` | `module.php` (comment-header) |
| **Bootstrap** | `bootstrap.php` (verplicht voor laden) | `module.php` (hoofdlogica) |
| **Actief** | Altijd | Alleen als `modules.active = 1` |
| **Admin UI** | Niet zichtbaar in modulelijst | `/admin/modules/` |
| **DB `module_type`** | `system` | `module` |

Laadvolgorde (`ModuleManager`):

1. Alle systeemmodules (`loadSystemModules` → `bootstrap.php`)
2. Discover publieke modules (`module.php` header parsen)
3. Laad actieve publieke modules uit database

---

## Systeemmodules (ingebouwd)

Huidige modules onder `core/system/`:

| Slug | Doel (indicatief) |
|------|-------------------|
| `activity` | Activity-log integratie |
| `media` | Media-gerelateerde hooks/routes |
| `notifications` | Notificaties |
| `search` | Zoekfunctionaliteit |
| `seo` | SEO-metadata/routes |
| `users` | Gebruikersbeheer-integratie |

Voorbeeld `module.json`:

```json
{
    "name": "Users",
    "slug": "users",
    "type": "system",
    "version": "1.0.0",
    "description": "Core user management: authentication, roles, and profile handling.",
    "autoload": true,
    "author": "Modular CMS"
}
```

Na bootstrap wordt o.a. `do_action('system_module_loaded', 'users')` uitgevoerd.

---

## Publieke module-structuur

```
public/modules/
└── mijn-module/
    ├── module.php          # Verplicht — header + code
    ├── includes/           # Optioneel — PHP-klassen
    ├── templates/          # Optioneel — frontend-templates
    ├── assets/             # Optioneel — css/, js/, images/
    └── migrations/         # Optioneel — alleen als niet in full_schema
```

**Let op:** Voor Simuze hoort schema in **`core/migrations/full_schema_simuze.sql`**, niet in losse migratiebestanden (projectregel).

---

## module.php-header

```php
<?php
/**
 * Module Name: Mijn Module
 * Module URI: https://example.com
 * Version: 1.0.0
 * Description: Korte beschrijving
 * Author: Naam
 * Author URI: https://example.com
 * Text Domain: mijn-module
 * Requires PHP: 8.1
 * Requires Modular: 1.0
 */

if (!defined('MODULAR_PATH')) {
    return;
}

// Modulecode…
```

`Modular\Module::loadFromFile()` leest deze header en synchroniseert metadata naar de `modules`-tabel.

---

## Hooks gebruiken

```php
add_action('init', function () {
    // Na bootstrap, modules geladen
});

add_action('module_activate', function ($slug) {
    if ($slug === 'mijn-module') {
        // Eenmalig bij activatie
    }
});

add_filter('admin_menu_items', function ($items) {
    $items[] = ['label' => 'Mijn module', 'url' => '…'];
    return $items;
});
```

### Veelgebruikte actions

| Hook | Wanneer |
|------|---------|
| `init` | Na `ModuleManager` + actieve modules |
| `system_modules_loaded` | Alle systeemmodules gebootstrap |
| `system_module_loaded` | Per systeemmodule (`$slug`) |
| `module_activate` / `module_deactivate` | Activatie in admin |
| `admin_init` | Admin-omgeving |
| `admin_menu` | Admin-menu opbouw |
| `wp_ajax_{action}` | AJAX via `public/api/simezu-ajax.php` |
| `wp_ajax_nopriv_{action}` | AJAX zonder login |

### Veelgebruikte filters

| Hook | Doel |
|------|------|
| `the_title` | Titel aanpassen |
| `the_content` | Content aanpassen |
| `admin_menu_items` | Admin-menu-items |

Alternatief met `md_*`-prefix: `md_hook_action`, `md_filter_apply` in `core/functions/hooks.php`.

---

## ModuleManager (PHP API)

```php
$moduleManager = $GLOBALS['module_manager']
    ?? new \Modular\ModuleManager($GLOBALS['db']);

$moduleManager->getModules();        // Alle publieke modules
$moduleManager->getSystemModules();  // Systeemmodules
$moduleManager->getActiveModules();  // Actieve publieke modules
$moduleManager->activateModule('mijn-module');
$moduleManager->deactivateModule('mijn-module');
$moduleManager->isModuleActive('mijn-module');
```

Instantie wordt gezet in `admin/bootstrap.php` als de `modules`-tabel bestaat.

---

## Database: `modules`-tabel

| Kolom | Beschrijving |
|-------|----------------|
| `name` | Weergavenaam |
| `slug` | Unieke mapnaam |
| `version` | Versie uit header/json |
| `description`, `author`, `author_url`, `module_url` | Metadata |
| `text_domain` | Vertaaldomain |
| `requires_php`, `requires_modular` | Vereisten |
| `active` | 0/1 (publiek); systeem altijd 1 |
| `module_type` | `system` of `module` |
| `settings` | JSON-instellingen |

---

## Admin-interface

- URL: **`/admin/modules/`** (relatief aan je `BASE_PATH`)
- Activeren/deactiveren werkt op publieke modules
- Voorbeeldmodule: `public/modules/example-module/`

**Installatie:** Zorg dat `modules`-tabel bestaat (onderdeel van `full_schema_simuze.sql` of setup). Zonder tabel slaat bootstrap het laden van modules over (geen fatale error).

---

## Referentie: simuze-community

De productie-module voor Simuze:

- Pad: `public/modules/simuze-community/`
- Entry: `module.php` laadt `includes/` (auth, groups, events, commerce, …)
- Constanten: `SIMUZE_COMMUNITY_PATH`, `SIMUZE_DB_PREFIX` (`simuze_`), `SIMUZE_MODULAR_RUN`
- AJAX: registreert `wp_ajax_*`-handlers; dispatch via `public/api/simezu-ajax.php`

`simuze-community-wp` is een aparte WordPress-port en niet de Modular-runtime.

---

## Best practices

1. **Geen core-patches** — gebruik hooks en eigen modulemap.
2. **Namespaces** — voorkom collisions in grotere modules.
3. **Guard** — `if (!defined('MODULAR_PATH')) return;` bovenaan `module.php`.
4. **Schema** — wijzigingen in `core/migrations/full_schema_simuze.sql` (idempotent).
5. **Helpers** — `md_db_*`, `md_home_url`, `md_esc_*` i.p.v. WordPress-functies in nieuwe code.
6. **Privacy** — geen e-mail in publieke templates/API-responses.

---

## Troubleshooting

| Probleem | Controle |
|----------|----------|
| Module niet in lijst | Map onder `public/modules/`, `module.php` + geldige header |
| Module laadt niet | `modules.active = 1` en `module_type = 'module'` |
| Hooks werken niet | Module actief? Hook vóór `do_action` geregistreerd? |
| Fout bij activatie | PHP error log; syntax in `module.php` |
| Systeemmodule | Geen activatie nodig; check `bootstrap.php` en `module.json` |

---

## Zie ook

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [structure.md](./structure.md) — schema & mappen
- [system_overview.md](./system_overview.md)
