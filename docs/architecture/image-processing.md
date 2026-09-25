# Image Processing

Status: foundation implementation

## Principle

The uploaded source is immutable.

All display images are generated derivatives with an explicit profile and processing version.

## Engine

The first image adapter uses ImageMagick through Symfony Process.

ImageMagick is infrastructure only; application code depends on `ImageDerivativeGenerator`.

## Profiles

Initial product profiles:

- `thumbnail`: 480 × 480 maximum
- `preview`: 1600 × 1600 maximum
- `large`: 2560 × 2560 maximum

Profiles are fit-inside bounds and never upscale the source.

Default derivative format is WebP. This can become deployment/profile configuration later.

## Orientation

Derivatives use embedded orientation during decoding and are physically normalized with auto-orient.

The generated derivative therefore does not require the browser to interpret the source orientation metadata.

## Metadata

Display derivatives are stripped of embedded source metadata by default.

Canonical metadata remains in PostgreSQL and the immutable source retains its original embedded metadata.

Metadata-bearing downloads are generated separately through the export pipeline.

This avoids accidentally publishing GPS or other sensitive source metadata through thumbnails/previews.

## Idempotency

A derivative identity is:

`media_id + kind + profile + processing_version`

The worker skips a derivative already recorded for that identity.

Storage keys are deterministic:

`derivatives/{media-id}/v{version}/{profile}.{format}`

Changing processing behavior requires incrementing the processing version.

## Watermarks

The profile model already carries a watermark flag, but watermark rendering is not enabled until watermark asset/configuration semantics are defined.

Watermarked output remains a derivative and never modifies the source.

## Resource safety

The process adapter has a timeout.

Production hardening must additionally set ImageMagick resource policies for memory, map, disk, width, height and area. Those limits belong to deployment/runtime configuration and must be tested with malformed/decompression-bomb fixtures.

## Failure behavior

Derivative failure must leave the MediaAsset in a failed processing state and must not publish a partially processed asset.

A retry with the same processing version is safe because completed profiles are detected and skipped.
