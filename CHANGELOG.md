# Changelog

All notable changes to this project will be documented in this file. See [commit-and-tag-version](https://github.com/absolute-version/commit-and-tag-version) for commit guidelines.

## [0.1.1](https://github.com/apavliukov/laravel-devtools/compare/v0.1.0...v0.1.1) (2026-08-03)

### Highlights

The package is now published on [Packagist](https://packagist.org/packages/apavliukov/laravel-devtools); the README links it. No code changes.

## 0.1.0 (2026-08-03)

### Highlights

First release. Ships the `laravel-finalize` Boost skill, the `check-translations` composer-bin checker (ratchet vs `main` by default, `--all` for a full audit, locales derived from the host's `lang/*.json`), and the Claude Code Stop-hook backstop.

To wire up a project: `composer require --dev apavliukov/laravel-devtools`, run `php artisan boost:update`, and point the `Stop` hook in `.claude/settings.json` at `vendor/apavliukov/laravel-devtools/hooks/check-translations.sh`.

### Features

* bootstrap laravel-devtools package ([7eef674](https://github.com/apavliukov/laravel-devtools/commit/7eef6743b5f87f9d188ba2a75f0fef3e568a86be))
