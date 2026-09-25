# Coppermine Theme & Plugin Architecture Analysis

Status: **verified discovery**
Date: 2026-09-25

This document evaluates Coppermine's presentation and extension architecture as input to Mediarama's UIkit-first design.

## Executive summary

Coppermine does have a meaningful separation mechanism:

- a top-level `template.html`;
- theme-specific CSS/assets;
- overridable `theme_*()` functions;
- token-based HTML templates;
- action/filter plugin hooks;
- install/configure/uninstall plugin lifecycle;
- plugin execution priority.

That is better than treating the application as one undifferentiated PHP codebase.

However, the separation is incomplete.

Theme PHP frequently contains:

- business rules;
- authorization decisions;
- SQL-aware/global application state;
- URL construction;
- menu logic;
- HTML templates;
- plugin calls.

Core application files also directly emit HTML and define presentation helpers.

Therefore Mediarama should preserve **themeability and extensibility**, but not Coppermine's function-override implementation.

---

## 1. Top-level page template

Coppermine defines:

```php
define('TEMPLATE_FILE', 'template.html');
```

The Curve theme's `template.html` contains page-shell tokens including:

- `{TITLE}`
- `{META}`
- `{JAVASCRIPT}`
- `{CUSTOM_HEADER}`
- `{GAL_NAME}`
- `{GAL_DESCRIPTION}`
- `{SYS_MENU}`
- `{SUB_MENU}`
- `{ADMIN_MENU}`
- `{MESSAGE_BLOCK}`
- `{GALLERY}`
- `{CUSTOM_FOOTER}`
- `{CREDITS}`

This provides a primitive layout/view boundary.

### Positive lesson

A theme should own the outer page composition.

### Limitation

The token system is bespoke and the rendered blocks themselves are often assembled by PHP functions containing application logic.

---

## 2. Theme overrides

Core defaults live in `include/themes.inc.php`.

Individual themes can define corresponding `theme_*()` functions to override presentation.

Examples include:

- JavaScript head;
- menus;
- breadcrumbs;
- thumbnail rendering;
- picture rendering;
- image navigation;
- rating box;
- comments;
- message boxes.

This is effectively a function-level override system.

### Positive lesson

Coppermine recognized that gallery components need more than CSS customization.

### Problem

The override boundary is not a clean view-model boundary.

For example, theme functions access globals such as:

- `$CONFIG`;
- `$CURRENT_PIC_DATA`;
- `$CURRENT_ALBUM_DATA`;
- `$USER_DATA`;
- current album/category state;
- language globals.

Some theme code also makes authorization decisions and database queries.

Therefore a theme can become tightly coupled to internals.

---

## 3. HTML inside PHP

Curve's `theme.php` defines large heredoc HTML fragments for:

- system menu;
- submenu;
- admin menu;
- buttons;
- dropdown structures;
- gallery elements.

Other application files also emit forms/tables directly.

This explains the user's original observation: **programming and design are not truly separated**.

The presence of `template.html` does not change the fact that substantial presentation markup lives inside executable PHP.

---

## 4. Table-based UI

Coppermine's historical UI uses many HTML tables for forms and layout components.

Some newer shell elements use `div` and list structures, but table markup remains widespread in both core pages and plugin configuration UIs.

### Mediarama requirement

UIkit should not be layered on top of these templates.

Mediarama should render semantic modern HTML directly with UIkit components.

Examples:

- `uk-container`
- `uk-grid`
- `uk-card`
- `uk-navbar`
- `uk-dropdown`
- `uk-offcanvas`
- `uk-modal`
- `uk-form-*`
- `uk-table` only for genuinely tabular data
- `uk-notification`
- `uk-pagination`
- `uk-lightbox`

No layout tables.

---

## 5. Proposed Mediarama presentation boundary

The application should hand the view a prepared view model.

Bad pattern to avoid:

```text
template
  → queries database
  → evaluates permissions
  → reads global application state
  → constructs domain rules
```

Preferred pattern:

```text
HTTP Controller
  ↓
Application Service / Query
  ↓
Authorization
  ↓
View Model
  ↓
Template / UIkit Components
```

A template may decide presentation details, but not whether a user fundamentally has permission to access an asset.

---

## 6. UIkit integration

UIkit should be the primary component system, not merely a stylesheet included beside legacy CSS.

### Base layer

Mediarama can maintain:

- UIkit source/custom build or standard distribution;
- project `custom.less`;
- design tokens/variables;
- minimal Mediarama component additions only where UIkit lacks the needed gallery behavior.

### Theme layer

A Mediarama theme should be able to control:

- layout shell;
- typography;
- spacing;
- color variables;
- gallery grid/list presentation;
- collection cards;
- media viewer presentation;
- navigation composition;
- optional component templates.

It should **not** own:

- SQL;
- permission logic;
- upload processing;
- storage;
- domain mutations.

---

## 7. Gallery components worth formalizing

Rather than exposing dozens of arbitrary PHP override functions, Mediarama should define explicit presentation components.

Initial candidates:

- AppShell
- PublicHeader
- UserNavigation
- AdminNavigation
- Breadcrumbs
- CollectionGrid
- CollectionCard
- MediaGrid
- MediaCard
- MediaViewer
- MediaMetadata
- MediaNavigation
- Filmstrip
- TagList
- SearchForm
- SearchResults
- Pagination
- CommentThread
- RatingControl
- FavoriteControl
- UploadQueue
- ModerationQueue
- EmptyState
- FlashMessage

These map well to UIkit and can be tested independently.

---

## 8. Responsive design

Responsive behavior should be a first-class design requirement.

Coppermine carries historical markup and compatibility workarounds that make responsive modernization harder.

Mediarama should define layouts from the beginning for:

- phone;
- tablet;
- desktop;
- wide media displays.

Media grids should use responsive UIkit widths and/or dynamic gallery logic without fixed table cells.

---

## 9. Media viewer

Coppermine's display page combines:

- image/media;
- previous/next navigation;
- rating;
- metadata;
- comments;
- filmstrip.

The product concept is good.

Mediarama should retain the integrated viewer concept while making its composition modular.

Potential desktop composition:

```text
┌─────────────────────────────────────────────────┐
│ breadcrumb / collection                         │
├─────────────────────────────────────────────────┤
│                                                 │
│                media viewer                     │
│                                                 │
├─────────────────────────────────────────────────┤
│ prev     filmstrip / position             next  │
├───────────────────────┬─────────────────────────┤
│ title / description   │ metadata / actions      │
├───────────────────────┴─────────────────────────┤
│ comments / activity                             │
└─────────────────────────────────────────────────┘
```

Mobile should collapse this naturally rather than emulate desktop tables.

---

## 10. Plugin architecture

Coppermine's plugin API is based on:

- plugin objects;
- registered actions;
- registered filters;
- enabled state;
- execution priority;
- filesystem plugin path;
- install/configure/uninstall callbacks;
- wake/sleep lifecycle.

This resembles hook systems familiar from other PHP applications.

### Strengths

- simple mental model;
- broad extension surface;
- execution ordering;
- plugins can transform values via filters;
- plugins can react to events via actions;
- explicit lifecycle.

### Weaknesses

- global mutable state;
- arbitrary function names;
- runtime includes from plugin directories;
- hooks pass loosely typed values;
- filters can mutate important application structures unpredictably;
- plugins can become deeply coupled to implementation details;
- presentation and domain hooks share the same loose mechanism.

---

## 11. Mediarama extension strategy

Do not begin by recreating a fully open plugin marketplace.

First make the application internally modular and define stable extension seams.

### Internal events

Use typed domain/application events.

Examples:

- `MediaUploaded`
- `MediaProcessed`
- `MediaPublished`
- `CommentCreated`
- `CollectionCreated`

### Presentation extension points

Use named UI slots/components where needed:

- media actions;
- collection actions;
- admin navigation;
- metadata panel;
- dashboard widgets.

### Installable extensions

Only formalize third-party installable plugins after:

- core module boundaries stabilize;
- security model is defined;
- version compatibility is defined;
- extension permissions/capabilities are defined.

This avoids freezing a premature API.

---

## 12. Plugin lifecycle worth preserving conceptually

Coppermine's:

- install;
- configure;
- enable/disable;
- priority;
- uninstall;

are all useful concepts.

A future Mediarama extension manifest could describe:

```text
name
package
version
mediarama_version_constraint
capabilities
event_subscribers
routes
migrations
settings_schema
ui_slots
```

But arbitrary extension code must be treated as trusted server code unless a sandboxed model is introduced.

---

## 13. Themes versus extensions

Mediarama should explicitly distinguish:

### Theme

Presentation only:

- templates;
- UIkit variable overrides;
- CSS/LESS;
- presentational JS;
- component composition.

### Extension

Application capability:

- routes;
- event subscribers;
- migrations;
- services;
- background jobs;
- settings.

A theme should never be the mechanism used to add domain behavior.

This is a key improvement over Coppermine's blurred boundary.

---

## 14. Admin UI

Coppermine's admin navigation exposes useful functional groupings:

- upload approval;
- categories;
- albums;
- picture manager;
- batch add;
- admin tools;
- comments;
- logs/statistics;
- plugin manager;
- user/group manager;
- configuration.

Mediarama can retain these product concepts but reorganize them around the new domain.

Potential Mediarama admin IA:

```text
Library
- Media
- Collections
- Tags
- Imports
- Uploads

Moderation
- Pending media
- Comments
- Reports

People
- Users
- Groups
- Permissions

System
- Storage
- Media processing
- Extensions
- Appearance
- Settings
- Diagnostics
```

This is a design hypothesis, not a locked sitemap.

---

## 15. Public navigation

Coppermine includes concepts such as:

- home;
- album list;
- latest uploads;
- latest comments;
- most viewed;
- top rated;
- favorites;
- search;
- user gallery;
- profile/login/logout.

These should be treated as feature requirements, not copied navigation labels.

Mediarama can expose them based on site configuration and enabled features.

---

## 16. Architecture conclusion

The user's original requirement — **separate programming and design completely** — should become an explicit Mediarama architecture principle.

Recommended boundary:

```text
Domain
  no UIkit, no HTTP, no templates

Application
  use cases, authorization, queries, commands

Infrastructure
  PostgreSQL, storage, queue, image/video tooling

HTTP
  routes/controllers/API

Presentation
  templates + UIkit components + view models

Theme
  presentation overrides only

Extensions
  explicit services/events/slots, separate from themes
```

This is the direction that makes a UIkit-first gallery maintainable rather than turning UIkit into another Coppermine theme.

---

## 17. Consequence for fork vs clean implementation

This analysis further weakens the case for a permanent direct Coppermine fork.

A direct fork would inherit exactly the coupling Mediarama intends to remove:

- global state;
- theme functions with domain knowledge;
- HTML in PHP;
- filesystem assumptions;
- loosely typed plugin mutation;
- legacy URL/controller structure.

Coppermine remains extremely valuable as:

- feature specification;
- behavioral reference;
- migration source;
- compatibility test source.

The emerging recommendation is therefore:

**Mediarama should be a clean implementation informed by Coppermine, with a dedicated Coppermine importer, rather than a long-lived fork of the Coppermine runtime.**

Licensing still needs to be respected for any code that is actually copied or adapted.

---

## 18. Next step

The research is now sufficient to begin formal Architecture Decision Records for:

1. fork vs clean implementation;
2. database engine;
3. storage abstraction;
4. application layering/framework;
5. UIkit/theme boundary;
6. background jobs/media processing;
7. extension strategy.

Before locking the PHP framework, current framework/runtime options should be evaluated against Mediarama's actual requirements rather than selected by familiarity alone.
