# ADR-0009: Make public SEO deliberate and independent from filtering

- Status: Accepted
- Date: 2026-09-26

## Context

Mediarama can expose many views of the same media:

- MediaAsset pages;
- manual Collections;
- Smart Collections;
- search results;
- faceted filters;
- tag/date/camera/location combinations.

If every query/filter combination becomes indexable, the application can create a very large number of low-value or duplicate public URLs.

Metadata can also contain information that should not automatically become public, such as exact GPS coordinates or private embedded fields.

SEO therefore cannot be implemented as “index every reachable gallery URL”.

## Decision

SEO is a first-class capability of the public content/routing model.

Indexability is **deliberate**.

### Indexable content

A public MediaAsset or deliberately published Collection may have:

- stable canonical URL;
- SEO title;
- meta description;
- Open Graph metadata;
- social preview;
- structured data;
- sitemap inclusion.

This applies equally to manual and Smart Collections.

### Non-indexable views by default

The following are not independently indexable by default:

- ad-hoc search URLs;
- temporary filters;
- faceted combinations;
- sorting/pagination variants;
- preview/admin URLs;
- private or restricted content.

They should use an appropriate combination of canonicalization and `noindex`.

A filter becomes an indexable public page only when it is saved/published as a Collection with stable identity and deliberate SEO state.

## Canonical identity

The canonical URL belongs to the MediaAsset or Collection identity, not to the current query string or computed result set.

Changing a Smart Collection rule must not generate a new canonical identity automatically.

## Structured data

Where appropriate, Mediarama may emit schema.org structures such as:

- `ImageObject`;
- `VideoObject`;
- `CollectionPage`;
- `BreadcrumbList`.

Structured data must be generated from intentionally public Mediarama fields, not by dumping embedded metadata.

## Sitemaps

Public indexable content may enter:

- normal XML sitemap;
- image sitemap;
- video sitemap where applicable.

Restricted/private content must never leak through sitemap generation.

## Metadata privacy boundary

SEO output must never automatically expose:

- exact GPS coordinates;
- private EXIF/IPTC/XMP values;
- source filesystem paths;
- internal IDs not intended for public use;
- hidden/restricted collection information;
- source-migration diagnostics.

Location text may be public only when it is part of the deliberately public Mediarama content model.

## Defaults

The product should prefer safe defaults:

- public manual Collection: eligible for indexing;
- public Smart Collection: not indexable until deliberately published/index-enabled;
- ad-hoc filter/search: noindex;
- restricted/private content: noindex and excluded from sitemaps;
- missing SEO title/description: use deterministic content-derived fallbacks.

This avoids “SEO by accidental URL generation”.

## Consequences

- Smart Collections can support SEO without creating an index explosion;
- public URLs stay stable while dynamic membership changes;
- metadata remains useful without becoming a privacy leak;
- SEO logic belongs to public routing/rendering, not to Coppermine migration behavior;
- sitemap and structured-data tests become part of authorization/privacy testing.

## Related

- GitHub issue #13 — integrated SEO, social metadata and media discovery
- GitHub issue #14 — Smart Collections from metadata and saved rules
- ADR-0008 — manual and metadata-driven Smart Collections
