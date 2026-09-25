# UIkit Frontend Foundation

Mediarama uses server-rendered Twig templates and UIkit as the bundled presentation system.

## Rules

- UIkit components before custom widgets.
- No layout tables.
- Templates receive prepared view data.
- No persistence or authorization logic in templates.
- Mobile navigation uses UIkit Offcanvas.
- Responsive layouts use UIkit Grid/Flex/Width utilities.
- Project-specific visual changes belong in LESS variables/partials rather than edits to vendor UIkit files.

## Asset build

The repository keeps UIkit as an npm dependency.

The production build must generate:

- `public/build/app.css`
- `public/build/uikit.min.js`
- `public/build/uikit-icons.min.js`

The initial shell is intentionally minimal. Gallery-specific components are added only when backed by actual application views.
