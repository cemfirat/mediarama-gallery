# Public Gallery

Status: foundation

The public gallery is a read model over Mediarama collections, media and generated derivatives. It does not expose storage paths or originals.

## Effective public visibility

A collection is publicly browseable only when:

- the collection is not deleted
- `visibility = public`
- it is not password-protected
- every ancestor collection satisfies the same conditions

This is evaluated through a recursive PostgreSQL CTE. A public child below a restricted or password-protected parent therefore remains non-public.

## Published media

Public collection pages only return media that are:

- not deleted
- processing state `ready`
- moderation state `published`

The initial gallery page returns up to 120 direct media items ordered by collection position and capture date.

## Derivatives

Public image delivery uses generated derivatives only.

URL shape:

`/media/{media-id}/derivatives/v{processing-version}/{profile}`

Allowed public profiles are currently:

- `thumbnail`
- `preview`
- `large`

Before a derivative is streamed, the media must still be reachable through at least one effectively public collection.

Derivative URLs include the processing version and can therefore use long-lived immutable caching. No public endpoint for the immutable original is introduced.

## Presentation

The public UI uses UIkit components for:

- collection card grid
- nested collection navigation
- responsive media grid
- image lightbox
- empty states
- mobile navigation

Video/audio currently render a placeholder in the public grid until their FFmpeg/FFprobe processing profiles exist.

## Next

- cursor pagination for large public collections
- media detail route and metadata panel
- tags/search UI with the same permission boundary
- video poster and rendition delivery
- configurable collection covers
- public/private sharing tests
- password access flow using modern password hashing
