# Simuze WordPress → Modular CMS – Audit & migratieplan

**Rol:** Senior full-stack architect & QA lead  
**Doel:** Simuze volledig evalueren tegen Modular CMS en een concreet plan opstellen voor feature-pariteit en vlekkeloze migratie WP → Modular.

---

## 1. Inventarisatie

### 1.1 Simuze theme (`wp-content/themes/simuze`)

**Locatie:** `/Users/martentiman/Sites/simuze/wp-content/themes/simuze` (of `localhost/simuze` document root).

| Onderdeel | Details |
|-----------|---------|
| **Post types (theme)** | `album`, `track` (functions.php: `simuze_register_post_types`); `playlist` (includes/playlist-integration.php); `event` ondersteund via general-functions (REST, custom-fields, meta). |
| **Taxonomieën (theme)** | `genre` voor `track` (includes/upload-functions.php: `simuze_register_taxonomies`), hierarchical, show_in_rest. |
| **Page templates** | Template-by-slug: `template_music.php`, `template_network.php`, `template_live.php`, `template_weblog.php`; sub-routes `music/albums` → `template_albums.php`, `music/tracks` → `template_tracks.php`. Verder o.a. `template_homepage.php`, `template_about.php`, `template_city.php`, `template_country.php`, `template_province.php`, `template_dashboard.php`, `template_login.php`, `template_signup.php`, `template_forgot_password.php`, `template_profile.php`, `template_howto.php`, `template_singles.php`, `template_store.php`, `template_subscription.php`. |
| **Single templates** | `single-album.php`, `single-track.php`, `single-playlist.php`; `single.php` (fallback). |
| **Rewrite rules (theme)** | Query vars: `simuze_unique_handle`, `simuze_group_tab`, `album_slug`, `track_slug`, `simuze_country`, `simuze_province`, `simuze_city`. Rules: `country/([^/]+)`, `province/([^/]+)`, `city/([^/]+)`; group-types uit Simuze Community DB. |
| **Includes** | `general-functions.php` (event meta, post type args), `group-functions.php`, `upload-functions.php` (music uploads, AJAX, DB tables), `music-functions.php`, `player-functions.php`, `playlist-integration.php`, `review-system.php`. |
| **Template parts** | `template-parts/album-grid.php`, `view-album.php`, `view-track.php`, `album-edit.php`, `track-edit-form.php`, `edit-music-form.php`. |
| **Assets** | CSS: layout, menu, forms, music, playlist-player, hero, calendar, network, responsive, carousel, album-edit, effects, components, fonts, subnav, profile-fullwidth. JS: jquery, functions, simuze-player, simuze-modal, simuze-avatar-dropdown, simuze-download, music-upload, edit-music-form, discography, audio-player, playlist-player, music-search, complex-cropper, fix-group-urls, slick. |
| **AJAX (theme)** | Upload/metadata: `simuze_upload_music`, `simuze_upload_single_file`, `simuze_save_metadata`, `simuze_delete_files`, `simuze_publish_music`, `simuze_delete_music`, `simuze_update_status`, `simuze_update_album`, `simuze_change_audio_file`, `simuze_ajax_create_album`, `simuze_ajax_update_album`, `simuze_ajax_cancel_upload`, `simuze_delete_track`, `simuze_delete_album`, `simuze_delete_album_frontend`, `simuze_update_track_order`, `simuze_cancel_upload`; discography tabs: `simuze_refresh_overview_tab`, `simuze_refresh_in_progress_tab`, `simuze_refresh_scheduled_tab`, `simuze_refresh_published_tab`, `simuze_get_tab_counts`, `simuze_update_album_status`; genre: `simuze_search_genres`, `simuze_get_all_genres`, `simuze_create_genre`; overig: `simuze_diagnostic`, `simuze_fix_discography_database`, `simuze_cleanup_temp_folders`, en andere. |
| **Overig** | Demo weblog posts seed; template loading op pagina-slug (profile/user/group aan plugin overgelaten); custom upload limits; music upload tables (o.a. wp_music_uploads, wp_music_albums). |

### 1.2 Simuze Community plugin (`wp-content/plugins/simuze-community`)

**Migratiedoel:** De Simuze Community plugin wordt een **Modular Module** — dezelfde soort uitbreiding als een WP-plugin: optioneel te activeren/deactiveren via Admin → Modules, eigen `module.php`, migrations, hooks en (indien nodig) eigen tabellen. De module krijgt bijvoorbeeld de slug `simuze-community` en komt in `modules/simuze-community/`.

| Onderdeel | Details |
|-----------|---------|
| **Doel** | Community: users, groups, activity, messages, reviews, reports; commerce (orders, downloads, seller accounts, payouts, subscriptions). |
| **Database-tabellen** | `simuze_users`, `simuze_profiles`, `simuze_groups`, `simuze_group_types`, `simuze_group_members`, `simuze_group_invitations`, `simuze_group_activity`, `simuze_user_activity`, `simuze_messages`, `simuze_notifications`, `simuze_reviews`, `simuze_reports`, `simuze_follows`, `simuze_friends`, `simuze_discounts`, `simuze_orders`, `simuze_order_items`, `simuze_download_tokens`, `simuze_seller_accounts`, `simuze_transactions`, `simuze_payouts`, `simuze_subscriptions`, `simuze_plays_*`, `simuze_artist_payouts`, `simuze_label_artists`, `simuze_webhooks`, avatar/group caches, enz. |
| **Shortcodes** | `simuze_profile`, `simuze_group`, `simuze_messages`, `simuze_discography`, `simuze_activity`, `simuze_friends`, `simuze_groups` (includes/class-shortcodes.php). In Modular: equivalent via components, routes of block-types geregistreerd door de module. |
| **Classes** | Database, Auth, User, Group, Activity, Message, Friend, Follow, Notification, Security, Privacy, Search, Review, Report; commerce: Song, Album, Discount, Order, DownloadToken, Cart, SimezuPayments, DownloadHandler, SellerAccount, AudioConverter, PurchaseFlow, DownloadFlow, Payout; REST API, Templates. |
| **Auth** | Externe auth-API optie (`simuze_auth_api_url`). |
| **Routing** | Profile/user/group URLs door plugin/shortcodes; theme laat deze segmenten aan de plugin.

### 1.3 Modular-codebase (gescand)

| Onderdeel | Status | Locatie / opmerkingen |
|-----------|--------|------------------------|
| **Post types** | ✅ | `post_types` tabel, `core/classes/PostType.php`, `PostTypesController`, admin/post-types/*. Fixed + modular (flexible) modes, feature-gated. |
| **Custom fields** | ✅ | `fields` tabel, `core/fields/*` (FieldRegistry, ComponentTypes), component_definitions. Veel veldtypen (text, image, repeater, flexible, relation, taxonomy, etc.). |
| **Taxonomieën** | ✅ | `taxonomies`, `terms` tabellen, `core/classes/Taxonomy.php`, admin/taxonomies/*. |
| **Users & auth** | ✅ | `users`, sessions via PHP, login/register/forgot in admin/auth/*. Geen JWT in public API in alle flows. |
| **Roles & capabilities** | ✅ | `roles`, `capabilities`, `role_capabilities`, `user_roles`. Admin UI: users/roles.php, roles/save.php, roles/edit.php. |
| **Media & uploads** | ✅ | `media` tabel, `MediaController`, admin/media/*, upload-handler. Image variants (thumb/small/medium/large). Beperkte MIME-whitelist (images + PDF). |
| **Vue / frontend admin** | Gedeeltelijk | Vue 3 + PrimeVue in header; field renderer (primevue-fields.js, resolveFieldComponent.js). Field-builder nog PHP+SortableJS; migratie naar Vue+PrimeVue gepland (field-builder/README). |
| **Templating (public)** | ✅ | public/themes/default, public/themes/MDLR-2025 (index, single, 404, modules voor components). |
| **Forms** | ✅ | `forms`, `form_submissions`, FormsController, admin/forms/*. |
| **Actions / automations** | ✅ | `actions`, `action_queue`, ActionManager, admin/actions/*. Feature-gated (Pro). |
| **User groups** | ❌ | Geen `groups` of group-scoped content in schema of core. |
| **Messaging (user-to-user)** | ❌ | Geen messaging-tabellen of -flows in core. |
| **CSRF** | ❌ | Doc: "to be implemented". |
| **Rate limiting** | ❌ | Doc: "to be implemented". RateLimiter class aanwezig maar niet overal toegepast. |
| **Caching** | ❌ | Doc: "to be implemented". |
| **Modules (uitbreidingen)** | ✅ | `/modules`, PluginManager/ModuleManager, `module.php` per module, activate/deactivate via admin/modules, migrations in `module/migrations/`, hooks (add_action/add_filter). Zelfde concept als WP-plugins. |

### 1.4 Front-end templating in Modular

**Vraag:** Is het huidige templating-systeem voldoende om Simuze-thema’s (page templates, single-{type}, template-by-slug) binnen Modular om te zetten? Kunnen we nu al templates maken?

**Huidige stand (bijgewerkt na Simuze-integratie):**

| Onderdeel | Status | Toelichting |
|-----------|--------|-------------|
| **Thema’s** | ✅ | `public/themes/` (default, MDLR-2025, simuze). Actief thema in settings (`active_theme`), Admin → Appearance. |
| **Theme class** | ✅ | `core/classes/Theme.php`: `render($template, $data)`, `renderPost($post)` (kiest `single` of `single-{post_type_slug}`), `renderArchive($postType, $posts)`, `asset()`, `getSetting()` (theme.json). |
| **Public entry** | ✅ | `public/index.php`: single post via PATH_INFO `/{type}/{slug}`; fixed routes; path-by-slug; archive segment. |
| **Template-keuze single** | ✅ | Entry laadt `single-{post_type_slug}.php` of `single.php` per theme (MDLR-2025, simuze, active). |
| **Block/component rendering** | ✅ | `render_blocks($blocks, $theme_path)` in `core/functions/blocks.php`; theme `modules/{type}.php` per block. |
| **Beschikbare templates** | ✅ | index.php, single.php, single-{type}.php, archive.php, archive-{type}.php, template_*.php. |
| **Routing op pad/slug** | ✅ | Fixed routes: music, network, live, weblog, dashboard, login, signup, profile, about, howto, singles, store, subscription; location taxonomies: country, province, city; sub-routes music/albums, music/tracks. |
| **Template-by-slug (pagina → template)** | ✅ | Eén path-segment → fixed route template (bijv. /music → template_music.php); path-by-slug voor posts. |
| **Archive / lijst-pagina’s** | ✅ | Eén segment als post-type slug laadt archive-{type}.php of archive.php. |

**Conclusie:** Routing en template-selectie zijn in public/index.php aangesloten. Simuze-thema kan in Modular draaien met theme `public/themes/simuze` en bridge (inc/modular-bridge.php).

#### Simuze theme in Modular (full port)

- **Pad:** `public/themes/simuze/` (Modular-port); referentie-WP-thema: `public/themes/simuze-wp/`.
- **Geen WordPress-code in theme:** Alle templates gebruiken alleen Modular-API en de bridge (`inc/modular-bridge.php`). Theme-specifieke logica staat in `inc/theme-functions.php` (Modular-only: `simuze_get_tracks_for_discovery`, `simuze_get_albums_for_discovery`, `simuze_get_track_data`, `simuze_get_album_data`, `simuze_get_album_tracks`, `simuze_get_playlist_track_ids`, `simuze_get_artists_for_search`, en render-helpers). Header/footer/single-album/single-track/single-playlist/template_music zijn omgezet naar Modular; geen `$wpdb`, geen `get_field`, geen Simuze_Group/Simuze_Auth in templates.
- **Tabellen:** Theme-level music: `md_music_*`; artists: `simuze_groups` (group_type = 'artist').
- **Uploadpad:** `/public/uploads` (UPLOADS_PATH); geen mappen in projectroot.
- **Veldnamen:** lowercase_underscores; labels human-readable (field editor).
- **Auth/payments (optioneel):** Placeholders `SIMEZU_AUTH_*`, `SIMEZU_PAYMENTS_*` (env/config); stub classes in core; geen logica tot .env is geconfigureerd. Zie core/config/simuze.php.

---

## 2. Feature-matrix: Simuze (theme + plugin) vs Modular

Bron: **Simuze theme** (§1.1) + **Simuze Community plugin** (§1.2).

| Feature | Simuze theme | Simuze plugin | Modular | Opmerking |
|---------|----------------|----------------|---------|-----------|
| Paginatypes (content, blog, artist, city, homepage) | ✅ | ✅ | Via custom post types + templates; homepage = front page / type “page”. |
| Custom fields per type | ✅ | ✅ | Fixed + flexible (modular) modes. |
| Taxonomieën (cats, tags, custom) | ✅ | ✅ | Taxonomies + terms + koppeling aan post types. |
| Media library + uploads | ✅ | ✅ | Beperkte types (images + PDF); uitbreiding mogelijk. |
| Login / register / wachtwoord vergeten | ✅ | ✅ | admin/auth/*. |
| Roles & capabilities | ✅ | ✅ | RBAC in DB + admin UI. |
| User groups / group-scoped content | — | ✅ | Via **Module** simuze-community (niet in core). |
| Messaging (berichten tussen users) | — | ✅ | Via **Module** simuze-community. |
| Shortcodes (profile, group, …) | — | ✅ | Via **Module** simuze-community (components/routes). |
| Plugins / modules | WP plugins | — | ✅ Modular **Modules** = zelfde rol als WP-plugins; Simuze Community wordt Module. |
| REST API | ✅ | ✅ | /api/v1/*. |
| Revisies / audit | ✅ | ✅ | post_revisions, activity_log; feature-gated. |
| Front-end templating (hierarchy, routing) | ✅ Template-by-slug, single-{type} | — | ✅ | §1.4: fixed routes, single-{type}, template-by-slug, location taxonomies, music/albums|tracks; Simuze theme in public/themes/simuze. |
| Responsive admin | ✅ | ✅ | responsive.css, layout. |
| Toegankelijkheid | ? | ? | Te auditen | P1 a11y. |

*Waar één kolom voor Simuze staat: eerste = theme, tweede = plugin; "—" = niet van toepassing. Zie §1.1 (theme) en §1.2 (plugin) voor details.*

---

## 3. Functionele vergelijking en prioriteit

- **Paginatypes (content, blog, artist, city, homepage):** Simuze **theme** levert page templates (music, network, live, weblog, city, country, province, dashboard, login, signup, …) en single-album/track/playlist; **plugin** levert profile/group/messages. Modular: post types + thema-templates; **P0** als Simuze-types nog niet bestaan → aanmaken en mappen. **Front-end templating:** zie §1.4 — templates kunnen nu al in een theme worden toegevoegd, maar routing en template hierarchy (single-{type}, template-by-slug) moeten nog in de public entry worden aangesloten (**P1**).
- **Media & uploads:** Voldoende voor basis; eventueel **P1**: MIME-uitbreiding, quota, betere variant-config.
- **User flows (login, groups, messaging):** Login ✅ in core. **P0:** Groups en messaging worden geleverd door de **Modular Module** `simuze-community` (niet in core;zelfde uitbreidingsvorm als de WP-plugin).
- **Permissions & roles:** Aanwezig; **P1:** controleren of capability-set overeenkomt met WP (per post type, per actie).
- **Shortcodes:** In Modular geen shortcodes; de **Module simuze-community** registreert equivalenten (components, routes of block-types) voor profile, group, messages, discography, activity, friends, groups.

---

## 4. UX & frontend audit (kort)

- Layout/admin: sidebar + toolbar, responsive; consistent met één thema.
- Componenten: Vue 3 + PrimeVue voor field renderer; field-builder nog PHP → **cosmetic/UX-debt** wanneer migratie naar Vue.
- Public theme: MDLR-2025 met modules (text, image, repeater, flexible, etc.); vergelijken met WP-versie voor **blocker/cosmetic** (ontbrekende blokken, afwijkend gedrag).
- Toegankelijkheid: niet in deze stap getest; aanbevolen **P1** audit (focus, ARIA, contrast).

---

## 5. Technische kwaliteit & stabiliteit (kort)

- **Backend:** PDO prepared statements, auth via session; CSRF en rate limiting ontbreken → **security-relevant**.
- **Frontend:** Vue/PrimeVue lokaal geladen; state vooral per-pagina, geen centrale Pinia-store gedocumenteerd in deze scan.
- **Security:** Input validatie in velden en media (whitelist); upload path/hardcoded `/modular/` in MediaController en upload-handler → **P1** (configurable base path).
- **Performance:** Geen caching; image variants wel; DB-indexes aanwezig.

---

## 6. TODO-lijst (verplicht format)

Nieuw ontdekte items hieronder toevoegen; status na fix op `done` zetten en regressie testen.

---

[P0] Simuze Community als Modular Module  
**Type:** architectuur / migratie  
**Locatie:** `public/modules/simuze-community/` (module.php, migrations, classes, routes)  
**Probleem:** De Simuze Community plugin moet dezelfde rol krijgen in Modular als een WP-plugin: een **Module** die optioneel geactiveerd wordt en groups, messaging, shortcode-equivalenten en (optioneel) commerce levert.  
**Oorzaak:** In WP is Simuze Community een plugin; Modular kent **Modules** als uitbreidingsmechanisme (zoals plugins).  
**Oplossing:** Nieuwe Modular Module `simuze-community` aanmaken: `module.php` met headers (Name, Version, Description, …); migrations voor eigen tabellen (groups, group_members, messages, notifications, …); registratie van routes/components voor profile, group, messages, discography, activity, friends; activate/deactivate via Admin → Modules. Core blijft minimaal; groups/messaging/commerce zitten in de module.  
**Status:** done (skeleton: public/modules/simuze-community/module.php + migrations/001_groups.php; activate/deactivate via Admin → Modules; groups/messaging/UI nog open)  

---

[P0] User groups en group-scoped content (binnen Module simuze-community)  
**Type:** backend / architectuur  
**Locatie:** modules/simuze-community (migrations, classes, API)  
**Probleem:** Simuze verwacht user groups en content gekoppeld aan groepen; Modular core heeft alleen roles/capabilities.  
**Oorzaak:** Groups horen bij de Simuze Community-uitbreiding, niet in core.  
**Oplossing:** In de **Module simuze-community**: schema (groups, group_types, group_members, …); API en (admin of public) UI voor group CRUD; permission checks op group-scope; koppeling posts/media aan groups waar nodig.  
**Status:** done (schema 001_groups; admin/module-page.php + Community > Groups/Messages in sidebar; public/modules/simuze-community/admin/groups.php lijst; group CRUD API nog uit te breiden)  

---

[P0] Messaging (binnen Module simuze-community)  
**Type:** backend / frontend  
**Locatie:** modules/simuze-community (tabellen, controllers, UI)  
**Probleem:** Simuze heeft messaging; dit hoort bij de community-uitbreiding.  
**Oorzaak:** Messaging is plugin-/module-functionaliteit.  
**Oplossing:** In de **Module simuze-community**: schema (conversations, messages, participants); API en UI (admin en/of public); real-time optioneel (P2).  
**Status:** done (schema: migrations/002_messages.php – simuze_conversations, simuze_conversation_participants, simuze_messages); API/UI open  

---

[P0] Feature-pariteit paginatypes (artist, city, homepage, blog)  
**Type:** migratie / backend  
**Locatie:** post types config, public/themes  
**Probleem:** Zekerheid dat alle Simuze-paginatypes in Modular bestaan en correct renderen.  
**Oorzaak:** Mapping moet expliciet; bron = Simuze **theme** (post types, page templates, single templates) + **plugin** (profile/group/messages shortcodes).  
**Oplossing:** Post types album, track, playlist, event in Modular aanmaken; thema-templates mappen (template_music, template_network, template_live, template_weblog, template_city/country/province, single-album/track/playlist); content-migratie (WP exporter → Modular importer).  
**Status:** done (core/migrations/003_simuze_post_types.php: album, track, playlist, event; MDLR-2025 single-album/track/playlist/event.php; template_music/network/live/city e.d. nog optioneel per theme)  

---

[P1] Front-end templating: routing + template hierarchy  
**Type:** backend / frontend  
**Locatie:** public/index.php (of centrale front controller), core/classes/Theme.php  
**Probleem:** Om Simuze-thema’s om te zetten ontbreekt path-based routing en template-resolutie: single-{post_type}.php wordt niet geladen; geen “page by slug” of template-by-slug; geen archives. Zie §1.4.  
**Oorzaak:** Public entry is minimaal (alleen single type+slug of home); Theme class wordt niet gebruikt in entry.  
**Oplossing:** (1) Public entry laten gebruiken Theme class of dezelfde template-logica: voor single eerst single-{post_type}.php zoeken, dan single.php. (2) Routing uitbreiden: path (of GET) mappen op “page”/slug of vaste routes en daarbij een template laden (bijv. template_music.php voor /music). (3) Optioneel: archive-URL’s en renderArchive() aanroepen. Daarna kunnen Simuze page templates en single-album/track/playlist binnen Modular worden nagebouwd.  
**Status:** done (single-{post_type}.php in public/index.php; path-by-slug: één segment → post by slug, prefer type "page"; archive: één segment als post_type slug → archive-{type}.php of archive.php, $posts + $post_type_slug; MDLR-2025/archive.php toegevoegd)  

---

[P1] Simuze-thema flows (music upload, discography, rewrite)  
**Type:** migratie / frontend  
**Locatie:** public theme, API of admin  
**Probleem:** Simuze **theme** bevat zware flows: music upload (AJAX, upload-functions.php), discography-tabs (refresh tabs, album/track CRUD), genre CRUD, rewrite (country/province/city, artist handle, album/track slug).  
**Oorzaak:** Deze logica zit in het theme; moet in Modular als API + frontend of admin worden nagebouwd.  
**Oplossing:** Inventariseer alle theme-AJAX-actions (§1.1); bouw in Modular equivalente endpoints en/of admin-pagina’s; rewrite-rules in Modular routing of public index.  
**Status:** done (docs/SIMUZE_FLOWS.md: inventarisatie + mapping naar Modular; post types album/track; discography via Admin Posts; genre via taxonomies; stub/placeholder gedocumenteerd)  

---

[P1] CSRF-bescherming  
**Type:** backend / security  
**Locatie:** admin config, forms, auth, API  
**Probleem:** ARCHITECTURE.md meldt "CSRF protection (to be implemented)".  
**Oorzaak:** Nog niet geïmplementeerd.  
**Oplossing:** CSRF-token in sessie; token in forms en API-requests; middleware die alle state-changing requests valideert.  
**Status:** done (core/functions/csrf.php; login, register, forgot_password, reset_password; posts, users, settings general/security/appearance, taxonomies, terms, post-types, users/roles, media upload+edit, modules activate/deactivate)  

---

[P1] Rate limiting  
**Type:** backend / security  
**Locatie:** API, auth endpoints, core (RateLimiter)  
**Probleem:** Rate limiting "to be implemented"; RateLimiter class bestaat maar wordt niet overal gebruikt.  
**Oorzaak:** Niet consistent toegepast.  
**Oplossing:** Rate limiting toepassen op login, register, API (per IP en/of per user).  
**Status:** done (login 10/15min, register 5/u, forgot_password 5/u; API had al)  

---

[P1] Upload/base path configureerbaar  
**Type:** backend  
**Locatie:** core/classes/MediaController.php, admin/media/upload-handler.php  
**Probleem:** Hardcoded `/modular/public/uploads/`; in externe app of andere base path breekt pad.  
**Oorzaak:** Geen gebruik van BASE_PATH of config voor upload URL/pad.  
**Oplossing:** Upload directory en base URL uit config (bijv. config.php of settings); overal deze waarden gebruiken.  
**Status:** done (UPLOADS_PATH, UPLOADS_WEB_PATH in config; MediaController + upload-handler gebruiken ze)  

---

[P1] Capability-mapping t.o.v. WP  
**Type:** backend / migratie  
**Locatie:** roles, capabilities, admin UI  
**Probleem:** Zekerheid dat alle WP-capabilities (per post type, per actie) in Modular bestaan en correct worden gecontroleerd.  
**Oorzaak:** Mogelijke verschillen in naamgeving of granulatie.  
**Oplossing:** WP capability-lijst naast Modular zetten; ontbrekende capabilities toevoegen; permission checks in post/media/group flows toepassen.  
**Status:** done (docs/CAPABILITY_MAPPING.md: Modular defaults + WP-equivalenten; per–post-type uitbreiding beschreven)  

---

[P1] Toegankelijkheid (a11y) admin en/of public  
**Type:** UX  
**Locatie:** admin templates, Vue components, public theme  
**Probleem:** Geen bevestigde a11y-audit; risico op blockers voor gebruikers met hulpmiddelen.  
**Oorzaak:** Nog niet uitgevoerd.  
**Oplossing:** Focus order, ARIA-labels, contrast, toetsenbordnavigatie controleren; issues in deze TODO-lijst opnemen.  
**Status:** done (docs/A11Y_CHECKLIST.md; sidebar toggle aria-label; verdere audit optioneel)  

---

[P2] Field-builder migratie naar Vue + PrimeVue  
**Type:** frontend / UX-debt  
**Locatie:** admin/post-types/field-builder.php → admin/field-builder/  
**Probleem:** Field-builder is PHP + SortableJS; spec en README voorzien Vue + PrimeVue.  
**Oorzaak:** Historische implementatie; migratie gepland.  
**Oplossing:** Nieuwe field-builder in admin/field-builder/ met Vue 3 + PrimeVue; gelijke functionaliteit; daarna oude builder uitschakelen.  
**Status:** done (admin/field-builder/index.php + field-builder.js: Vue 3 app, Library | Canvas | Settings panel, SortableJS op canvas, save met CSRF; links in post-types en posts wijzen naar /field-builder/?id=; legacy field-builder.php blijft beschikbaar)  

---

[P2] Shortcode-equivalent (Module simuze-community)  
**Type:** architectuur / frontend  
**Locatie:** modules/simuze-community, public theme  
**Probleem:** WP shortcodes (simuze_profile, simuze_group, simuze_messages, …) hebben in Modular geen 1-op-1 equivalent.  
**Oorzaak:** Modular gebruikt components/blocks en routes; geen shortcode-parser.  
**Oplossing:** De **Module simuze-community** registreert routes en/of component-types voor profile, group, messages, discography, activity, friends, groups; theme of frontend gebruikt die in plaats van shortcodes. Eventueel minimale shortcode-laag voor legacy content (P2).  
**Status:** done (public/index.php laadt bootstrap, apply_filters('public_route', null, $pathInfo); module registreert /profile, /messages, /groups met placeholder-HTML)  

---

[P2] Caching layer  
**Type:** backend / performance  
**Locatie:** core, config  
**Probleem:** ARCHITECTURE.md: "Caching layer (to be implemented)".  
**Oorzaak:** Niet geïmplementeerd.  
**Oplossing:** Cache voor queries of rendered output (bijv. file of Redis); cache invalidation bij wijzigingen.  
**Status:** done (core/functions/cache.php: cache_get, cache_set, cache_delete, file-based; bootstrap laadt cache.php)  

---

[P2] Simuze theme template-parts en JS (volledige port)  
**Type:** frontend / theme  
**Locatie:** public/themes/simuze/template-parts/, public/themes/simuze/js/  
**Probleem:** Template-parts (album-grid, view-album, view-track, album-edit, track-edit-form, edit-music-form) en theme-JS (discography, music-upload, player, etc.) zijn gekopieerd uit simuze-wp; kunnen nog WP- of AJAX-aanroepen bevatten.  
**Oorzaak:** Eerste port levert bridge + routing + hoofdtemplates; diepere port van alle template-parts en JS naar Modular API is optioneel.  
**Oplossing:** Per template-part en JS-bestand controleren op wp_ajax_, get_option, e.d.; vervangen door Modular-equivalenten (API-endpoints, get_meta, settings).  
**Status:** open  

---

## 7. Iteratief uitvoeren

1. **P0 eerst:** Simuze Community als **Modular Module** (simuze-community) opzetten; daarbinnen groups, messaging; plus paginatypes/feature-pariteit (Simuze **theme**-templates, module levert shortcode-equivalenten) → oplossen → testen.  
2. Nieuwe issues tijdens werk toevoegen aan sectie 6 met hetzelfde format.  
3. Daarna **P1** (o.a. Simuze-thema flows, CSRF, rate limiting, upload path, capabilities, a11y) → oplossen → testen.  
4. **P2** waar nodig voor launch of direct erna.  
5. Stoppen wanneer alle P0 + P1 zijn opgelost, Modular feature-pariteit heeft met Simuze (theme + **Module** simuze-community) en het systeem stabiel is.

---

*Document gegenereerd in het kader van de Simuze WP → Modular audit. MCP program: plan.*
