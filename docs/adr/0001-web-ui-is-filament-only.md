# ADR 0001 — Web UI is a Filament admin panel, not an Inertia/Vue SPA

- Status: Accepted
- Date: 2026-09-01
- Deciders: product owner

## Context

The repo was scaffolded from a Laravel + Inertia + Vue starter kit (Fortify web
auth, `resources/js/pages/*`). The THOB specification, however, is API-first
(`/api/v1/`, versioned, Sanctum tokens) and the customer/seller experience it
describes is an explicitly mobile-first, RTL Arabic marketplace app. Open
Decision #9 ("scope of the web client") was left unresolved by the spec.

Phase 1 needed an admin surface for reviewing verification requests, and the
project already had Filament v5 installed with an `/admin` panel and
User/Role/Permission resources.

## Decision

The **only** web UI in this repository is a **Filament v5 admin/back-office
panel** at `/admin`.

- All admin, moderation, taxonomy, plan, dashboard and reporting screens are
  Filament Resources / Pages / Widgets.
- The marketplace app (buyers, sellers) is a **separate client** — mobile or
  otherwise — that consumes `/api/v1/`. This repo's obligation to it ends at the
  API.
- The starter kit's Inertia/Vue scaffold and Fortify **web** auth are legacy and
  to be removed; no new Inertia/Vue/React pages are built.
- Business logic stays in the module (Action / Service / Policy); Filament pages
  call into it (e.g. `DecideVerificationRequest` is shared by the REST
  controller and the Filament view page).
- Each module registers its Filament classes with the panel via an additional
  `discoverResources` path in `AdminPanelProvider`.

This resolves Open Decision #9 and supersedes the `inertia-laravel` guidance in
the root `CLAUDE.md` (Laravel Boost guidelines).

## Consequences

- One rendering stack for web (Livewire/Alpine), no Node runtime in production.
- Faster admin delivery — Phase 9 screens are Filament resources, not hand-built
  pages.
- The customer marketplace client is out of scope for this repo; its team
  integrates against the OpenAPI surface generated from routes + Form Requests +
  Resources.
- Follow-up: delete `resources/js`, the Inertia middleware/root view, Fortify
  web routes, and the `inertiajs/*` + Vue npm dependencies once no code depends
  on them.
