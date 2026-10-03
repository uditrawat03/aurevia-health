# Technology Baseline

**Baseline date:** 2026-10-03

This document records the framework and runtime choices used when the repository was created. It is intended to make upgrades deliberate and traceable rather than to freeze dependencies permanently.

## Selected Baseline

| Technology | Repository baseline | Rationale |
|---|---|---|
| Angular | 22.x | Current active Angular major |
| Node.js | 24 LTS, minimum compatible line 24.15+ | Stable LTS line supported by Angular 22 |
| TypeScript | Angular-managed compatible 6.0.x | Follow Angular compatibility matrix |
| Angular testing | Vitest | Default for new Angular projects |
| Angular change detection | Zoneless | Default for Angular 21+ |
| Angular app model | Standalone | Default for new CLI applications |
| Laravel | 13.x | Current Laravel major |
| PHP | 8.5 recommended | Current PHP line supported by Laravel 13; Laravel 13 minimum is PHP 8.3 |
| Authentication | Laravel Sanctum | First-party SPA session/cookie approach |
| Queue operations | Laravel queues + Horizon | Redis-backed queue visibility |
| PostgreSQL | 18.x | Current stable PostgreSQL major while PostgreSQL 19 is still pre-GA at baseline date |
| Redis | 8.x | Current Redis major |
| Package generation | Official Laravel installer / Composer and Angular CLI | Avoid hand-maintained copies of framework skeletons |

## Angular Notes

At this baseline date:

- Angular 22 is actively supported.
- Angular 22.2 is the current scheduled minor line around September 2026.
- Angular 22 supports Node.js `^22.22.3`, `^24.15.0`, or `^26.0.0`.
- This repository recommends Node 24 LTS for stability.
- New Angular CLI applications use standalone APIs by default.
- Angular 21+ uses zoneless change detection by default.
- Vitest is the default test runner for newly created Angular CLI applications.

The bootstrap script uses:

```text
@angular/cli@22
```

rather than pinning a specific patch, allowing a new repository bootstrap to receive the latest tested patch in the Angular 22 major line. The generated lock file should then be committed.

Official references:

- https://angular.dev/reference/releases
- https://angular.dev/reference/versions
- https://angular.dev/cli/new
- https://angular.dev/guide/testing
- https://angular.dev/guide/zoneless

## Laravel Notes

At this baseline date:

- Laravel 13 was released March 17, 2026.
- Laravel 13 supports PHP 8.3–8.5.
- Laravel 13 security fixes are scheduled through March 17, 2028.
- Laravel's documented installation flow uses the official installer or Composer-created application.
- Laravel Sanctum supports first-party SPA authentication with session cookies and CSRF protection.
- Laravel Horizon operates Redis-backed Laravel queues.

The bootstrap script uses:

```text
composer create-project laravel/laravel:^13.0 apps/api
```

and then:

```text
php artisan install:api
composer require laravel/horizon
php artisan horizon:install
```

Official references:

- https://laravel.com/docs/13.x/releases
- https://laravel.com/docs/13.x/installation
- https://laravel.com/docs/13.x/sanctum
- https://laravel.com/docs/13.x/horizon

## PostgreSQL Notes

PostgreSQL 19 was still in beta at this baseline date. PostgreSQL 18 is therefore selected rather than using a pre-GA database major for the starter.

The local Docker configuration tracks the PostgreSQL 18 image line:

```text
postgres:18-alpine
```

Before production, pin the chosen image version/digest under the organization's supply-chain policy.

Official reference:

- https://www.postgresql.org/about/news/

## Upgrade Policy

Patch/minor upgrades should be kept current through dependency automation and CI.

A major framework/runtime upgrade requires:

1. compatibility review;
2. migration/upgrade guide review;
3. test-suite pass;
4. security review;
5. performance smoke test;
6. update to this document;
7. ADR if the upgrade changes architecture or runtime behavior.

Do not upgrade core framework majors directly in production without passing the milestone/release gates defined in `MILESTONES_TESTING.md`.
