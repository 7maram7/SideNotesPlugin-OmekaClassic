# Changelog

All notable changes to the SideNotes plugin are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.7] - 2026-09-18

### Changed
- **Search and pagination now share one row above the table** -- search on the
  left, pagination on the right -- closing the empty space left behind when the
  note count moved into the heading. Previously the search sat alone on the
  right with the pagination on a row of its own.
- On phones the row stacks: search across the full width, pagination centred
  beneath it.
- The top pager sits outside the region the live search swaps (so the search
  field is never replaced mid-typing), so the search refreshes it explicitly
  alongside the heading and the table.

## [2.4.6] - 2026-09-18

### Changed
- **The note count now sits in the heading, in parentheses**, matching Omeka's
  own browse pages: `Side Notes (389 total)`, the same shape as
  `Browse Items (6329 total)`. The separate "N notes total" line beneath the
  tabs is gone.
- While searching, the figure is the number of matches, so the heading doubles
  as the result count.
- The heading is refreshed alongside the table when a live search runs -- it
  sits outside the region the search swaps, so it would otherwise have shown a
  stale figure.

## [2.4.5] - 2026-09-18

### Changed
- The browse page heading (and browser tab title) now reads **"Side Notes"**
  rather than "Notes", matching the plugin's name. The left-hand admin
  navigation item is still labelled "Notes".

## [2.4.4] - 2026-09-18

### Fixed
- **"Delete Selected" had its label crushed against the borders.** 2.3.4 copied
  the theme's own `height: 25px` for batch-bar buttons, but the theme pairs
  that with no padding compensation: a `.small` button needs 28px (5px padding
  + 16px line-height + 5px padding + 2px border), so forcing 25px left a 13px
  content box for a 16px line. The height override is gone and the button now
  keeps the theme's genuine `.small` proportions. The bar still sits tight,
  because the fix for its excess height was clearing `margin-bottom`, not
  forcing a height.

## [2.4.3] - 2026-09-18

### Fixed
- **The search box sat on the left instead of the right.** The float was
  written against a `.side-notes-searchform` wrapper that had never been added
  to the markup, so it simply never applied. The field and its overlaid submit
  are now wrapped in that element, and the control is right-aligned above the
  table as intended.
- **The pagination arrow rendered ~10px above the page box.** The theme gives
  `.pagination li a` a `margin: 0 0 20px 0`; in a centred flex row it is the
  margin box that gets centred, so the visible button floated high. The bottom
  margin is now cleared, which is the last of the three separate causes of that
  crooked row (floated items, the theme's invalid `height: 38x`, and this).

## [2.4.2] - 2026-09-18

### Fixed
- **The space bar was sometimes swallowed while typing a search.** The box
  submitted the form on a timer, so a natural pause mid-phrase triggered a page
  reload and any keystrokes made while it was in flight were discarded.
  Searching now fetches results and swaps them in place, leaving the field
  untouched, so typing is never interrupted. Enter refreshes in place too. The
  form is still a plain GET, so it degrades without JavaScript.
- **Pagination sat crooked.** The theme floats the pagination list items, so
  nothing shared a centre line, and its own field rule reads `height: 38x` --
  invalid, so the page box fell back to the 36px base height and rode low
  between the 38px arrow buttons. The row is now flex-aligned and all three
  controls are 38px.
- **The search focus ring now wraps the whole control**, matching the admin
  header. Omeka's header search is a full-width field with the submit
  *absolutely positioned over it* and `padding-right` reserving its space --
  so the ring surrounds everything. A sibling button, as used before, left the
  ring stopping at the field's edge. The control is now built the same way.

### Changed
- Row handlers are delegated from a persistent results container, so they
  survive the table being refreshed by a search.
- Results dim briefly while a search request is in flight.

## [2.4.1] - 2026-09-18

### Fixed
- **The search box was badly proportioned.** The theme's text fields are 36px
  tall; the box had been forced to 25px to match the adjacent button, leaving a
  squashed field paired with a mismatched button. It now keeps its native
  height and nothing is overridden.
- **Rebuilt as Omeka's actual search component** rather than an approximation:
  a 36px field followed by a 36px maroon square submit (`#82423B`, hover
  `#68302C`) carrying the Font Awesome magnifier -- identical to the search in
  the admin header (`#search-form`).
- **Moved out of the batch action bar** into its own right-aligned row above
  the table, which is where Omeka puts browse-page search (`#search-users` on
  the Users page). The batch bar is a 25px-button strip and was never designed
  to hold a 36px field, which is what made the proportions clash.
- **The box no longer moves when a search returns nothing.** It previously sat
  beside "Delete Selected", so when no results meant no batch button, the box
  shifted. Anchored in its own row, it stays put whatever the results.

## [2.4.0] - 2026-09-18

### Added
- **Search the note text** from the Notes browse page. The box sits in the
  action bar beside "Delete Selected" on desktop, and moves above a
  full-width batch button on mobile.
- Searching is **live**: the box submits shortly after you stop typing, and the
  caret is restored afterwards so you can keep typing. It is a real GET form,
  so Enter and the Search button still work without JavaScript.
- The search runs **server-side across every note**, not just the rows on the
  current page -- filtering the visible page would have reported "no matches"
  while results sat on another page. Results paginate normally.
- The term is carried through pagination, column sorting, and the redirect
  after a delete or an inline edit, so you stay inside your results.
- A "Clear" link appears while a search is active, and the action bar is
  rendered even when nothing matches, so a fruitless search can always be
  changed or cleared.
- The result count reads "N notes matching <term>" while searching.

### Security
- `%` and `_` in the search term are escaped, so they match literally instead
  of acting as SQL wildcards. The term is length-capped and always bound as a
  query parameter.

## [2.3.4] - 2026-09-17

### Fixed
- **The batch action bar was taller than Omeka's own.** The theme's `.small`
  button class carries `margin-bottom: 20px`, which left dead space inside a
  `.table-actions` bar padded by only 5px. Omeka cancels this for its own batch
  bars via `.items .browse-items .table-actions button { float: left; height:
  25px }` -- a selector this plugin's markup doesn't match -- so the same
  treatment is now applied directly. On phones the button keeps a taller,
  tappable height instead of 25px.

## [2.3.3] - 2026-09-17

### Changed
- **Pagination now uses Omeka's native widget.** Replaced the row of numbered
  page links with the admin theme's own pattern -- previous arrow, a page box,
  "of N", next arrow. The theme ships styling for `.page-input` and has no
  current-page style at all, which is why the active page was indistinguishable
  from the rest before; it is now simply the value in the box. It also stays
  compact at any page count instead of growing a row of buttons, and collapses
  to three controls on a phone, where it is centred rather than floated.
- Worked around a typo in the theme's own rule (`height: 38x`) that left the
  page box shorter than the 38px arrow buttons.
- The redundant "Page 1 of 11" text was dropped from the count line, which now
  reads just the total.

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
