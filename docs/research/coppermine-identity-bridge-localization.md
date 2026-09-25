# Coppermine identity, bridging, bans and localization audit

Status: **active audit**
Date: 2026-09-25
Tracking: #11

## External identity bridging

Coppermine contains a formal bridge subsystem and bridge manager.

Current 1.6 bridge adapters include:

- MyBB
- Phorum
- phpBB3
- SMF 2.0
- SMF 2.1
- vBulletin 3
- XMB
- XOOPS
- Coppermine/local UDB base infrastructure

The bridge manager stores settings in the `bridge` key/value table and validates items such as:

- application/config paths;
- external URL/path assumptions;
- cookie prefixes;
- mandatory settings.

Source and language text also confirm recovery behavior for disabling a failed bridge using the standalone Coppermine administrator identity.

### Migration implication

A bridged gallery may not own the authoritative user credential store.

Therefore a Mediarama importer must not blindly treat every Coppermine user row as a complete portable identity.

Preflight should detect bridge configuration and require one of:

- explicit standalone/local identity migration;
- external identity mapping strategy;
- migration to a modern identity provider;
- documented loss/disablement of bridge-derived login.

This is an architecture/migration blocker until the bridge semantics are fully classified.

## Category-scoped album creation

The previously missed `categorymap` table maps `cid + group_id` and is actively used for authorization.

It controls which groups may create/manage public albums in specific categories and participates in album/category editing/deletion decisions.

This is **not the same thing as album view visibility**.

Mediarama therefore needs to distinguish at least:

- view collection;
- add media to collection;
- create collection under parent;
- edit/manage collection;
- possibly moderate/manage media in collection.

The current imported `collection.view` rules do not cover this source capability.

## Group semantics

Beyond the capabilities already mapped, Coppermine user groups persist:

- storage quota;
- public upload approval requirement;
- private upload approval requirement;
- access level;
- e-card capability.

These are not decorative values. They affect runtime behavior.

Mediarama must classify each:

- quota → likely migrate to quota policy;
- upload approval → moderation policy;
- access level → research exact semantics before mapping;
- e-card capability → likely obsolete if e-cards are intentionally omitted.

## Bans and brute-force state

The `banned` table can store:

- user ID;
- username;
- email;
- IP address;
- expiry;
- brute-force state.

Login code also updates brute-force ban state/expiry.

### Mediarama implication

Do not bulk-import network bans until the security/privacy design is settled.

However, silently dropping a deliberate active account ban may reactivate a user who was intentionally blocked.

The final migration policy must distinguish:

- account-level bans worth preserving;
- temporary brute-force lockouts that should not migrate;
- IP bans whose privacy/security value must be evaluated;
- expired bans that can be ignored.

## Language architecture

Coppermine has both language files and a `languages` database table containing:

- language ID;
- English/native/custom name;
- flag;
- abbreviation;
- availability;
- enabled state;
- completeness.

The language manager reconciles installed language files with DB definitions and lets administrators enable/disable languages and choose a default.

Runtime language selection can be rendered as a dropdown or flags.

English acts as a practical fallback source when a requested language variable is absent.

### Mediarama implication

Do not migrate Coppermine PHP language files.

Useful configuration semantics to preserve or reconsider:

- default locale;
- enabled locales;
- installation-specific display labels;
- fallback locale.

Mediarama should use a modern translation catalog system and locale negotiation rather than Coppermine-compatible PHP language globals.

## Remaining identity audit

Still required:

- exact profile custom-field semantics;
- registration approval/activation flows;
- password/hash transition history;
- login-by-username/email configuration;
- anonymous/guest group behavior;
- access-level semantics;
- bridge group mapping;
- registration/profile restrictions while bridged;
- how plugin hooks can override authorization (`authorize_user`).
