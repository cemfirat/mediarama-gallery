# ADR-0002: Use PostgreSQL as the primary database

- Status: Accepted
- Date: 2026-09-25

## Context

Coppermine is MySQL-oriented and does not provide genuine vendor-independent persistence. Mediarama is a clean implementation, so the database can be selected for its target model rather than source compatibility.

The target model requires strong relational integrity and benefits from flexible structured metadata. Examples include media in multiple collections, normalized users/groups/tags/favorites, explicit permissions, derivative records, ingestion state, and extensible EXIF/IPTC/XMP metadata.

## Decision

PostgreSQL will be the primary and initially supported Mediarama relational database.

Use PostgreSQL features deliberately where they provide product value, especially:

- foreign keys and constraints;
- transactional migrations;
- JSONB for extensible media metadata;
- appropriate JSONB/expression indexes;
- full-text/search capabilities where sufficient;
- concurrency-safe quota/accounting operations.

The domain/application layers must not contain raw PostgreSQL-specific SQL. Vendor-specific persistence belongs in infrastructure/repository implementations.

Mediarama will **not** promise MySQL/MariaDB compatibility in v1. Database portability is not worth constraining the schema prematurely.

## Migration

The Coppermine importer will read MySQL/MariaDB source data and transform it into the PostgreSQL Mediarama model. Cross-engine migration is therefore an importer responsibility, not a reason to retain MySQL internally.

## Consequences

- PostgreSQL becomes an installation requirement for v1.
- JSONB can replace PHP-serialized/free-form legacy metadata safely.
- We can use database-enforced integrity rather than application conventions.
- A future second database backend remains possible but requires a separate decision and test matrix.
