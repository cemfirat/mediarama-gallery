# ADR-0004: Separate processing state from editorial moderation

- Status: Accepted
- Date: 2026-09-25

## Decision

Mediarama will use asynchronous media processing and keep technical processing state separate from editorial moderation state.

Indicative processing states:

- created
- uploading
- uploaded
- validating
- processing
- ready
- invalid
- failed

Indicative editorial states:

- draft
- pending_review
- published
- rejected

The exact enum names may change during implementation without changing this architectural decision.

Heavy operations such as metadata extraction, image derivatives, video probing/transcoding, poster generation and search indexing run through background jobs rather than being coupled to the upload HTTP request.

## Consequences

- uploads can finish before expensive processing;
- failed processing is not confused with rejected moderation;
- jobs must be idempotent/retry-safe;
- abandoned uploads and orphaned objects require cleanup;
- queue/worker infrastructure becomes part of the application foundation.
