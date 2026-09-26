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

All Mediarama ImageMagick subprocesses use the same finite resource envelope before an input image is read.

Application-level limits are configured through:

- `IMAGEMAGICK_LIMIT_MEMORY`
- `IMAGEMAGICK_LIMIT_MAP`
- `IMAGEMAGICK_LIMIT_DISK`
- `IMAGEMAGICK_LIMIT_AREA`
- `IMAGEMAGICK_LIMIT_WIDTH`
- `IMAGEMAGICK_LIMIT_HEIGHT`
- `IMAGEMAGICK_LIMIT_FILES`
- `IMAGEMAGICK_LIMIT_THREADS`
- `IMAGEMAGICK_LIMIT_TIME_SECONDS`
- `IMAGEMAGICK_LIMIT_LIST_LENGTH`
- `IMAGEMAGICK_PROCESS_TIMEOUT_SECONDS`

The command-line limits are prepended to both geometry inspection and derivative generation, so they are active before ImageMagick reads the untrusted source. `area` accepts either a finite cache-byte value (for example `512MiB`) or a pixel-area value supported by ImageMagick (for example `64MP`). Sequence length is requested through `MAGICK_LIST_LENGTH_LIMIT` where supported; the deployment policy remains the authoritative ceiling for builds that do not expose that environment control.

The Symfony Process timeout remains a second hard stop around the ImageMagick resource-time limit. This is intentional: ImageMagick resource limits primarily control its own pixel-cache/runtime resources, while the parent process must still be able to terminate a command that does not return.

Production deployments should also install a restrictive ImageMagick `policy.xml` as an upper ceiling. A reviewed example is kept at `config/imagemagick/policy.xml.example`. ImageMagick policy limits cannot be relaxed by a larger command-line value. Version-specific policy keys must only be enabled after checking the deployed ImageMagick version; for example `max-memory-request` is an ImageMagick 7 feature.

The defaults are deliberately finite but are deployment settings rather than universal hardware recommendations. Operators may tighten them for smaller workers or raise them after measurement for unusually large professional images. Width/height, disk and elapsed-time limits must remain finite for Internet-facing installations.

CI exercises the actual ImageMagick binaries with a valid image and with an image that exceeds a deliberately small width limit. The oversized image must fail through both the identify and conversion paths.

References:

- ImageMagick command-line resource limits: https://imagemagick.org/command-line-options/#limit
- ImageMagick security policy: https://imagemagick.org/security-policy/
- ImageMagick legacy/6.x resource model: https://legacy.imagemagick.org/script/resources.php/

## Failure behavior

Derivative failure must leave the MediaAsset in a failed processing state and must not publish a partially processed asset.

A retry with the same processing version is safe because completed profiles are detected and skipped.
