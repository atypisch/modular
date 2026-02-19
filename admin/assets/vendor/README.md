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
| `tinymce/` | TinyMCE (WYSIWYG editor); run `download-tinymce.sh` |
| `sortablejs/Sortable.min.js` | Drag & drop (Field Builder) |
| `fontawesome/css/all.min.css` + `webfonts/` | Font Awesome icons |

If `tinymce/` is missing, the WYSIWYG field will not load; run `admin/assets/vendor/download-tinymce.sh` to install TinyMCE locally.

**jQuery:** The admin no longer loads jQuery; sidebar and tables use vanilla JS. The download script skips jQuery by default. Uncomment the jQuery line in `download-vendor.sh` if a module needs it.

**PrimeVue styled mode:** If `primevue/themes/aura.js` exists, the post-edit form uses the Aura theme ([PrimeVue Theming](https://primevue.org/theming/styled/)). Otherwise PrimeVue falls back to default styles. The post-edit page loads `primevue-fields.js` for Relationship (MultiSelect) and Post Object (Dropdown). WYSIWYG uses TinyMCE via the field renderer.

After the script runs, the admin works offline.
