# ADR-0006: Symfony 7.4 LTS, PHP 8.5 and Doctrine

- Status: Accepted
- Date: 2026-09-25

## Context

Mediarama needs a long-lived self-hostable foundation for a modular monolith with:

- server-rendered UIkit presentation;
- PostgreSQL;
- background workers;
- robust authorization;
- CLI/import tooling;
- storage abstraction;
- testable module boundaries.

As of 2026-09-25:

- PHP 8.5 is the current stable PHP line and receives active support through 2027 and security support through 2029.
- Symfony 8.1 is the current feature release but reaches end of support in January 2027.
- Symfony 7.4 is the current LTS release, with bug fixes through November 2028 and security fixes through November 2029.
- Laravel 13 is current and supported, but follows a yearly major-release cadence with a shorter framework security window than Symfony 7.4 LTS.

## Decision

Mediarama will use:

- **PHP 8.5** as the initial production/runtime baseline;
- **Symfony 7.4 LTS** as the application framework;
- **Twig** for server-rendered presentation;
- **Doctrine ORM 3.x / DBAL 4.x** for persistence;
- **Doctrine Migrations** for schema versioning;
- **Symfony Messenger** for commands/events/background jobs;
- **Doctrine/PostgreSQL Messenger transport initially**, avoiding a mandatory Redis service in the first deployment profile;
- **Flysystem 3** behind Mediarama's own storage interface for local and S3-compatible adapters;
- **Symfony Security** for authentication/authorization infrastructure.

## Why Symfony 7.4 instead of Symfony 8.1

Mediarama is starting a new long-lived product, but Symfony 8.1 is a short support release. Beginning on 7.4 LTS gives the foundation a substantially longer maintenance window while remaining compatible with PHP 8.5 and current Doctrine packages.

We can upgrade to a future Symfony LTS intentionally rather than forcing an early 8.1 → 8.2/8.x cadence during foundational development.

## Why Symfony instead of Laravel

Laravel 13 is capable of implementing the product and has excellent first-party developer ergonomics.

Symfony is selected because Mediarama benefits more from:

- explicit component boundaries;
- a strong DI/service model;
- framework-neutral domain code;
- mature Messenger abstraction with multiple transports;
- long LTS window;
- less pressure toward framework-specific Active Record-style application patterns;
- good fit for a modular-monolith architecture where infrastructure adapters remain replaceable.

This is not a claim that Laravel is technically incapable; it is a fit decision for this architecture.

## Persistence

Doctrine entities/repositories are infrastructure concerns. Domain behavior should not depend on Doctrine APIs where avoidable.

Use Doctrine for:

- mapping/persistence;
- transactions;
- DBAL access for specialized PostgreSQL queries;
- migrations.

Do not force every query through ORM entities. Read-heavy/search/report queries may use DBAL/query services when that is clearer and more efficient.

## Queue

Initial queue transport:

`doctrine://default`

Reasons:

- no mandatory extra broker for the first self-hosted deployment;
- PostgreSQL is already required;
- Symfony Messenger's Doctrine transport supports PostgreSQL LISTEN/NOTIFY;
- failed-message handling and retry tooling are built in.

The transport is an infrastructure choice. Redis, AMQP or SQS may be introduced later without changing application messages/handlers.

## Storage

Flysystem is used inside infrastructure adapters, but Mediarama code depends on its own `MediaStorage` contract rather than directly spreading Flysystem calls through the application.

## Frontend

Twig renders UIkit-based templates. Focused JavaScript handles upload queues, lightbox/viewer interactions and other progressive enhancements.

A large SPA framework is not part of the foundation.

## Consequences

- PHP 8.5 becomes a minimum requirement.
- Symfony 7.4 LTS becomes the supported framework line.
- PostgreSQL remains the database requirement.
- The first production profile can run with application + worker + PostgreSQL + storage, without Redis.
- Framework upgrade work is planned around LTS lifecycle rather than feature-release churn.
