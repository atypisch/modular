# Admin vendor assets (internal only, no CDN at runtime)

All admin scripts and styles are loaded **from this folder only**. No external URLs are used when the admin is open; everything is internal/downloaded.

## One-time setup

From the project root, run (requires `curl`):

```bash
bash admin/assets/vendor/download-vendor.sh
```

This fetches:

| Path | Purpose |
|------|---------|
| `vue/vue.global.prod.js` | Vue 3 (admin UI) |
| `primevue/primevue.min.js` + `.min.css` | PrimeVue components |
| `primevue/themes/aura.js` | Aura theme (styled mode; optional) |
| `primeicons/primeicons.css` | PrimeVue icons |
| `quill/quill.min.js` + `quill.snow.css` | Quill (required for PrimeVue Editor / WYSIWYG) |
| `jquery/jquery-3.6.0.min.js` | jQuery (sidebar, legacy) |
| `sortablejs/Sortable.min.js` | Drag & drop (Field Builder) |
| `fontawesome/css/all.min.css` + `webfonts/` | Font Awesome icons |

If `quill/` is missing, the PrimeVue Editor (WYSIWYG) will not load; other fields still work. Run the script to get Quill.

**PrimeVue styled mode:** If `primevue/themes/aura.js` exists, the post-edit form uses the Aura theme ([PrimeVue Theming](https://primevue.org/theming/styled/)). Otherwise PrimeVue falls back to default styles. The post-edit page loads `primevue-fields.js`, which enhances WYSIWYG (Editor), Relationship (MultiSelect), and Post Object (Dropdown) with PrimeVue components.

After the script runs, the admin works offline.
