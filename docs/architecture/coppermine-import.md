# Coppermine Import

Status: foundation

Mediarama treats Coppermine as a migration source, not as its runtime architecture.

## Import principles

- source database is read-only
- source media files are never mutated
- first operation is a dry-run/schema inspection
- source IDs are mapped to target UUIDs
- import operations must be resumable and idempotent
- ambiguous records produce warnings rather than silent data loss
- counts are reconciled after import

## Initial mappings

| Coppermine | Mediarama |
| --- | --- |
| pictures | media_assets |
| albums | collections |
| users | users |
| usergroups | groups / permissions |
| comments | comments |
| votes | ratings |
| keywords | tags |
| serialized EXIF | embedded metadata snapshot + canonical metadata |
| album visibility | collection access policy |

## Version detection

The first inspector recognizes a 1.6-compatible schema and detects known 1.7-era picture fields such as `mime` / `ftype`.

Version detection will remain capability-based rather than trusting a version string alone.

## Dry run

The inspector reports:

- detected source generation
- row counts for important tables
- missing expected tables
- future compatibility warnings

No target writes happen during inspection.

## Next implementation stage

- source configuration isolated from the Mediarama PostgreSQL connection
- source→target mapping persistence
- albums/collections import
- picture/media import
- physical file reconciliation
- users/groups/ACL conversion
- comments/ratings/tags
- metadata conversion
- resumable checkpoints
- final reconciliation report
