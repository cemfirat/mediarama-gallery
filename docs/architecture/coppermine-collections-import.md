# Coppermine Collections Import

Coppermine categories and albums are imported into the Mediarama collection tree.

## Mapping

- Coppermine category → Mediarama Collection used as a structural parent
- Coppermine album → Mediarama Collection containing media
- category.parent → collection.parent_id
- album.category → collection.parent_id
- title/name → title
- description → description
- pos → position
- positive album/category `thumb` PID → `cover_media_id`

Owner references are resolved through the persistent Coppermine user mapping when available.

## Covers

Coppermine stores a stable user-selected thumbnail as a positive picture ID in both `albums.thumb` and `categories.thumb`.

Those positive references are reconciled only after picture/media mappings exist and become `collections.cover_media_id`. Preflight rejects positive thumbnail IDs that do not resolve to a source picture, and cover reconciliation fails if the corresponding collection/media mapping is unexpectedly absent.

Dynamic source behavior is not frozen into arbitrary media:

- album `thumb < 0` means choose a random picture at display time;
- `thumb = 0` is the source's automatic/default choice (for category management it is labelled “last uploaded”).

These non-positive values leave `cover_media_id` null so Mediarama can use its own automatic cover behavior instead of turning a transient source choice into permanent data.

## Visibility

Coppermine album `visibility = 0` is imported as `public`.

Non-zero visibility values can represent group/user restrictions. They are imported conservatively as `restricted` first. A later ACL-conversion stage maps source groups/users into `collection_access`.

This intentionally avoids accidentally publishing a previously restricted album.

## Resumability

Categories and albums have independent persistent checkpoints.

Each imported source row gets a stable source→target UUID mapping. Rerunning the importer therefore updates/reuses the same target collection instead of duplicating it.

After category creation is complete, a reconciliation pass resolves parent-category relationships.

Before any migration writes begin, preflight rejects category rows whose non-zero parent does not exist and rejects category cycles. The reconciliation pass also fails if a child or parent mapping is unexpectedly missing, so hierarchy corruption cannot silently flatten a category.


## Virtual user galleries

Coppermine reserves category IDs from `FIRST_USER_CAT = 10000` upward for the per-user gallery namespace. Those values are not treated as missing normal category rows.

Albums in that namespace are imported as owned root collections in Mediarama. Normal non-zero category IDs below `FIRST_USER_CAT` must resolve to an imported category; otherwise migration stops instead of silently flattening the hierarchy.
