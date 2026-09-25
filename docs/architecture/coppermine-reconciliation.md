# Coppermine Migration Reconciliation

A migration is not considered successful merely because the import command completed.

`mediarama:import:coppermine:reconcile` compares the source and target and reports:

- source picture count
- persistent picture mappings
- mapped target MediaAssets
- collection/media links
- missing or unreadable Coppermine original files
- unmapped source picture IDs

The command returns a failure exit code when the migration is not clean.

Imported originals are copied into Mediarama storage and then dispatched through the same asynchronous `ProcessMedia` pipeline used by normal uploads. ExifTool extraction, image geometry and derivative generation therefore do not form a separate legacy-only processing path.

Reconciliation is intentionally separate from import so it can be rerun after filesystem repairs, retries or later migration stages.
