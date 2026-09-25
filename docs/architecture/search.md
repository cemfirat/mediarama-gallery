# Media Search

Status: foundation implementation

Mediarama does not read embedded metadata from media files during normal search.

Search uses PostgreSQL fields populated during ingestion and metadata extraction.

## Full text

A stored PostgreSQL `tsvector` combines:

- title
- description
- creator
- copyright
- camera make/model
- lens
- location
- original filename

A GIN index backs full-text queries.

The `simple` text-search configuration is intentionally language-neutral for the first release foundation because one Mediarama installation may contain multilingual collections.

## Structured filters

The query layer currently supports:

- creator
- camera make
- camera model
- lens
- minimum/maximum ISO
- capture date range
- presence/absence of GPS coordinates

More photographic filters can be added without re-reading the source file.

## API

`GET /api/media`

Examples of query parameters:

- `q`
- `creator`
- `camera_make`
- `camera_model`
- `lens`
- `iso_min`
- `iso_max`
- `captured_from`
- `captured_until`
- `has_location`
- `limit`
- `offset`

Only media in processing state `ready` are returned.

## Next steps

- collection/tag filters
- rating/label filters
- permission-aware visibility
- cursor pagination for large libraries
- faceting for camera/lens/date/location
- search integration tests with PostgreSQL
