# Kritee

[![tests](https://github.com/cosmicspork/kritee/actions/workflows/tests.yml/badge.svg)](https://github.com/cosmicspork/kritee/actions/workflows/tests.yml)
[![linter](https://github.com/cosmicspork/kritee/actions/workflows/lint.yml/badge.svg)](https://github.com/cosmicspork/kritee/actions/workflows/lint.yml)

A Laravel application for managing work — **accounting**, **tasks/kanban**, and **time tracking**.

## About the name

**Kritee** comes from the Hindi/Sanskrit word कृति (*kṛti*) — a "creation," "work," or "composition." It's
sometimes romanised with an accented long *i* (*kṛti* / *kritī*), which is where the doubled-e spelling approximates the long final vowel. A tool for managing the work people make seemed like a fitting thing to name after the act of making.

## Stack

- **Laravel 13** on **PHP 8.5**
- **Livewire 4**
- **Fortify** for authentication
- **Postgres** (Valkey for cache/queue), deployed to **Laravel Cloud**
- **Pest 4** for tests, **Larastan/PHPStan** (level 6) for static analysis, **Pint** for formatting

## Getting started

### Dev container (recommended)

Open the repository in a Dev Container. It provisions PHP 8.5, bun, a local Postgres 17 cluster and Valkey, installs dependencies, runs migrations, and builds assets automatically.

### Local

```bash
cp .env.example .env       # defaults to a local Postgres named "kritee"
composer install
bun install
php artisan key:generate
php artisan migrate
composer run dev           # serve + queue + logs + vite
```

## Common tasks

Recipes live in the [`justfile`](justfile):

```bash
just dev            # run the app (server, queue, logs, vite)
just format         # apply Pint
just check          # format-check + static analysis
just test           # run the Pest suite
just all            # everything CI runs
```

## Quality

CI runs on every push and pull request to `main`:

- **linter** — Pint (code style) + PHPStan level 6
- **tests** — the Pest suite on PHP 8.5
