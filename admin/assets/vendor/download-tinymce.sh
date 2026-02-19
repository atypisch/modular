#!/usr/bin/env bash
# Install TinyMCE locally under admin/assets/vendor/tinymce (no CDN).
# Run from project root: bash admin/assets/vendor/download-tinymce.sh
# Requires: npm (to fetch the package); copies only needed core, theme, and plugins.

set -e
# Resolve script dir and project root (modular repo root)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../../../../.." && pwd)"
VENDOR="$ROOT/admin/assets/vendor"
TINYDIR="$VENDOR/tinymce"
cd "$ROOT"

mkdir -p "$TINYDIR"

# Prefer copying from admin/node_modules (npm install in admin/ first)
SRC=""
[ -d "$ROOT/admin/node_modules/tinymce" ] && SRC="$ROOT/admin/node_modules/tinymce"
[ -z "$SRC" ] && [ -d "$ROOT/node_modules/tinymce" ] && SRC="$ROOT/node_modules/tinymce"

if [ -z "$SRC" ] || [ ! -f "$SRC/tinymce.min.js" ]; then
  echo "TinyMCE not found. From project root run: cd admin && npm install && cd .. && bash admin/assets/vendor/download-tinymce.sh"
  exit 1
fi

echo "Copying TinyMCE to $TINYDIR..."
cp -f "$SRC/tinymce.min.js" "$TINYDIR/" 2>/dev/null || cp -f "$SRC/tinymce.js" "$TINYDIR/tinymce.min.js" 2>/dev/null || true
[ -d "$SRC/skins" ]   && cp -r "$SRC/skins" "$TINYDIR/"
[ -d "$SRC/icons" ]   && cp -r "$SRC/icons" "$TINYDIR/"
[ -d "$SRC/themes" ]  && cp -r "$SRC/themes" "$TINYDIR/"
[ -d "$SRC/models" ]  && cp -r "$SRC/models" "$TINYDIR/"
[ -d "$SRC/langs" ]   && cp -r "$SRC/langs" "$TINYDIR/" 2>/dev/null || true

PLUGINS="link lists table code wordcount charmap visualblocks fullscreen autoresize"
mkdir -p "$TINYDIR/plugins"
for p in $PLUGINS; do
  [ -d "$SRC/plugins/$p" ] && cp -r "$SRC/plugins/$p" "$TINYDIR/plugins/" 2>/dev/null || true
done

echo "Done. TinyMCE is in $TINYDIR ."
