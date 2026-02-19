#!/usr/bin/env bash
# Rebuild Modular CMS: Composer autoloader + admin vendor assets.
# Run from project root: bash admin/bin/rebuild.sh

set -e
cd "$(dirname "$0")/../.."

echo "=== Composer ==="
if command -v composer &>/dev/null; then
    composer install --no-interaction
else
    echo "Composer not found. Run manually: composer install"
fi

echo ""
echo "=== Admin vendor assets (Vue, PrimeVue, Quill, Aura theme) ==="
if [ -f admin/assets/vendor/download-vendor.sh ]; then
    bash admin/assets/vendor/download-vendor.sh
else
    echo "download-vendor.sh not found."
fi

echo ""
echo "=== Done ==="
echo "Start the app: php -S localhost:8080 -t public"
echo "Or use your existing web server."
