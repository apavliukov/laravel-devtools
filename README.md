# laravel-devtools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/apavliukov/laravel-devtools.svg)](https://packagist.org/packages/apavliukov/laravel-devtools)
[![Total Downloads](https://img.shields.io/packagist/dt/apavliukov/laravel-devtools.svg)](https://packagist.org/packages/apavliukov/laravel-devtools)

Shared development tooling for Laravel projects, distributed as a `require-dev`
composer package so every project and every developer gets the same tools without
per-project copies that rot.

Ships:

- **`laravel-finalize` Boost skill** — the finalization checklist (IDE helpers →
  translations → Pint → tests) that Claude runs before marking work complete.
  [Laravel Boost](https://github.com/laravel/boost) auto-installs it into
  `.claude/skills/laravel-finalize/` on `php artisan boost:update`.
- **`check-translations`** — a composer-bin script that scans `app/`, `resources/`
  and `routes/` for `__()` / `@lang` / `trans()` / `trans_choice()` strings and fails
  if any is missing from any of the app's `lang/*.json` locales.
- **Claude Code Stop hook** — a backstop that blocks ending a turn with
  untranslated strings.

## Requirements

- PHP 8.4+
- Locale files as `lang/*.json` (the locale list is derived from them — no config)

## Installation

```bash
composer require --dev apavliukov/laravel-devtools
php artisan boost:update
```

`boost:update` installs the skill into `.claude/skills/laravel-finalize/`. Keep that
directory git-ignored — it is a vendor artifact, reinstalled on every `boost:update`.

## Translation checker

```bash
# Ratchet mode (default): only strings in files changed vs main
vendor/bin/sail php vendor/bin/check-translations

# Full audit: every string under app/resources/routes
vendor/bin/sail php vendor/bin/check-translations --all
```

Exit code 1 and an `Untranslated strings found` marker on STDERR mean gaps; the
output lists missing keys per locale. Dotted keys (`auth.failed`) resolve from PHP
array lang files and are ignored.

## Stop hook

Wire the hook in the project's `.claude/settings.json` so Claude cannot end a turn
that touched PHP/lang files while translations are missing:

```json
{
    "hooks": {
        "Stop": [
            {
                "hooks": [
                    {
                        "type": "command",
                        "command": "vendor/apavliukov/laravel-devtools/hooks/check-translations.sh"
                    }
                ]
            }
        ]
    }
}
```

The hook is a detector only — silent when nothing relevant changed or when
Docker/Sail is down; fixing translations is the skill's job.

## Migrating a project off local copies

1. `composer require --dev apavliukov/laravel-devtools && php artisan boost:update`
2. Delete the local `.claude/skills/laravel-finalize/` copy (and any local
   `translations:check` artisan command).
3. Repoint `.claude/settings.json` hooks to the vendor path above; delete the local
   `.claude/hooks/check-translations.sh`.
4. Drop the gitignore exception for `!/.claude/skills/laravel-finalize/`.

## Releases

Versioned with `commit-and-tag-version` (conventional commits, enforced by
commitlint + lefthook). Use `yarn release:patch|minor|major` — see CHANGELOG.md.

## License

MIT
