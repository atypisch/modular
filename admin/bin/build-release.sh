#!/usr/bin/env bash
#
# build-release.sh — generate the deployable Modular core release ZIP.
#
# The GitHub repo is the SSOT; this packages it into a clean, versioned ZIP
# (the ACTUAL deployable artifact) that the in-admin "System update" button can
# consume. GitHub auto-generates a zipball per release tag (what UpdateService
# downloads by default); this script lets you (a) bump core/version.json and
# (b) produce/verify a clean dist ZIP locally before cutting the release.
#
# Usage:
#   admin/bin/build-release.sh            # package current core/version.json version
#   admin/bin/build-release.sh 1.02       # bump version.json to 1.02, then package
#
set -euo pipefail

# repo root = two levels up from this script (admin/bin/ -> repo root)
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

VERSION_FILE="core/version.json"
[ -f "$VERSION_FILE" ] || { echo "✗ $VERSION_FILE not found"; exit 1; }

# Optional: bump version
if [ "${1:-}" != "" ]; then
  NEW="$1"
  if command -v python3 >/dev/null 2>&1; then
    python3 - "$VERSION_FILE" "$NEW" <<'PY'
import json,sys,datetime
f,ver=sys.argv[1],sys.argv[2]
d=json.load(open(f))
d['version']=ver
d['build_date']=datetime.date.today().isoformat()
json.dump(d,open(f,'w'),indent=4); open(f,'a').write("\n")
PY
  fi
  echo "→ version.json set to $NEW"
fi

VERSION="$(python3 -c "import json;print(json.load(open('$VERSION_FILE'))['version'])" 2>/dev/null || echo "0.0.0")"
DIST="dist"
ZIP="$DIST/modular-core-v${VERSION}.zip"
mkdir -p "$DIST"
rm -f "$ZIP"

echo "→ packaging Modular core v${VERSION}"

# Exclude dev/runtime/secret/local paths from the deployable artifact.
zip -r -q "$ZIP" . \
  -x ".git/*" \
  -x "node_modules/*" "*/node_modules/*" \
  -x "dist/*" \
  -x "uploads/*" "*/uploads/*" "public/uploads/*" \
  -x "core/storage/backups/*" \
  -x "updates/tmp/*" \
  -x ".playwright-mcp/*" \
  -x "*.playwright-mcp/*" \
  -x "admin/config/config.local.php" \
  -x "admin/config/installed.php" \
  -x "*.log" \
  -x "*.DS_Store" \
  -x "*.png" "*.jpg" "*.jpeg" \
  -x "e2e/*" "docs/*"

SIZE="$(du -h "$ZIP" | cut -f1)"
echo "✓ built $ZIP ($SIZE)"
echo
echo "Next — cut the GitHub release (the SSOT the update button reads):"
echo "  git tag v${VERSION} && git push origin v${VERSION}"
echo "  gh release create v${VERSION} '$ZIP' --title 'v${VERSION}' --notes 'Release notes…'"
echo
echo "The admin → System update screen will then offer v${VERSION} to every install."
