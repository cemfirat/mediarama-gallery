# ADR-0005: UIkit presentation boundary

- Status: Accepted
- Date: 2026-09-25

## Decision

UIkit is Mediarama's primary UI framework for public and administrative server-rendered interfaces.

Presentation must be separated from domain/application behavior:

```text
HTTP Controller
    ↓
Application Service / Query
    ↓
Authorization
    ↓
View Model
    ↓
Template / UIkit Components
```

Themes may alter templates, UIkit variables, LESS/CSS, presentational JavaScript and component composition.

Themes must not:

- query the database;
- implement authorization;
- process uploads;
- mutate domain state directly;
- depend on application globals.

Extensions are separate from themes.

HTML tables are reserved for genuinely tabular information, never page layout.

## Consequences

UIkit becomes an architectural dependency of the bundled UI, not a cosmetic layer over Coppermine markup. A future API/headless client can consume application services independently of UIkit.
