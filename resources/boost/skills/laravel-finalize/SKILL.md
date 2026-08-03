---
name: laravel-finalize
description: "Use BEFORE finalizing or marking complete any task in this project — especially after implementing a feature planned via brainstorm/spec, and as the last step of verification-before-completion. Runs the project finalization checks: regenerates IDE helpers, verifies every UI string is translated in all locales, and applies code style. Trigger on 'finish', 'finalize', 'done', 'wrap up', 'ready to commit', or when about to claim a task is complete."
---

# Laravel Finalize

Run these checks before claiming a task is complete. Do them in order, fix what surfaces, and re-run until clean.

## Checklist

1. **Regenerate IDE helpers** — keep model/facade autocompletion in sync after any model/migration/service change:
   ```bash
   vendor/bin/sail composer ide-helper
   ```
   Stage `_ide_helper.php` / `_ide_helper_models.php` if they changed.

2. **Check translations** — every `__()`/`@lang`/`trans()` string under `app/resources/routes` must exist in all `lang/*.json` locales:
   ```bash
   vendor/bin/sail php vendor/bin/check-translations
   ```
   Ships with `apavliukov/laravel-devtools` (dev tooling stays out of `App\Console`). By default it scans only strings under `app/resources/routes` in files changed vs `main` (the day-to-day ratchet). Run it once with `--all` for a full audit to clear the backlog. The locale list comes from the app's `lang/*.json` files. It lists the missing keys per locale — add accurate translations for **each** missing key to **every** locale:
   - Preserve placeholders verbatim: `:max`, `:name`, `:model`, `:language`, …
   - Never translate brand or product names — keep them unchanged across languages.
   - `en.json` is the canonical English source (key → English text).
   - Add only missing keys; never overwrite or reformat existing entries. Re-run until it passes.

3. **Code style** on changed files only:
   ```bash
   vendor/bin/sail bin pint --dirty --format agent
   ```

4. **Tests** for the affected code:
   ```bash
   vendor/bin/sail artisan test --filter=<relevant>
   ```

5. **Finalize extras** — check the project's `CLAUDE.md` for a "Finalize extras" section and run those steps too (project-specific builds, schema dumps, larastan, …).

6. Report what ran and any fixes applied.

> A project `Stop` hook (`vendor/apavliukov/laravel-devtools/hooks/check-translations.sh`) runs the checker as a backstop on turns that touched PHP/lang files and will flag remaining gaps — but it only **detects**. Fixing the translations is this skill's job.
