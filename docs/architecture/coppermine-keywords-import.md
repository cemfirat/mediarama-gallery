# Coppermine Keywords Import

Coppermine stores picture keywords in a delimited string.

Mediarama reads the actual source `keyword_separator` configuration instead of assuming a comma or semicolon. This follows Coppermine's own keyword handling.

Keywords are:

1. split using the source separator
2. HTML-decoded and trimmed
3. de-duplicated per picture
4. converted to stable tag slugs
5. inserted into `tags`
6. linked through `media_tags` with source `coppermine`

The importer is idempotent at the tag and media/tag-link level.

Pictures without a persistent source→MediaAsset mapping are reported and make the command fail rather than silently discarding their keywords.
