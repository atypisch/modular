# Simuze theme flows – inventarisatie en Modular-equivalent

Overzicht van zware flows uit het Simuze-thema en hoe ze in Modular (of de simuze-community module) kunnen worden nagebouwd.

## Bron: Simuze theme (§1.1 audit)

### Music upload & metadata

| WP/AJAX action | Doel | Modular-equivalent | Status |
|----------------|------|--------------------|--------|
| `simuze_upload_music`, `simuze_upload_single_file` | Upload audio bestanden | Admin media upload (audio MIME allowed); optional row in md_music_uploads | ✅ |
| `simuze_save_metadata`, `simuze_publish_music` | Metadata opslaan, publiceren | Post save (album/track); custom fields in post data | ✅ |
| `simuze_delete_music`, `simuze_delete_track`, `simuze_delete_album` | Verwijderen | Post delete + eventueel media cleanup | ✅ |
| `simuze_update_album`, `simuze_change_audio_file` | Album/track bijwerken | Post edit + media koppeling | ✅ |
| `simuze_ajax_create_album`, `simuze_ajax_update_album` | Album CRUD | Post types album/track; admin posts of API | ✅ |

### Discography-tabs

| WP/AJAX | Doel | Modular-equivalent | Status |
|---------|------|--------------------|--------|
| `simuze_refresh_overview_tab`, `simuze_refresh_*_tab` | Tab-inhoud verversen | Lijst op post type (album/track) + filters op status | ✅ |
| `simuze_get_tab_counts` | Aantallen per status | GET /api/v1/simuze/discography/counts (JSON) | ✅ |
| `simuze_update_album_status` | Status wijzigen | Post status update (draft/published) | ✅ |

### Genre / taxonomie

| WP/AJAX | Doel | Modular-equivalent | Status |
|---------|------|--------------------|--------|
| `simuze_search_genres`, `simuze_get_all_genres`, `simuze_create_genre` | Genre CRUD | Taxonomie “genre” + GET /api/v1/simuze/genres; admin taxonomies/terms | ✅ |

### Overig theme

- **Rewrite (country/province/city, artist handle):** Path-routing in `public/index.php` of module; custom routes of post-by-slug; eventueel taxonomieën voor locatie.
- **Template-by-slug:** Pagina met slug “music” → template; in Modular: path-by-slug (één segment) of vaste route naar specifiek template (nog uit te breiden).

## Aanpak in Modular

1. **Post types:** Album, track, playlist, event bestaan (migratie 003); single-*.php templates in theme.
2. **Media:** Audio toevoegen aan MIME-whitelist in upload-handler; eventueel aparte “music upload” flow in module.
3. **Discography:** Admin Posts gefilterd op post_type_id (album/track) + status; of module-adminpagina “Discography” die dezelfde data toont.
4. **Genre:** Taxonomie “genre” aanmaken, koppelen aan “track”; CRUD via bestaande taxonomies/terms.
5. **API:** Optioneel REST of interne API voor front-end (bijv. Vue) voor tab-refresh en CRUD; endpoints in admin of public (met auth).

## md_* tables (music reports/reviews/comments)

| Table | Usage |
|-------|--------|
| md_music_uploads | Optional extra refs for audio beyond `media`; link via media_id to core media. |
| md_music_reports | Reports on music content (track/album); reporter_id, content_type, content_id, status. |
| md_music_reviews | Reviews for music; reviewer_id, content_type, content_id, rating, body, status. |
| md_music_review_comments | Comments on reviews; review_id, user_id, body. |

Theme or module can implement report/review/comment flows using these tables; no REST endpoints in core yet.

## New public API endpoints (Simuze theme)

- **GET /api/v1/simuze/genres** – JSON list of genre terms (id, name, slug).
- **GET /api/v1/simuze/discography/counts** – JSON counts for album/track by status (draft, pending, published, scheduled); optional filter by current user when authenticated.

## SIMEZU auth & payments (stub)

- **Config:** `core/config/simuze.php` definieert placeholders `SIMEZU_AUTH_ENABLED`, `SIMEZU_AUTH_API_URL`, `SIMEZU_PAYMENTS_ENABLED`, `SIMEZU_PAYMENTS_API_URL` (via getenv of config.local.php). Geen logica tot .env/config is gezet.
- **Stub classes:** `Modular\SimezuAuth`, `Modular\SimezuPayments` (core/classes/) met `isEnabled()` en `getApiUrl()`; gebruiker past .env later aan.

## Stub / placeholder

- **Admin:** Onder Community-module kan een “Discography” subpagina worden toegevoegd die een link naar Posts gefilterd op album/track toont, of een eigen overzichtspagina (zelfde als Groups).
- **Public:** Music-upload en -bewerking kunnen eerst volledig via Admin → Posts (album/track) en Media; daarna eventueel front-end flows met eigen endpoints.
