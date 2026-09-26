# Mediarama source provenance and Coppermine reuse audit

Status: **history-aware technical provenance audit automated; final project-license governance decision still open**
Date: 2026-09-26
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


## Full-history marker audit

The current-tree search is now backed by a repeatable Git-history control.

CI checks out the repository with `fetch-depth: 0` and runs `.github/scripts/audit-coppermine-provenance-history.sh`. The script scans the patch history of application and test code across every commit reachable from the PR head and fails if it finds implementation-specific Coppermine markers such as:

- `IN_COPPERMINE`;
- `cpg_db_query(`;
- `CPGPluginAPI::`;
- `template_eval(`;
- `cpg_die(`;
- `get_pic_url(`;
- `cpg_get_type(`;
- `cpg_get_alb_keyword`;
- `cpgSanitize`;
- Coppermine copyright/project signatures.

Research documentation, CI plumbing and the deliberately synthetic Coppermine SQL fixtures are excluded from this mechanical scan because those areas intentionally contain source names/schema facts.

A marker scan is not a mathematical proof that no code was ever semantically rewritten or renamed. It is a reproducible historical guard against the most likely accidental copy/paste paths and complements the architectural/manual review in this document.

## Coppermine names in application source

Mediarama application code does contain source-format names where required for migration compatibility, for example:

- `alb_password`
- source table/column names;
- `FIRST_USER_CAT = 10000`;
- Coppermine entity/table identifiers.

These are interoperability/schema facts used to read a Coppermine database.

They are not evidence by themselves of copied Coppermine runtime implementation.

## Synthetic migration fixtures

`tests/Fixtures/Coppermine/1.6-minimal.sql` and `1.7-variation.sql` are explicitly documented as hand-assembled interoperability fixtures based on verified upstream schema/configuration facts. They are not source database dumps and do not carry upstream comments or bundled gallery content.

The fixture directory now carries its own provenance README and each SQL file identifies the upstream model reference and license context.

Binary media is not kept as provenance-ambiguous repository content:

- JPEG fixtures are generated during CI with ImageMagick and receive synthetic EXIF/XMP/IPTC values via ExifTool;
- MP3 and MP4 fixtures are generated during CI with FFmpeg;
- no Coppermine sample photos, theme graphics, icons or fonts are bundled.

A future real/anonymized gallery fixture requires its own documented permission, source, anonymization process and retained-data scope before commit.

No production runtime code depends on a copied Coppermine SQL schema file.

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


Both upstream reference lines checked for this audit carry the GNU GPL v3 license text:

- Coppermine 1.6.x: `LICENSE.txt`;
- Coppermine 1.7.x: `LICENSE`.

Keeping Mediarama at `GPL-3.0-or-later` is therefore license-compatible with a future deliberate GPL-compatible reuse path, but compatibility does not remove the need to preserve upstream copyright/provenance notices if actual upstream code is ever copied or adapted.

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

- keep the automated full-history marker audit green and manually review any future similarity/provenance exception;
- inspect any future real-gallery fixture and its documented permission/anonymization record;
- inspect future importer code added from upstream examples;
- keep generated/bundled asset provenance explicit if repository assets are added later;
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

The automated history marker audit adds evidence that obvious upstream implementation code was not temporarily committed and later removed from application/test paths.

This is not yet the final licensing exit gate because:

- marker-based history review cannot by itself exclude heavily rewritten/renamed derivation;
- future real/anonymized fixtures remain provenance-sensitive by definition;
- the project owner has not yet made a deliberately documented final license choice.

Issue #11 should remain open.


## Dependency and generated-asset boundary

Mediarama does not vendor Composer or npm dependency source into the repository: `vendor/`, `node_modules/` and generated `public/build/` are ignored.

UIkit, Symfony, Doctrine, Flysystem and build/test packages remain separately licensed dependencies resolved by their package managers. Their licenses are not reclassified as Mediarama-authored source merely because CI installs them or copies generated UIkit build artifacts into the ignored build directory.

At the current audited tree, the only repository binary media were the two synthetic MP3/MP4 test fixtures. They are removed by the next fixture-provenance change and generated in CI instead.
