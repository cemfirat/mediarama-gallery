# Coppermine Identity Import

Coppermine users and groups are migrated conservatively.

## Passwords

Legacy Coppermine password hashes are **not** copied into Mediarama authentication.

Imported active users receive status `password_reset_required` and no usable Mediarama password hash.

This avoids making Mediarama's authentication layer depend on historical hash algorithms, salts or iteration settings. A later product flow can issue password-reset invitations or support a one-time migration login if explicitly designed and audited.

Inactive source users remain inactive.

## Groups and permissions

Coppermine group capabilities map to stable Mediarama permission keys:

- admin access → `system.admin`
- upload pictures → `media.upload`
- rate pictures → `media.rate`
- post comments → `media.comment`
- create albums → `collection.create`

Primary and additional Coppermine group memberships are preserved in `user_groups`.

## Scope

This stage migrates global group capability. Album/category-specific access is handled separately through the Collection ACL migration.
