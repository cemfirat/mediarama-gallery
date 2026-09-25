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

Owner references are resolved through the persistent Coppermine user mapping when available.

## Visibility

Coppermine album `visibility = 0` is imported as `public`.

Non-zero visibility values can represent group/user restrictions. They are imported conservatively as `restricted` first. A later ACL-conversion stage maps source groups/users into `collection_access`.

This intentionally avoids accidentally publishing a previously restricted album.

## Resumability

Categories and albums have independent persistent checkpoints.

Each imported source row gets a stable source→target UUID mapping. Rerunning the importer therefore updates/reuses the same target collection instead of duplicating it.

After category creation is complete, a reconciliation pass resolves parent-category relationships.


## Virtual user galleries

Coppermine reserves category IDs from `FIRST_USER_CAT = 10000` upward for the per-user gallery namespace. Those values are not treated as missing normal category rows.

Albums in that namespace are imported as owned root collections in Mediarama. Normal non-zero category IDs below `FIRST_USER_CAT` must resolve to an imported category; otherwise migration stops instead of silently flattening the hierarchy.
