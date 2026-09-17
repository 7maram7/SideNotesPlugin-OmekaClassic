# Changelog

All notable changes to the SideNotes plugin are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.2] - 2026-09-17

### Fixed
- **Row actions looked inconsistent on mobile.** Below 768px the admin theme
  restyles browse-page actions as filled blue buttons (`.browse .edit`), which
  framed one of the three actions while the other two -- rendered as reset
  `<button>` elements -- stayed plain links. All three (Edit, View, Delete) are
  now forced to identical plain-link styling at every width, scoped by
  `#side-notes` so it outranks the theme rule without `!important`.

## [2.3.1] - 2026-09-17

### Fixed
- **The Notes browse page was unusable on phones.** The seven-column table was
  forced into a ~390px viewport by `table-layout: fixed`, collapsing headers to
  one letter per line ("A c t i o n s") and squeezing every cell to a few
  characters wide.
- The fixed column widths are now scoped to viewports 768px and wider. Below
  that, each note renders as a labelled card: the record title leads, each
  value is captioned with its column name, and the row actions sit on one line.
  The card reuses the admin theme's borders and colours, so it still reads as
  Omeka. "Delete Selected" goes full width on mobile using the theme's own
  `full-width-mobile` class.

## [2.3.0] - 2026-09-03

### Added
- **Notes are available through the Omeka API** at `/api/side_notes`, so
  scripts and external tools can read and write them. Supports `index`, `get`,
  `post`, `put` and `delete`.
- `POST /api/side_notes` is an **upsert**: send `record_type`, `record_id` and
  `note`, and the note is created, or replaced if that record already has one.
  Callers never need to check first. `created` and `created_by_user_id` are
  preserved across an overwrite so the audit trail survives.
- `SideNote` record, `Table_SideNote` table and `Api_SideNote` adapter classes.
  These map to the existing `side_notes` table — no schema change, no
  duplicated data, and the admin screens are unaffected.
- `?record_type=` and `?record_id=` filters on the API index action.

### Security
- Notes declare an ACL resource and are denied to everyone by default, then
  granted to the `super` and `admin` roles.
- **Omeka's API index action performs no per-record permission check** — it
  returns whatever the table query yields (core hides private items the same
  way, by filtering inside the table). `Table_SideNote::applySearchFilters`
  therefore enforces the check itself and fails closed, so anonymous callers
  get an empty list rather than the contents of every note.
- Verified against the live site: unauthenticated `GET` by id, `POST` and
  `DELETE` all return `403`, and an unauthenticated listing returns `[]`.

## [2.2.0] - 2026-08-13

### Added
- **Pagination** on the Notes browse page, following the site's admin
  "results per page" setting. Only the records on the current page are
  resolved, so large note sets load quickly.
- **Identifier column** showing each record's Dublin Core Identifier — useful
  when many records share the same title.
- **Batch delete**: per-row checkboxes, a select-all checkbox in the header,
  and a "Delete Selected" action bar.
- **Inline editing**: an "Edit" action on each row turns the Note cell into a
  textarea with Save/Cancel, so notes can be corrected from the browse page
  without opening the Item or Collection. The editor shows the full note text,
  not the truncated preview. Saving an empty note deletes it, matching the
  note field on the record edit form.

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

[2.3.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.3.0
[2.2.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.2.0
[2.1.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v2.1.0
[1.0.0]: https://github.com/7maram7/SideNotesPlugin-OmekaClassic/releases/tag/v1.0.0
