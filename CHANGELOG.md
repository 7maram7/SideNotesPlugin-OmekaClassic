# Changelog

All notable changes to the SideNotes plugin are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-06-24

### Added

- Dedicated Notes browse page with Item Notes / Collection Notes tabs.

- Sortable Created and Modified columns using Omeka's native sort controls.

- Recent-notes dashboard panels for Items and Collections.

- Author and timestamp tracking (created by / modified by, created / modified).

- Working Delete action on the browse page (POST + CSRF-protected, with confirmation).

- Configuration options: note preview length, dashboard note count, timestamp format.

### Changed

- Rebuilt the admin UI to match the native Omeka admin theme: native table styling,
  sortable headers, section-nav tabs, action-links, and recent-row markup on the
  dashboard and sidebar.

- Removed custom CSS and hardcoded colors so the plugin inherits the active admin theme.

- Configuration values are now validated and clamped server-side.

### Fixed

- Record titles containing & (and similar) no longer display double-escaped.

- Recent-notes dashboard query no longer fails from binding the LIMIT value as a string.

### Security

- Delete requires POST and a valid per-session CSRF token.

- Browse sorting uses a strict column/direction whitelist; queries are parameterized and output is escaped.

## [1.0.0] - 2025-07-25

### Added

- Initial release: private, staff-only notes attached to Items and Collections.

- Sidebar panel and edit-form textarea for viewing and editing notes.

- Dedicated database table; notes never appear on the public site.

- Automatic table creation on install and removal on uninstall.

[2.1.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.1.0

[1.0.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v1.0.0
