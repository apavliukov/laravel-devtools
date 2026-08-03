#!/usr/bin/env bash
#
# Stop-hook backstop for i18n completeness.
#
# Nudges Claude to finish translations before ending a turn that touched PHP or
# locale files. Gated to changed PHP/lang files so unrelated turns are never
# delayed, and only nags when the checker actually ran and reported gaps
# (so it stays silent when Docker/Sail is down).
#
set -uo pipefail

project_dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$project_dir" || exit 0

# Read the hook payload; bail out if we're already continuing from this hook to
# avoid an infinite stop -> continue -> stop loop.
payload="$(cat 2>/dev/null || true)"
if printf '%s' "$payload" | grep -q '"stop_hook_active"[[:space:]]*:[[:space:]]*true'; then
  exit 0
fi

# Only act when PHP or locale JSON files changed this session.
if ! git status --porcelain 2>/dev/null | grep -qE '\.php$|lang/[A-Za-z_-]+\.json$'; then
  exit 0
fi

output="$(vendor/bin/sail php vendor/bin/check-translations 2>&1 || true)"

if printf '%s' "$output" | grep -q 'Untranslated strings found'; then
  echo "Untranslated strings remain. Use the laravel-finalize skill: run vendor/bin/check-translations, add the missing keys to every lang/*.json locale, then finish." >&2
  exit 2
fi

exit 0
