# Architecture

Kriti manages client work — accounting, tasks/kanban, time tracking. It ships
**without** AI features but is built so every write is a self-contained,
idempotent, auditable unit that can later be exposed as a tool to an LLM agent.
Designed for AI; not dependent on it.

This document is the source of truth for the application's core patterns. Read
it before adding or changing anything in `app/Actions`, `app/Services`, or the
actor wiring.

## Service / Action split

- **Services** hold *pure domain logic*: calculations (e.g. the billing-rate
  cascade), validation, and non-trivial queries. A service **must not** open a
  transaction, dispatch an event, or call another service. If it needs one of
  those, it has become an action — make it one.
- **Actions** own the *unit of work*: open the transaction, call services,
  persist, fire events, return a result. Actions are the single control surface,
  callable identically from HTTP, CLI, queued jobs, and (later) AI tools.

Keeping services side-effect-free and actions transactional makes the unit-of-work
boundary unambiguous. The future agent layer (`AgentExecution.tool_calls` audit
log) needs each tool invocation to be a replay-safe unit — which only holds if
actions already are.

## Action contract

One uniform shape, so every caller looks identical:

```php
interface Action
{
    public function execute(ActionInput $input): ActionResult;
}
```

- **Input DTOs** use `spatie/laravel-data` — readonly inputs, validation,
  serialization, and JSON Schema generation. When the agent layer arrives, tool
  schemas derive from input DTOs rather than being hand-maintained.
- **Result** is a generic `ActionResult` (`success` / `data` / `errors`). Pick
  one result shape and stay consistent.
- **Idempotency** is part of the contract from day one. `ActionInput` carries an
  optional `idempotency_key`; an action checks it before any side-effectful work
  and short-circuits on repeats. A retrying agent must never double-send an
  invoice or double-bill a time entry.

## Actor abstraction

Every action call carries an explicit actor:

```php
interface Actor
{
    public function id(): ?string;
    public function isUser(): bool;
    public function isSystem(): bool;
    public function isAgent(): bool;
}
```

`UserActor` wraps a `User`; `SystemActor` is cron/CLI/queued work with no user;
`AgentActor` wraps an `AgentExecution` so audit trails link back to the run that
requested the action. Wiring per caller:

- **HTTP** — middleware wraps `auth()->user()` in a `UserActor`.
- **CLI** — `SystemActor` by default, `UserActor` with an explicit user flag.
- **Jobs** — carry `user_id` forward in the payload and rebuild `UserActor`;
  otherwise `SystemActor`.
- **Agents** — `AgentActor` wrapping the originating `AgentExecution`.

**Authorization lives inside the action**, not the controller. Call policies from
the action. Controller-side gates don't fire for jobs, CLI, or agents, so they
are not a security boundary in a multi-caller architecture.

## Read paths

Reads are not actions — wrapping every list/show in `execute(...)` is noise, and
the agent layer doesn't need reads as transactional units. Choose one of query
objects (`App\Queries\TicketsForKanban`), Eloquent scopes, or direct queries from
Livewire components, and do not mix. Read consistency comes from convention.

## Frontend

- **Livewire + Alpine + Blade** for the product surface. The action layer maps
  directly to Livewire component methods.
- **[Mary UI](https://mary-ui.com)** (free, MIT, daisyUI + Tailwind) is the
  component vocabulary: forms, tables, modals, drawers, badges, buttons. The
  daisyUI theme pair is `lemonade` (light) / `forest` (dark); the active mode is
  persisted client-side (`theme-mode` in `localStorage`) and applied before paint
  in `partials/head`.
- **No Flux.** The scaffold shipped with `livewire/flux`; auth and settings views
  were migrated to Mary UI and the dependency was dropped. Do not reintroduce
  `<flux:*>`.
- **Filament** is reserved for an internal admin slice only (user management, raw
  data inspection) — never the main UI.
- **Kanban** is custom: Alpine Sort + Mary UI cards + a single Livewire method to
  persist `sort_order` on drop. It is too app-specific for a black-box component.

## Enforcement

The lightest enforcement that holds the line:

1. **Namespaces.** `App\Actions\{Domain}\{ActionName}`,
   `App\Services\{Domain}\{ServiceName}`. The namespace says where logic belongs.
2. **Pest architecture tests** in `tests/Architecture/`, run in CI. The core
   rules:
   - Classes in `App\Actions` implement `Action`; `execute` takes one
     `ActionInput` and returns `ActionResult`.
   - Classes in `App\Services` import neither `DB::transaction`, `Event`, nor
     anything from `App\Actions`, and are `final` (services are leaf nodes —
     subclassing one is a smell that it should have been an action).
   - Controllers don't import models.
   - Input DTOs extend the base `ActionInput` (so every action inherits the
     `idempotency_key` contract and JSON Schema generation for free).
   - Domain events live in `App\Events` and are dispatched only from
     `App\Actions` — never from services, controllers, or models. This keeps the
     event seam (the one the agent layer and webhooks attach to) on the
     unit-of-work boundary.
   Each rule is a few lines of Pest; together they catch the violations that
   namespaces alone won't.
3. **`make:action` generator** is the enforcement seam, not just a convenience.
   It scaffolds Action + Input DTO + Result + arch-test stub *with the boilerplate
   already compliant*: the Input DTO extends `ActionInput` (idempotency key
   wired), the Action resolves an `Actor`, opens the transaction, and returns an
   `ActionResult`. Because the generated starting point already passes the arch
   tests, the easy path is the correct path — drift takes deliberate effort, and
   reviewers see a uniform shape on every action.

PHPStan/Larastan runs at level 6 over `app`, `routes`, `database` (see
`phpstan.neon`).

## Keep in sync

When you change the core patterns above, update together:

- This file (`docs/ARCHITECTURE.md`) — `.ai/guidelines/architecture.md` symlinks
  to it, so the AI guidelines never drift.
- The matching Pest architecture test under `tests/Architecture/`.
- `make:action` stubs, when the Action/Input/Result shape changes.

After editing AI guidelines, run `php artisan boost:install --guidelines
--no-interaction` to refresh the generated `CLAUDE.md` / `AGENTS.md`.
