# Resumable Upload Protocol

Status: foundation implementation

## Flow

1. `POST /api/uploads` creates a session.
2. Client splits the asset into chunks.
3. `PUT /api/uploads/{id}/chunks/{index}` uploads each chunk.
4. `GET /api/uploads/{id}` returns accepted chunks so an interrupted client can resume.
5. `POST /api/uploads/{id}/complete` validates continuity and assembles the temporary object.
6. `POST /api/uploads/{id}/finalize` re-authorizes the destination, detects actual content/MIME, computes SHA-256, promotes the immutable original, creates the MediaAsset and dispatches background processing.

## Chunk headers

- `Content-Length`
- `Upload-Offset`
- `Upload-Checksum-SHA256`

Chunk identity is the session UUID plus zero-based chunk index.

## Integrity

Every chunk is SHA-256 verified before acceptance.

Completion verifies:

- contiguous offsets;
- all chunk files exist;
- assembled byte count equals the session's expected asset size.

Finalization then computes the full-file SHA-256 and detects MIME from file content.

## Resume

Clients query session status and only resend missing chunks.

Writing an existing chunk index replaces that chunk only after the replacement passes size/checksum verification.

## Authorization

Destination authorization is checked when the session is created and again at finalization.

Collection owners may upload to their own collection. Group permission `collection.media.add` grants upload capability according to the current foundation ACL model.

The ACL model will become resource-scoped as collection sharing rules are expanded; a global permission must not become the final sharing model.

## Current authentication boundary

The HTTP foundation currently uses `X-Mediarama-User` as an explicit temporary actor transport.

This is **not** production authentication. It exists so the upload application protocol can be implemented and tested independently. Symfony Security/session/token authentication must replace it before public deployment.

## Limits

Default example configuration:

- maximum asset: 2 GiB;
- maximum chunk: 16 MiB.

These are deployment policy values, not hard-coded product limits.

## Remaining hardening

- persistent quota reservations/accounting;
- expired-session cleanup command/job;
- production authentication;
- resource-scoped collection ACL/sharing;
- integration tests over HTTP + PostgreSQL + filesystem;
- conflict/idempotency behavior for concurrent finalize requests.
