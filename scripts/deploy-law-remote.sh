#!/bin/bash
# Primjenjuje već provjereni git archive. Ne briše tuđe datoteke i ne sije bazu.
set -euo pipefail

NEW_TGZ=${1:?}
NEW_SHA=${2:?}
OLD_TGZ=${3:-}

APP=/var/www/legal-saas
WORK=/tmp/legal-deploy-work
rm -rf "$WORK"
mkdir -p "$WORK/new"
tar -xzf "$NEW_TGZ" -C "$WORK/new"

if [ -n "$OLD_TGZ" ]; then
  mkdir -p "$WORK/old"
  tar -xzf "$OLD_TGZ" -C "$WORK/old"
  REF=$WORK/old
else
  REF=$WORK/new
fi

skip() {
  case "$1" in
    bootstrap/cache/*|storage/*|vendor/*|node_modules/*|public/storage/*|public/build/*|public/hot|database/database.sqlite|.env|.deploy-sha)
      return 0
      ;;
  esac
  return 1
}

collect() {
  local root=$1
  (
    cd "$root"
    find app bootstrap config database public resources routes tests \
      \( -path 'bootstrap/cache' -o -path 'public/storage' -o -path 'public/build' \) -prune -o \
      -type f -print 2>/dev/null || true
    for f in composer.json composer.lock artisan; do
      [ -f "$f" ] && printf '%s\n' "$f"
    done
  )
}

fail=0
while IFS= read -r rel; do
  skip "$rel" && continue
  if [ ! -f "$REF/$rel" ]; then
    echo "EXTRA $rel"
    fail=1
    continue
  fi
  if ! cmp -s "$APP/$rel" "$REF/$rel"; then
    echo "DIFF $rel"
    fail=1
  fi
done < <(collect "$APP")

if [ "$fail" -ne 0 ]; then
  echo "Objava stala. Poslužitelj ima datoteke kojih nema u commitu koji se objavljuje."
  exit 1
fi

while IFS= read -r rel; do
  skip "$rel" && continue
  mkdir -p "$APP/$(dirname "$rel")"
  cp "$WORK/new/$rel" "$APP/$rel"
done < <(collect "$WORK/new")

printf '%s\n' "$NEW_SHA" > "$APP/.deploy-sha"
cd "$APP"
php artisan migrate --force
php artisan view:clear
php artisan route:clear
php artisan config:clear
echo "OBJAVA $NEW_SHA"
