# Coppermine → Mediarama migration loss matrix

Status: **provisional — gaps intentionally visible**
Date: 2026-09-25
Tracking: #8, #11

This matrix exists to prevent the importer from being declared complete merely because albums, pictures, users, comments and ratings migrate successfully.

The current Coppermine 1.6/1.7 core schema contains **22 core tables**, plus plugin-owned data may exist outside the core schema.

Each source concept must end in one of these states:

- **Direct** — preserve essentially equivalent data/semantics;
- **Transform** — preserve meaning in a different Mediarama model;
- **Historical** — preserve only aggregate/audit/history where individual semantics cannot or should not be reconstructed;
- **Intentional omission** — explicitly do not migrate, with rationale;
- **Blocking gap** — unresolved; architecture/import cannot be signed off yet.

## Core schema matrix

| Coppermine source | Meaning | Current Mediarama status | Classification | Remaining work |
| --- | --- | --- | --- | --- |
| `albums` | album metadata, ownership, visibility, password settings | importer exists | Transform | finish edge cases, real-gallery tests |
| `banned` | user/name/email/IP bans, expiry, brute-force flag | non-empty table is rejected by preflight | Unsupported / block | do not silently drop security state; migrate only after a Mediarama ban model is deliberately designed |
| `bridge` | external-application bridge configuration | not migrated | Blocking gap | determine identity-authority migration strategy |
| `categories` | hierarchy, ownership, structural grouping | importer exists | Transform | nested/user-gallery real tests |
| `categorymap` | groups allowed to create albums in categories | currently not migrated | Blocking gap | map to collection/category creation capability/policy |
| `comments` | comments, author, moderation/spam | importer exists | Transform | real guest/moderation tests; historical IP deliberately excluded |
| `config` | large installation-wide behavior/config surface | only selected values read | Transform | build explicit allowlist of migratable behavior; never bulk-copy |
| `dict` | keyword dictionary | not migrated | likely Intentional omission / rebuild | confirm dictionary is derived and can be regenerated from tags |
| `ecards` | sent e-card history including sender/recipient emails and sender IP | not migrated | unresolved, likely Intentional omission | privacy/legal/product decision; do not import by default without reason |
| `exif` | persisted EXIF blob per picture | source file metadata currently re-extracted instead | Transform | compare stored EXIF vs source-file metadata; preserve source-only fields if necessary |
| `favpics` | authenticated-user favorites serialized in source | not migrated | Blocking gap | decode safely, resolve picture/user IDs, import to normalized favorites |
| `filetypes` | extension→MIME→content type→player registry | not migrated | Transform | distinguish built-in defaults from site customizations; map relevant custom media policies |
| `hit_stats` | detailed views incl. IP, search phrase, referrer, browser, OS, user | not migrated | unresolved, likely Historical or Intentional omission | decide aggregate preservation vs privacy-safe discard |
| `languages` | installed/available/enabled language definitions | not migrated | likely Transform / configuration | map only installation language preferences, not runtime implementation |
| `pictures` | media records, metadata, ownership, approval, counters | importer exists | Transform | non-image/custom type and metadata-heavy real tests |
| `plugins` | installed plugin registry, enablement and priority | registry itself is not migrated; any installed row blocks preflight | Intentional omission as runtime registry + plugin-data blocker | audit/waive each installed plugin and any plugin-owned files/tables before core migration |
| `sessions` | active login sessions | not migrated | Intentional omission | document security rationale; never migrate sessions |
| `temp_messages` | transient cross-page messages | not migrated | Intentional omission | document as ephemeral |
| `usergroups` | global capabilities, quotas and approval flags | partially migrated | Transform | quota + approval semantics still incomplete |
| `users` | local identities, profiles, activation/status | importer exists | Transform | custom profiles, bridged users, bans, activation edge cases |
| `votes` | basic per-voter anti-repeat records without rating value | not reconstructed individually | Historical / omit detail | aggregate/detailed vote strategy already documented |
| `vote_stats` | detailed ratings + IP/referrer/browser/OS/user | user-linked rating values partly migrated | Transform + privacy reduction | retain rating value/identity when recoverable; deliberately omit network/client telemetry |

## Important non-table behavior/data

### Anonymous favorites

Coppermine can persist favorites in a client cookie as a serialized/base64-encoded PID list.

These are not part of the server-side `favpics` table for an anonymous visitor. A server migration cannot reliably transfer a browser-local favorite list without a separate client-side migration mechanism.

Current classification: **intentional limitation unless a practical opt-in migration workflow is designed**.

### Album/category keyword semantics

Coppermine can associate an album keyword with media whose picture keyword string matches that value, meaning some album-like membership can be dynamic rather than represented solely by `pictures.aid`.

Current importer primarily maps native album ownership through `aid`.

Classification: **Blocking gap until the exact album-keyword semantics and migration strategy are tested**.

### Plugin-owned data

The core `plugins` table stores only plugin registry information (name/path/enabled/priority).

Installed plugins can execute install/uninstall/configuration code and may maintain their own files, configuration or database tables. Therefore:

> A core-schema-only importer can never claim arbitrary Coppermine installation completeness without first inventorying installed plugins.

Required migration behavior:

1. enumerate installed plugins;
2. identify plugin-specific persisted data;
3. classify each plugin as supported migration / custom migration / intentional omission / blocker;
4. warn before migration when unknown plugin data may be lost.

### Custom file types

Coppermine's `filetypes` registry is not just a hard-coded list.

Current 1.6 defaults contain **107 extensions**:

- 13 image
- 11 movie
- 7 audio
- 76 document

1.7 adds three defaults:

- `webp`
- `webm`
- `weba`

The table is site data/configuration, so an installation can differ from defaults.

Mediarama must not infer import support merely from filename extension. It should continue content inspection, but the migration audit must preserve awareness of source custom file-type policy and unsupported media.

## Category creation rights: newly identified gap

`categorymap` is actively used in 1.6 to determine which groups may create/manage albums in specific categories.

It participates in:

- album manager category availability;
- category manager group assignment;
- user public-album creation capability;
- edit/delete authorization.

This is semantically different from album visibility.

The current Mediarama ACL importer covers **view access**, but not this category-scoped **creation/management capability**.

This gap must be resolved before permission migration is considered complete.

## User-group semantics not yet fully migrated

The current importer maps major global capabilities, but Coppermine groups also include:

- `group_quota`;
- public-upload approval requirement;
- private-upload approval requirement;
- access level;
- e-card capability.

These require explicit mapping/classification. In particular, quota and approval requirements affect ingestion/moderation behavior and cannot be silently discarded.

## Privacy-sensitive historical tables

Several Coppermine tables carry historical personal/network data:

- e-card sender/recipient email + sender IP;
- hit statistics IP/referrer/browser/OS/search phrase;
- vote statistics IP/referrer/browser/OS;
- comments historical raw/header IP;
- picture upload/last-hit IP fields;
- bans by IP/email/name.

Mediarama should not bulk-copy these fields merely for parity.

For each one, the final migration policy must document:

- product value;
- legal/privacy value;
- retention necessity;
- whether an aggregate is sufficient;
- whether the field should be intentionally discarded.

## Exit condition

Issue #8 cannot be treated as migration-complete until every row in this matrix has a final classification and all **Blocking gap** entries are resolved or explicitly accepted as unsupported with preflight failure/warning behavior.


## Album visibility/password-specific migration risks

The deeper access-control audit adds several migration rules that are not represented by the basic albums table mapping alone.

### Visibility integer must be decoded, not copied

Coppermine `albums.visibility` mixes principal types:

- `0` public;
- ordinary group IDs;
- `FIRST_USER_CAT + user_id` user/private visibility.

Mediarama must transform these to explicit resource ACLs and fail closed when a principal cannot be mapped.

### Password-bearing albums

A source album password is an alternate access mechanism for an otherwise restricted album.

Migration classification: **Transform**.

Required target behavior:

- preserve the fact that password protection existed;
- preserve/review the hint;
- do not reuse the MD5 source password hash;
- do not import browser unlock cookies;
- keep the target restricted until a new password/policy is set.

The existing ACL importer already follows this direction by requiring a password reset/replacement.

### Global private-album switch

`allow_private_albums` changes effective runtime behavior. Coppermine only builds/enforces the private-album forbidden set when this switch is enabled; its own administration text also warns that disabling the switch makes existing private albums visible.

Migration policy: **fail closed on conflicting stored intent**.

Preflight now requires `allow_private_albums` to be present with value `0` or `1`. When it is `0`, migration is blocked if any album still carries non-zero `visibility` or an album password. That state has two competing truths — effective Coppermine behavior was public, while stored album metadata still expresses restricted intent — so Mediarama does not guess which one should win.

### Moderator-group residue

`albums.moderator_group` remains in schema/runtime code, but current 1.6 disables normal persistence of the feature and the updater resets values to zero.

Migration classification: **Blocking review when non-zero**.

Preflight must count non-zero rows. A non-zero value may indicate:

- historical residue;
- an old installation not fully updated;
- plugin/custom code that re-enabled the feature.

Do not grant Mediarama moderator rights from this field automatically.

## Identity preflight gap: duplicate emails

Coppermine can allow duplicate email addresses.

Mediarama's current users table enforces unique non-null `CITEXT` email.

Current identity import therefore has a production migration blocker for source galleries containing duplicate non-empty emails.

Required preflight:

- duplicate normalized email count;
- invalid emails;
- empty emails;
- duplicate-email policy decision before insert.

Migration must not silently rewrite user emails without a reportable rule.


## Explicit preflight policy for security and extensions

### Ban table

Coppermine's `banned` table is active security state, not harmless history. It can represent user, name, email or IP bans with expiry plus brute-force lockout records.

Mediarama currently has no equivalent ban/expiry model. A non-empty source table therefore blocks migration before identity writes. Preflight reports only counts by manual/brute-force type and does not echo source email/IP values into logs.

This is an **unsupported-and-block** classification, not an intentional discard.

### Installed plugins

The core `plugins` table records plugin name/path/enabled state/priority, but Coppermine plugins can maintain separate tables, files and configuration. Persisted data can remain after a plugin is disabled.

The registry itself is not imported into Mediarama runtime state. Any installed plugin row instead blocks core migration until that plugin has been audited or explicitly waived.
