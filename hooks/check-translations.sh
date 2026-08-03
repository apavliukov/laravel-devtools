#!/usr/bin/env bash
#
# Stop-hook backstop for i18n completeness.
#
# Nudges Claude to finish translations before ending a turn that touched PHP or
# locale files. Gated to changed PHP/lang files so unrelated turns are never
# delayed. Runs the checker with host PHP — it needs only the autoloader and git,
# no framework boot — so the backstop works even when Docker/Sail is down.
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

# Only act when PHP or locale JSON files changed this session; let git do the
# filtering instead of scanning the whole worktree and grepping.
if [ -z "$(git status --porcelain -- '*.php' 'lang/*.json' 2>/dev/null)" ]; then
  exit 0
fi

command -v php >/dev/null 2>&1 || exit 0

# Capture, then grep: piping into `grep -q` under pipefail turns the checker's
# SIGPIPE (141) into a failed pipeline and silently skips the nag branch.
output="$(php vendor/bin/check-translations 2>&1 || true)"

if printf '%s' "$output" | grep -q 'Untranslated strings found'; then
  echo "Untranslated strings remain. Use the laravel-finalize skill: run vendor/bin/check-translations, add the missing keys to every lang/*.json locale, then finish." >&2
  exit 2
fi

exit 0
