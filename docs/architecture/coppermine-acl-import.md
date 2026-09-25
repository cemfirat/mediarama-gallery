# Coppermine Collection ACL Import

Coppermine album visibility is converted to explicit Mediarama collection access rules.

## Visibility mapping

Coppermine 1.6 defines `FIRST_USER_CAT = 10000` and uses album `visibility` values as follows:

- `0` → public
- group ID → visible to that group
- `10000 + user_id` → visible to that user

Mediarama converts restricted principals to `collection_access` rows with capability `collection.view`.

The collection owner also receives an explicit view rule.

## Password-protected albums

Coppermine validates album passwords using MD5 hashes.

Mediarama does **not** reuse those hashes.

Imported password-protected albums are therefore:

- forced to `restricted`
- marked `password_protected = true`
- imported without a usable password hash
- marked `password_reset_required = true`
- given the old password hint when present

An administrator must set a new password using Mediarama's modern password hashing before password-based public access can be enabled.

This is intentionally fail-closed: a previously protected album must never become public merely because its old MD5 password cannot be reused safely.

## Idempotency

Partial unique indexes prevent duplicate user/group access rules on repeated imports.
