#!/usr/bin/env bash
# Download admin vendor assets so the app runs without external CDNs.
# Run from project root: bash admin/assets/vendor/download-vendor.sh

set -e
VENDOR="admin/assets/vendor"
mkdir -p "$VENDOR/jquery" "$VENDOR/sortablejs" "$VENDOR/vue" "$VENDOR/primevue" "$VENDOR/primeicons" "$VENDOR/fontawesome/css" "$VENDOR/fontawesome/webfonts" "$VENDOR/tinymce"

# jQuery: not used by admin (sidebar/tables use vanilla JS). Uncomment if a module needs it.
# echo "Downloading jQuery..." && curl -sL -o "$VENDOR/jquery/jquery-3.6.0.min.js" "https://code.jquery.com/jquery-3.6.0.min.js"

echo "Downloading SortableJS..."
curl -sL -o "$VENDOR/sortablejs/Sortable.min.js" "https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"

echo "Downloading Vue 3..."
curl -sL -o "$VENDOR/vue/vue.global.prod.js" "https://unpkg.com/vue@3/dist/vue.global.prod.js"

echo "Downloading PrimeVue..."
curl -sL -o "$VENDOR/primevue/primevue.min.js" "https://unpkg.com/primevue@4/umd/primevue.min.js"
curl -sL -o "$VENDOR/primevue/primevue.min.css" "https://unpkg.com/primevue@4/resources/primevue.min.css"

echo "Downloading PrimeVue Aura theme (styled mode)..."
mkdir -p "$VENDOR/primevue/themes"
curl -sL -o "$VENDOR/primevue/themes/aura.js" "https://unpkg.com/@primeuix/themes/umd/aura.js"

echo "Downloading PrimeIcons..."
curl -sL -o "$VENDOR/primeicons/primeicons.css" "https://unpkg.com/primeicons@6/primeicons.css"
# PrimeIcons 6 uses inline base64 in CSS or webfonts; if the CSS references fonts, download them
if grep -q "url(" "$VENDOR/primeicons/primeicons.css"; then
  echo "PrimeIcons: if fonts are 404, copy them from node_modules/primeicons/fonts or primeicons package."
fi

echo "TinyMCE: run admin/assets/vendor/download-tinymce.sh to install TinyMCE locally (WYSIWYG)."

echo "Downloading Font Awesome CSS..."
curl -sL -o "$VENDOR/fontawesome/css/all.min.css" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
echo "Downloading Font Awesome webfonts..."
for f in fa-solid-900 fa-regular-400 fa-brands-400; do
  curl -sL -o "$VENDOR/fontawesome/webfonts/$f.woff2" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/$f.woff2" || true
done
echo "Done. Admin vendor assets are in $VENDOR ."
