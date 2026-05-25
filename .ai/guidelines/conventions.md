# Conventions

Read `docs/ARCHITECTURE.md` (also linked as `.ai/guidelines/architecture.md`)
before touching the Action/Service layers — it is the source of truth for the
design.

## Commits

[Conventional Commits](https://www.conventionalcommits.org). One-line subject,
no body:

```
type(scope): imperative summary
```

Types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `ci`, `build`, `perf`.
Scope is optional. Leave the *details* to the pull request — the commit subject
is the headline, not the explanation.

## Branches

`type/short-kebab-summary`, e.g. `feat/time-entry-timer`, `chore/project-skeleton`.

## Pull requests

The diff already answers **what** changed. A description only needs the **why**
plus anything a reviewer can't infer from the code. No test plans, no restating
the changes, no length for its own sake.

## Comments

Code says *what*; a comment earns its place only when it explains a *why* that
isn't obvious from the code — a non-trivial constraint, a deliberate trade-off, a
gotcha. Delete comments that restate the line below them. Prefer a clearer name
or smaller function over a comment. When in doubt, leave it out.

## Releases

`release-please` maintains `CHANGELOG.md` and cuts tagged GitHub releases from the
conventional-commit history. Do not hand-edit `CHANGELOG.md`; write good commit
subjects instead.

## Verify before committing

Run these gates after edits, in order — fastest first, don't flip-flop:

1. **Targeted tests** for what you changed: `php artisan test --compact <path>`.
2. **`just analyse`** (PHPStan/Larastan). The gate agents skip most; don't.
3. **`vendor/bin/pint --dirty --format agent`** — formatting; trust it as
   behavior-safe.
4. **Full suite** (`php artisan test --compact`) only when the change reaches
   beyond the files the targeted tests already cover.

`just check` runs format-check + analyse; `just all` adds the suite.
