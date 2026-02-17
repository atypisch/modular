# Field Builder (Vue 3 + PrimeVue)

Per `docs/MODULAR_CUSTOM_FIELDS_SPEC.md`:

- **Layout:** Links = Field Library (search, accordion categories, drag handle). Rechts = Canvas (vertical sortable, drop indicators).
- **Field settings:** Inline slide-in panel, tabs: General, Validation, Display, Logic. Geen modal chaos.
- **UI:** Vue 3 + form controls; PrimeVue loaded for future use (Input, Select, Tabs, etc.). Icons: FontAwesome.

**Implementatie:** `admin/field-builder/index.php` (entry) + `field-builder.js` (Vue 3 app). Library: drag to canvas or click to add. Canvas: SortableJS for reorder; click row to open settings panel. Save: POST to `admin/post-types/field-schema-save.php` (CSRF protected).

**Reorder:** `onEnd` uses only `evt.oldIndex` / `evt.newIndex` to recompute `activeFields` (array splice). No DOM or `data-index` is read, so ghost/placeholder nodes cannot produce wrong or empty rows. After update, `canvasListKey` is incremented so the list is re-mounted and Sortable is re-initialised in `$nextTick`. The `updated()` hook no longer re-inits Sortable (avoids races).

Legacy: `admin/post-types/field-builder.php` (PHP + SortableJS) blijft beschikbaar; links in admin wijzen naar `/field-builder/?id=` (Vue-versie).

## Testing

**Unit test (reorder logic)**  
`node admin/field-builder/buildReordered.test.js` — checks that building the reordered array never yields undefined or duplicates.

**Manual test (drag/reorder visibility)**  
1. Open Field Builder for a post type (e.g. `/admin/field-builder/?id=1`).
2. Add 4–5 fields from the library (e.g. Text, Email, WYSIWYG, Image, Gallery).
3. Drag a field by its grip handle to a new position; release.
4. **Verify:** Every row shows label, icon, type badge, and action buttons (settings, duplicate, delete). No empty row and no row with invisible/faded title or icons.
5. Repeat reorder several times (different rows, different directions).
6. Optionally: drag from library onto canvas, then reorder; confirm new field and all others remain fully visible.
