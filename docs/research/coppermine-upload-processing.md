# Coppermine Upload & Media Processing Analysis

Status: **verified discovery**
Date: 2026-09-25

This document maps Coppermine 1.6.x upload and processing behavior into requirements for the Mediarama ingestion architecture.

## Executive summary

Coppermine has accumulated a surprisingly capable upload system:

- pluggable upload UIs;
- HTML5 upload;
- chunked upload;
- legacy single-file and SWF upload paths;
- batch-add of files already present on disk;
- per-group upload settings;
- album-level upload permissions;
- file-size limits;
- image dimension limits and optional auto-resize;
- storage quota checks;
- moderation/approval;
- EXIF orientation handling;
- IPTC import;
- thumbnails;
- intermediate images;
- watermarks;
- plugin hooks around upload and media insertion.

The important architectural weakness is that transport, validation, image processing, filesystem mutation, quota enforcement and database insertion are tightly coupled in synchronous PHP request flows.

Mediarama should preserve the capabilities but split the process into explicit stages.

---

## 1. Coppermine upload entry points

### `upload.php`

Acts primarily as the upload coordinator/UI entry point.

It:

- checks whether the current user can upload or create albums;
- determines albums the user can upload into;
- reads the configured upload mechanism;
- lets plugins register upload methods;
- dispatches upload form rendering and processing through plugin hooks;
- exposes maximum upload size;
- performs temporary edit-directory cleanup.

Upload methods are deliberately extensible through:

- `upload_options` filter;
- `upload_form` action;
- `upload_process` action.

This is a useful design principle to retain.

### Upload plugins

Current 1.6.x contains upload plugins including:

- `upload_h5a` — HTML5/AJAX uploader;
- `upload_sgl` — single-file fallback;
- `upload_swf` — legacy Flash/SWF uploader.

Mediarama should not preserve SWF compatibility, but the distinction between core ingestion and replaceable upload clients is valuable.

### `uniload.php`

Modern Coppermine 1.6 uses `uniload.php` as a common receiving/processing endpoint for multiple upload methods.

It performs substantial transport and validation work and ultimately feeds the media into the common picture-management pipeline.

### `upchunk.php`

Provides chunk handling for the HTML5 uploader.

Chunks are written into a temporary directory and later combined into the final upload file.

This permits files larger than normal PHP request limits by keeping individual chunks below request limits.

### `db_input.php`

Older/general form-processing endpoint. It performs file-size checks and invokes `add_picture()`.

### `searchnew.php` + `addpic.php`

Support batch-add of files already located in the configured albums filesystem.

`addpic.php`:

- is admin-only;
- receives an album ID and encoded filesystem path;
- sanitizes/renames the filename;
- checks for an existing record;
- invokes `add_picture()`.

This is an important migration/import use case, but Mediarama should model it as an explicit import source rather than as a special upload page.

---

## 2. Common processing core: `add_picture()`

The central processing function is in `include/picmgmt.inc.php`.

Conceptually it performs:

```text
source file
  ↓
known-type check
  ↓
image-specific metadata/orientation
  ↓
dimension enforcement / optional resize
  ↓
optional original backup
  ↓
thumbnail generation
  ↓
intermediate image generation
  ↓
optional watermarking
  ↓
size calculation
  ↓
quota check
  ↓
approval decision
  ↓
plugin filter
  ↓
database insert
  ↓
plugin success event
```

This sequence is one of the most important pieces of Coppermine product behavior to preserve semantically.

---

## 3. Type validation

Coppermine uses `is_known_filetype()`, which delegates to:

- `is_image()`;
- `is_movie()`;
- `is_audio()`;
- `is_document()`.

The upload path also explicitly validates filename extensions against configured allowed types.

### Weakness

The system is strongly extension-oriented. No use of `mime_content_type()` was found in the repository search performed for this analysis.

1.7's addition of `mime` and `ftype` to `pictures` points toward a better model but does not fundamentally redesign ingestion.

### Mediarama requirement

File acceptance must not trust the extension alone.

Validation should combine:

1. original filename/extension;
2. detected MIME type;
3. content signature / magic bytes;
4. media decoder/probe result;
5. administrator-defined allow policy.

For video/audio, a media probe such as FFprobe is a natural candidate.

---

## 4. File-size limits

Coppermine applies multiple limits:

- PHP `upload_max_filesize`;
- PHP `post_max_size`;
- global Coppermine `max_upl_size`;
- HTML5 uploader configuration;
- chunk-size configuration.

The HTML5 uploader can use chunking to upload a file larger than the ordinary single-request PHP limit.

### Mediarama requirement

Separate:

- maximum asset size;
- maximum HTTP request/chunk size;
- per-user/group storage quota;
- deployment/storage backend constraints.

These are different policies and should not share one ambiguous setting.

---

## 5. Chunked/resumable upload

Coppermine's HTML5 uploader:

- slices files client-side;
- writes chunks into a temporary server directory;
- tracks chunk numbers;
- combines chunks into the destination file;
- reports missing chunks/failures.

This is a proven requirement for large media.

### Mediarama direction

Chunked upload should be a first-class ingestion protocol, not an implementation detail of a UI plugin.

Prefer an upload-session model:

```text
UploadSession
- id
- user_id
- original_filename
- expected_size
- expected_type
- status
- expires_at

UploadPart
- session_id
- part_number
- byte_size
- checksum
```

For S3-compatible storage, multipart uploads can eventually be delegated directly to the object store.

This would also allow pause/resume and reliable recovery.

---

## 6. Album/collection authorization

Before upload, Coppermine queries albums according to:

- gallery-admin mode;
- album `uploads='YES'`;
- album visibility;
- current user's groups;
- album ownership;
- password state;
- user-gallery category.

This demonstrates that upload authorization is not merely a global permission.

### Mediarama requirement

Authorization must be checked against the destination Collection at commit time, not only when the upload UI is opened.

Suggested capability:

```text
collection.media.add
```

The server must re-evaluate it for every finalized upload/import.

---

## 7. Filename handling

Coppermine applies plugin filtering and `replace_forbidden()` to sanitize filenames.

Storage paths still incorporate filenames directly.

### Mediarama direction

Keep the original filename as metadata, but do not make it the storage identity.

Example:

```text
original_filename: IMG_0042.JPG
storage_key: media/01K.../original
```

Benefits:

- collision-free storage;
- Unicode filenames without filesystem assumptions;
- renaming does not move storage objects;
- safer URLs;
- easier S3/object-storage support.

---

## 8. Image orientation

Coppermine optionally reads EXIF Orientation and physically reorients the uploaded image.

It uses PHP EXIF when available and falls back to its own EXIF reader.

### Mediarama requirement

Normalize display orientation during processing while retaining original metadata.

The original asset should ideally remain immutable.

A normalized display derivative can be generated without destructively changing the original.

---

## 9. IPTC ingestion

If configured, Coppermine reads IPTC metadata.

When title, caption and keywords are all empty, it can populate them from:

- IPTC Headline;
- IPTC Caption;
- IPTC Keywords.

This is valuable gallery behavior and should be preserved.

### Mediarama improvement

Metadata import rules should be explicit and configurable:

```text
embedded title → title?
embedded description → description?
embedded keywords → tags?
creator/copyright → attribution fields?
GPS → retain / strip / private?
```

The ingestion system should record provenance so users can distinguish imported embedded metadata from manually edited values.

---

## 10. Image dimension enforcement

Coppermine compares uploaded image dimensions with `max_upl_width_height`.

Depending on configuration and user/admin state it may:

- resize the uploaded image;
- keep an oversized admin upload;
- reject/delete an oversized upload.

### Mediarama direction

Do not destructively resize the original merely to satisfy display limits.

Prefer:

- retain immutable original if policy allows;
- reject only if the source asset itself violates a hard policy;
- generate bounded derivatives for presentation.

A separate optional archival policy can discard originals when storage conservation is explicitly desired.

---

## 11. Derivatives

Coppermine generates:

### Thumbnail

A prefixed file in the same logical media directory.

### Intermediate image

Optional reduced-size display image.

### Original backup

When full-size watermarking is enabled, an `orig_` copy can be retained.

### Mediarama direction

Model derivatives explicitly.

```text
MediaDerivative
- id
- media_id
- kind
- storage_key
- mime_type
- width
- height
- byte_size
- processing_version
- created_at
```

Possible kinds:

- thumbnail;
- preview;
- display;
- responsive sizes;
- poster frame;
- web-optimized image;
- video rendition.

Do not encode derivative type through filename prefixes.

---

## 12. Watermarking

Coppermine can watermark:

- resized/intermediate images;
- originals;
- both.

When watermarking originals, it may preserve a backup copy.

### Mediarama direction

Watermarking should normally be a derivative transformation.

Avoid mutating the immutable source asset.

This makes it possible to:

- change watermark style later;
- regenerate;
- provide unwatermarked originals to authorized users;
- use different watermarks per collection/use case.

---

## 13. Quota enforcement

Coppermine calculates stored size including generated image variants and compares the total against the user's group quota for user galleries.

If exceeded, it removes the new files and aborts.

### Weakness

Quota calculation and media processing are interleaved. Work may already have been performed before quota failure.

### Mediarama direction

Use two quota stages:

1. **preflight reservation** based on incoming original size and expected processing overhead;
2. **final accounting** using actual persisted object sizes.

A reservation avoids parallel uploads racing past the quota.

---

## 14. Approval/moderation

Coppermine determines `approved=YES/NO` from:

- admin status;
- private/user gallery upload approval settings;
- public gallery upload approval settings.

The decision is stored directly on the picture.

### Mediarama direction

Use a moderation state rather than a YES/NO field.

For example:

```text
processing
pending_review
published
rejected
quarantined
failed
```

Processing state and editorial moderation state may ultimately deserve separate fields.

---

## 15. Plugin hooks

Relevant hooks found around upload include:

- `upload_options`
- `upload_form`
- `upload_process`
- `upload_file_name`
- `add_file_data`
- `add_file_data_success`

There are also upload-method-specific pre-move hooks.

### Mediarama direction

Preserve lifecycle extensibility, but define typed/stable events rather than passing arbitrary mutable PHP arrays.

Potential events:

```text
UploadSessionCreated
UploadCompleted
MediaValidationStarted
MediaValidated
MediaMetadataExtracted
MediaProcessingRequested
DerivativeCreated
MediaPersisted
MediaPendingReview
MediaPublished
MediaRejected
MediaDeleted
```

Not every event needs to be public/plugin API in v1.

---

## 16. Synchronous processing problem

Coppermine's common flow performs expensive work inside the request:

- metadata parsing;
- orientation;
- resizing;
- thumbnail generation;
- watermarking;
- filesystem work;
- quota calculation;
- DB insertion.

This is manageable for traditional photos but becomes problematic for:

- large RAW images;
- high-resolution images;
- long videos;
- video transcoding;
- many simultaneous uploads;
- remote/object storage.

### Mediarama requirement

Separate upload acceptance from heavy processing.

Recommended high-level flow:

```text
Browser / API
    ↓
Upload Session
    ↓
Temporary/Object Storage
    ↓
Finalize
    ↓
Security + Type Validation
    ↓
MediaAsset created: processing
    ↓
Job Queue
    ├── metadata extraction
    ├── image derivatives
    ├── video probe/transcode
    ├── poster frame
    ├── optional watermark
    └── search indexing
    ↓
Moderation decision
    ↓
published / pending_review / rejected
```

---

## 17. Failure and rollback

Coppermine frequently deletes generated/uploaded files directly when later checks fail.

There is no general transactional boundary spanning filesystem changes and database insertion.

### Mediarama direction

Use explicit ingestion states and idempotent cleanup.

Important properties:

- retry-safe jobs;
- orphan-object cleanup;
- database transaction for relational commit;
- deterministic derivative keys;
- processing attempts recorded;
- failed assets visible to authorized operators;
- scheduled cleanup for abandoned upload sessions.

Object storage and SQL cannot share one ordinary ACID transaction, so Mediarama must design compensation/cleanup deliberately.

---

## 18. Batch import

Coppermine can scan files already placed into its album directory and add them to the DB.

This remains important for Mediarama because existing galleries may contain large media trees that should not be uploaded again through a browser.

### Mediarama import sources

The architecture should eventually support:

- Coppermine installation import;
- local filesystem import;
- server-side directory import;
- S3-compatible bucket import;
- ZIP/archive import, if justified later.

All sources should feed the same ingestion pipeline after acquisition.

---

## 19. Video and audio

Coppermine recognizes movie/audio/document file types and can associate player information through `filetypes`.

But the image pipeline is substantially richer than the non-image pipeline.

### Mediarama requirement

Video must be first-class rather than merely "a non-image file".

At minimum model:

- duration;
- width/height;
- codec/container metadata;
- poster frame;
- streaming/download source;
- processing state.

Future transcoding can generate multiple renditions.

Audio can similarly expose duration and embedded metadata/artwork.

---

## 20. Security requirements derived from the analysis

The Mediarama ingestion boundary should enforce:

- authenticated/authorized destination;
- CSRF protection for browser flows;
- server-side size policy;
- content-based type detection;
- filename/path isolation;
- no executable uploads into a web-served code directory;
- image/media decoder isolation where practical;
- decompression/pixel limits;
- checksums;
- rate limiting;
- abandoned-session expiration;
- malware scanning integration point;
- audit trail for administrative imports.

This is stricter than Coppermine's legacy filesystem model and should be treated as a core architectural requirement.

---

## 21. Proposed Mediarama ingestion components

### Upload API

Responsible for:

- authorization;
- upload session creation;
- multipart/chunk coordination;
- finalization.

### Storage Adapter

Responsible for:

- temporary objects;
- originals;
- derivatives;
- local filesystem and S3-compatible implementations.

### Media Inspector

Responsible for:

- MIME/content detection;
- image dimensions;
- EXIF/IPTC/XMP;
- video/audio probe;
- checksum.

### Media Processor

Responsible for:

- orientation normalization;
- image derivatives;
- watermark derivatives;
- poster frames;
- future video transcoding.

### Ingestion Orchestrator

Responsible for:

- state transitions;
- queue dispatch;
- retries;
- quota reservation/accounting;
- moderation handoff.

### Import Adapters

Responsible for:

- Coppermine;
- filesystem;
- future external sources.

---

## 22. Initial state model

A possible technical ingestion state machine:

```text
created
  ↓
uploading
  ↓
uploaded
  ↓
validating
  ├──→ invalid
  ↓
processing
  ├──→ failed
  ↓
ready
```

Editorial state can remain separate:

```text
draft
pending_review
published
rejected
```

Keeping these separate prevents "not approved" from ambiguously meaning either moderation or technical processing failure.

---

## 23. Architectural conclusions

Research now strongly supports these Mediarama decisions:

1. Keep the useful Coppermine upload feature set, not its request architecture.
2. Make chunked/resumable upload first-class.
3. Separate transport from ingestion.
4. Keep originals immutable by default.
5. Represent derivatives in the database.
6. Detect media from content, not only extension.
7. Make video a first-class media type.
8. Move expensive processing to background jobs.
9. Separate technical processing state from moderation state.
10. Abstract storage from the application filesystem.
11. Make quota reservation concurrency-safe.
12. Feed browser upload and migration/import through the same post-acquisition pipeline.
13. Design cleanup/retry behavior explicitly.
14. Preserve extension points as stable lifecycle events.

---

## 24. Next research

The next analysis should cover the **presentation/theme and extension architecture** in detail:

- how Coppermine themes override rendering;
- where HTML and business logic remain coupled;
- plugin lifecycle and hooks;
- which UI concepts should survive;
- how a full UIkit Mediarama presentation layer can remain separate from domain/application code.

That analysis will let us define the target application layers before choosing the exact PHP framework and repository structure.
