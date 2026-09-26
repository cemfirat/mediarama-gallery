# Resumable Upload Protocol

Status: foundation implementation

## Flow

1. `POST /api/uploads` creates a session.
2. Client splits the asset into chunks.
3. `PUT /api/uploads/{id}/chunks/{index}` uploads each chunk.
4. `GET /api/uploads/{id}` returns accepted chunks so an interrupted client can resume.
5. `POST /api/uploads/{id}/complete` validates continuity and assembles the temporary object.
6. `POST /api/uploads/{id}/finalize` re-authorizes the destination, detects actual content/MIME, computes SHA-256, applies the upload allow policy, structurally validates the media with ImageMagick (images) or FFprobe (audio/video), and only then promotes the immutable original, creates the MediaAsset and dispatches background processing.

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

## Structural media validation

MIME detection is not treated as decoder validation.

After the MIME/type allow policy and expected-size check pass, Mediarama validates the temporary object before finalization begins:

- images must decode far enough for the hardened ImageMagick geometry inspector to return valid dimensions;
- audio must contain an audio stream recognized by FFprobe;
- video must contain a video stream recognized by FFprobe.

FFprobe runs through an argv-only process with a parent timeout plus bounded probe size and analyze duration. With the current local-storage adapter the validator probes the already assembled temporary file in place; it does not duplicate a potentially multi-gigabyte upload merely to validate it. A structural validation failure leaves the upload session in `uploaded`, keeps the temporary object retryable, and prevents immutable-original promotion, `MediaAsset` creation and background dispatch.


## Finalization idempotency and crash recovery

For media created by the resumable upload pipeline, the MediaAsset UUID is the UploadSession UUID. The immutable original therefore has one deterministic target key for the lifetime of the session.

Finalization uses two short PostgreSQL critical sections backed by `SELECT ... FOR UPDATE` on the upload-session row:

1. after MIME/decoder/probe validation, claim `uploaded -> finalizing`;
2. after filesystem promotion, persist the MediaAsset, finalization mapping, completed session state and processing enqueue exactly once.

ImageMagick/FFprobe work and filesystem promotion remain outside the row lock.

The `finalizing` state is recoverable. A retry uses the temporary object when it still exists, or the deterministic permanent object when a previous request already promoted it. Local promotion is idempotent and re-checks the target after a concurrent rename race.

Before committing, the promoted object's size and SHA-256 must still match the validated content.

The current deployment uses Symfony's Doctrine Messenger transport on the same PostgreSQL connection. CI must verify that queue insertion participates in the final database transaction; a future non-Doctrine transport requires an explicit outbox rather than assuming cross-system atomicity.

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
- production authentication, persistent quotas and richer sharing remain separate hardening work.
