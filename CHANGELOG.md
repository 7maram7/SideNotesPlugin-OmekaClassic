# Changelog

All notable changes to the SideNotes plugin are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-08-13

### Added
- **Pagination** on the Notes browse page, following the site's admin
  "results per page" setting. Only the records on the current page are
  resolved, so large note sets load quickly.
- **Identifier column** showing each record's Dublin Core Identifier — useful
  when many records share the same title.
- **Batch delete**: per-row checkboxes, a select-all checkbox in the header,
  and a "Delete Selected" action bar.

### Fixed
- **Deleting a note redirected to a 404 page.** `url()` already includes the
  admin base path and the redirector prepended it again, producing
  `/admin/admin/side-notes/...`. The redirect now returns to the notes list,
  preserving the current tab, sort order, and page.

## [2.1.0] - 2026-06-24

### Added
- Dedicated **Notes** browse page with Item Notes / Collection Notes tabs.
- Sortable Created and Modified columns using Omeka's native sort controls.
- Recent-notes dashboard panels for Items and Collections.
- Author and timestamp tracking (created by / modified by, created / modified).
- Working **Delete** action on the browse page (POST + CSRF-protected, with confirmation).
- Configuration options: note preview length, dashboard note count, timestamp format.

### Changed
- Rebuilt the admin UI to match the native Omeka admin theme: native table styling,
  `th.sorting`/`asc`/`desc` sortable headers, `#section-nav` tabs, `action-links`,
  and `panel`/`recent-row` markup on the dashboard and sidebar.
- Removed custom CSS and hardcoded colors so the plugin inherits the active admin theme.
- Configuration values are now validated and clamped server-side.

### Fixed
- Record titles containing `&` (and similar) no longer display double-escaped (`&amp;`).
- Recent-notes dashboard query no longer fails from binding the `LIMIT` value as a string.

### Security
- Delete requires POST and a valid per-session CSRF token.
- Browse sorting uses a strict column/direction whitelist; queries are parameterized and output is escaped.

## [1.0.0] - 2025-07-25

### Added
- Initial release: private, staff-only notes attached to Items and Collections.
- Sidebar panel and edit-form textarea for viewing and editing notes.
- Dedicated database table; notes never appear on the public site.
- Automatic table creation on install and removal on uninstall.

[2.2.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.2.0
[2.1.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.1.0
[1.0.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v1.0.0
