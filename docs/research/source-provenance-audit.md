# Mediarama source provenance and Coppermine reuse audit

Status: **provisional — repository-level source audit completed, legal/final-license sign-off still open**
Date: 2026-09-25
Tracking: #11

## Purpose

Mediarama is intentionally a clean implementation informed by Coppermine.

This audit asks a different question from the functional research:

> Is there evidence that Mediarama application source has copied/adapted Coppermine implementation code, creating provenance/licensing obligations beyond ordinary reference/migration compatibility?

This is a technical provenance audit, not legal advice.

## Existing architectural decision

ADR-0001 already states:

- Mediarama is a new application;
- Coppermine is a functional/behavioral/migration reference;
- code should not be copied merely for convenience;
- copied/adapted GPL code must be deliberately identified and reviewed.

That remains the correct policy.

## Repository search performed

Targeted repository searches were run for Coppermine-specific implementation markers.

### No application-source hits found for

- `IN_COPPERMINE`
- `Coppermine Dev Team`
- `cpg_db_query(`
- `add_picture(`
- `template_eval(`
- `Inspekt`
- Coppermine-style `unserialize(` implementation
- Coppermine-style `md5(` implementation

Search hits for `cpg_` are limited to:

- migration configuration/table-prefix examples;
- migration fixtures;
- research documentation.

This is positive evidence that the current Mediarama `src/` implementation was not created by mechanically copying obvious Coppermine PHP functions/classes.

## Coppermine names in application source

Mediarama application code does contain source-format names where required for migration compatibility, for example:

- `alb_password`
- source table/column names;
- `FIRST_USER_CAT = 10000`;
- Coppermine entity/table identifiers.

These are interoperability/schema facts used to read a Coppermine database.

They are not evidence by themselves of copied Coppermine runtime implementation.

## Synthetic SQL fixtures

`tests/Fixtures/Coppermine/1.6-minimal.sql` reproduces a deliberately small subset of Coppermine table/column shape needed for integration tests.

`tests/Fixtures/Coppermine/1.7-variation.sql` adds the verified 1.7 `mime`/`ftype` variation.

These fixtures are compatibility material and closely reflect source schema facts.

Before final license sign-off, they should be treated explicitly as provenance-sensitive test fixtures:

- document that they are synthetic/minimal interoperability fixtures;
- keep only fields necessary for tests;
- avoid copying comments, seed data or implementation text unnecessarily;
- review whether an attribution/provenance notice is desirable.

No production runtime code should depend on a copied Coppermine SQL schema file.

## Research documentation

The research documents intentionally discuss Coppermine behavior, names, fields and code paths.

They contain paraphrased findings and short identifiers/snippets necessary to explain interoperability.

They should not become a repository for large copied source passages.

Current policy should remain:

- paraphrase behavior;
- name files/functions/fields as facts;
- quote only the minimum necessary;
- link/source the upstream project when appropriate.

## Current license state

The repository currently declares:

- `composer.json`: `GPL-3.0-or-later`
- root `LICENSE`: GPL-3.0-or-later declaration

Important: this license choice was made before this provenance audit was complete.

Because the current evidence supports a clean implementation, GPL is **not established here as technically mandatory merely because Coppermine was researched**.

The final project license is therefore still a project/governance decision unless a later audit finds copied/adapted GPL implementation code.

## LICENSE-file quality issue

The current root `LICENSE` starts with the GPL v3 heading and initial paragraph but does **not** contain the complete canonical GPLv3 license text; it points to the FSF for the remainder.

If GPL-3.0-or-later remains the final choice, replace this abbreviated file with the complete canonical GPLv3 text before the first public release.

Do not call the current file a complete license copy.

## Clean-room indicators in the current architecture

The following Mediarama structures are architecturally independent from Coppermine's runtime:

- Symfony/application-service architecture;
- PostgreSQL normalized target schema;
- UUIDv7 target identities;
- n:m Collection ↔ Media;
- Flysystem storage abstraction;
- immutable-original policy;
- asynchronous processing via Messenger;
- explicit derivative records;
- ExifTool metadata pipeline;
- typed domain/application classes;
- UIkit/Twig presentation boundary;
- explicit resource ACL model;
- staged MySQL/MariaDB → PostgreSQL importer.

These differences do not by themselves prove legal independence, but they are consistent with the documented clean-implementation strategy.

## Areas that still need provenance review

Before closing the licensing/provenance gate:

- inspect the full Git history for any temporarily copied Coppermine source later rewritten/deleted;
- inspect migration fixtures and any future real-gallery fixtures;
- inspect future importer code added from upstream examples;
- inspect any copied theme/UI assets;
- inspect bundled icons/images/fonts for third-party licenses;
- inspect any ported metadata/parser logic;
- keep third-party package licenses separate from Mediarama's own license.

## Rule for future Coppermine research

When a Coppermine behavior is useful:

1. document the observable requirement;
2. design the Mediarama model independently;
3. implement against that requirement;
4. write Mediarama-native tests;
5. do not paste upstream implementation unless deliberately choosing a derived GPL path and recording provenance.

## Recommended provenance record for deliberate reuse

If upstream code is ever deliberately adapted, create a record containing:

- upstream repository;
- exact path;
- upstream commit SHA;
- license;
- copied/adapted range;
- Mediarama destination;
- date;
- nature of modification;
- reason reuse was necessary.

That makes later license review auditable.

## Current conclusion

The targeted repository search finds **no obvious Coppermine runtime implementation copied into Mediarama application source**.

However this is not yet the final licensing exit gate because:

- full historical similarity review is still pending;
- fixtures/assets need final provenance classification;
- the project owner has not yet made a deliberately documented final license choice.

Issue #11 should remain open.
