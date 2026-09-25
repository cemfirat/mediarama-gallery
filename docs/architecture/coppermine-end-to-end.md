# End-to-end Coppermine Migration

The complete migration is available through:

`php bin/console mediarama:import:coppermine`

The runner executes stages in dependency order:

1. inspect the source schema
2. import groups and users
3. import categories and albums
4. import pictures/original files
5. dispatch normal Mediarama media processing
6. import keywords/tags
7. convert album visibility/ACL
8. import comments and recoverable ratings
9. reconcile source rows, target rows and source files

A row is created in `import_runs` before writes begin. The current stage and failure message are stored in its JSON progress document; a clean run is marked `completed`.

The lower-level stage commands remain available for diagnostics and repair.

## Current pre-beta limitation

The original schema already contained `import_runs` / `import_id_map`, while the first resumable implementation introduced source-scoped `import_mappings` / `import_checkpoints`.

The staged runner records audit runs, while current resumability still uses the source-scoped mapping/checkpoint tables. This is intentional only for the pre-release implementation and must be consolidated into one run-scoped persistence model before beta so multiple independent Coppermine sources cannot collide.
