# ADR-0001: Build Mediarama as a clean implementation

- Status: Accepted
- Date: 2026-09-25

## Context

Mediarama is inspired by Coppermine Photo Gallery, but its primary goals require architectural changes that cut across almost every Coppermine subsystem: separation of application and presentation logic, UIkit-first semantic markup, normalized persistence, storage abstraction, asynchronous media processing, explicit authorization, and a maintainable extension boundary.

Research in `docs/research/` found that Coppermine 1.7.x is not a new architecture. It retains the page-oriented PHP runtime, globals, SQL coupling, theme-function overrides and filesystem assumptions of the 1.6 line. Meanwhile 1.6.x is the actively maintained line and is therefore the better behavioral/migration reference.

## Decision

Mediarama will be implemented as a new application rather than as a permanent fork or progressive in-place refactor of Coppermine.

Coppermine will be used as:

- functional reference;
- behavioral reference;
- migration source;
- compatibility test fixture;
- source of product lessons.

Mediarama will provide a dedicated Coppermine importer.

No source code should be copied merely for convenience. Any copied/adapted GPL code must be deliberately identified and its licensing consequences reviewed.

## Consequences

Positive:

- architecture can match Mediarama requirements directly;
- UIkit is not constrained by legacy markup;
- database and storage can be redesigned cleanly;
- Coppermine migration can normalize legacy data;
- technical debt is not inherited wholesale.

Cost:

- more initial implementation work;
- compatibility must be implemented explicitly;
- Coppermine behavior must be inventoried carefully;
- importer quality becomes a core product requirement.
