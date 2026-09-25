# Mediarama

Mediarama is a modern photo and video gallery platform inspired by the proven feature set and workflows of [Coppermine Photo Gallery](https://coppermine-gallery.net/).

The project is **not intended to be a visual reskin of Coppermine**. The goal is to understand what Coppermine does well, compare its current development lines, identify architectural and technical legacy, and use those findings to design a maintainable, modern media gallery platform.

## Project status

**Phase: research and architecture**

No final decision has yet been made about forking Coppermine, the database engine, or the final application architecture. These decisions should follow a structured analysis rather than be assumed in advance.

## Vision

Mediarama should preserve the strengths that made Coppermine useful while removing technical constraints that accumulated over many years.

Core principles:

- clean separation of application logic, data access and presentation
- no table-based page layouts
- responsive, accessible and maintainable frontend
- UI built consistently with [UIkit](https://getuikit.com/)
- modern photo and video management
- clear frontend/backend separation
- extensible architecture for future media types and metadata
- secure-by-default implementation
- maintainable upgrade and migration paths
- avoid unnecessary rewrites of functionality that is already proven
- avoid carrying legacy architecture forward merely for compatibility

## Reference projects

The first research phase compares both active Coppermine codebases:

- [Coppermine 1.6.x](https://github.com/coppermine-gallery/cpg1.6.x)
- [Coppermine 1.7.x](https://github.com/coppermine-gallery/cpg1.7.x)

The comparison should cover at least:

- application architecture
- PHP/runtime requirements
- database schema and database access
- albums and categories
- media/file model
- image and video handling
- upload workflows
- metadata
- users, groups, permissions and privacy
- authentication and sessions
- comments, ratings and community features
- search
- thumbnails and image processing
- administration
- themes/templates
- plugins/hooks/extensions
- localization
- configuration
- caching and performance
- security model
- APIs and integrations
- installation and upgrades
- migration compatibility
- tests and development tooling
- known legacy dependencies

The purpose is not to assume that 1.7.x is automatically the correct foundation. Its actual architectural differences from 1.6.x must first be established.

## Frontend and design system

Mediarama should use **UIkit (getuikit)** as its primary UI framework.

The frontend should use semantic, responsive structures such as:

- Grid and Flex
- Cards
- Navbar and navigation components
- Modal
- Offcanvas
- Drop/Dropdown
- responsive media components
- modern forms
- notifications
- utility classes

Application logic must not generate presentation-specific table layouts.

UIkit should be integrated as a real design system rather than added as a cosmetic layer over legacy markup. Project-specific styling should remain customizable without requiring changes to core application logic.

## Architecture direction

A key architectural goal is separation of concerns.

Conceptually:

```text
Media / Domain
      |
Application Services
      |
Data Access
      |
Database

Presentation
      |
UIkit-based Views
```

The exact framework and implementation are intentionally not fixed yet. They should be selected after the Coppermine analysis and requirements inventory.

## Database evaluation

Coppermine's existing database model and MySQL dependencies will be analyzed before selecting Mediarama's persistence layer.

**PostgreSQL is a candidate, not yet a decision.**

The evaluation should include:

- relational integrity
- compatibility with the required media model
- metadata flexibility
- JSON/JSONB use cases
- full-text search
- indexing
- migrations
- performance
- operational complexity
- hosting requirements
- portability
- migration from existing Coppermine installations

If PostgreSQL offers meaningful architectural advantages, Mediarama may use it. Database abstraction should nevertheless be considered so application logic is not unnecessarily coupled to vendor-specific SQL.

## Fork vs. new foundation

A permanent fork of Coppermine is **not assumed**.

Three approaches must be evaluated:

1. **Direct fork**  
   Modernize Coppermine incrementally while retaining its codebase.

2. **Deep refactor**  
   Use Coppermine as the starting codebase but progressively replace major architectural layers.

3. **New Mediarama core with Coppermine compatibility/migration**  
   Treat Coppermine as the functional reference and migration source while implementing a clean architecture for Mediarama.

The decision should consider development effort, security, maintainability, technical debt, upgrade paths, licensing, migration requirements and the amount of Coppermine code that can realistically remain useful.

## Compatibility and migration

Existing Coppermine installations are important.

Even if Mediarama becomes a new implementation, the project should investigate migration of:

- albums and categories
- media files
- thumbnails/derivatives where useful
- users and groups
- permissions
- metadata
- comments
- ratings
- configuration where meaningful

A reliable migration path may provide more long-term value than maintaining source-level compatibility with Coppermine.

## Research before implementation

Before large implementation work begins:

1. Analyze Coppermine 1.6.x.
2. Analyze Coppermine 1.7.x.
3. Document their differences.
4. Inventory Coppermine's functional capabilities.
5. Identify technical debt and architectural constraints.
6. Define Mediarama's functional requirements.
7. Propose the Mediarama target architecture.
8. Evaluate database options.
9. Decide fork vs. refactor vs. new core.
10. Define migration strategy.
11. Convert the findings into GitHub milestones and actionable issues.

## GitHub project structure

After the research phase, development should be organized using:

- milestones
- focused issues
- labels
- architecture decision records (ADRs)
- dependency-aware implementation phases
- documented acceptance criteria
- tests and verification requirements

Large umbrella tasks should be split into independently verifiable work instead of creating an unstructured backlog.

## Initial roadmap

### Phase 0 — Discovery
Coppermine 1.6/1.7 analysis and feature inventory.

### Phase 1 — Architecture
Target architecture, technology decisions, database decision and ADRs.

### Phase 2 — Foundation
Mediarama application skeleton, development environment, database migrations, testing and CI.

### Phase 3 — Media core
Media library, albums/categories, uploads, metadata, image/video processing and permissions.

### Phase 4 — UI
Complete UIkit-based frontend and administration interface.

### Phase 5 — Extended features
Search, community functionality, extensibility, localization and integrations.

### Phase 6 — Migration
Coppermine importer, migration validation and compatibility documentation.

### Phase 7 — Production readiness
Security review, performance, accessibility, upgrade process, documentation and release preparation.

## Current next step

The immediate task is a **deep technical comparison of Coppermine 1.6.x and 1.7.x**.

The findings will determine Mediarama's architecture and will be converted into professional GitHub milestones, issues and architecture decisions before substantial implementation begins.
