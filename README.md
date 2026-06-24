# Side Notes Plugin — Omeka Classic

![SideNotes in action](images/screenshot-1.png)

SideNotes adds a dedicated internal notes layer to Omeka Classic. It lets staff
attach private notes to Items and Collections directly from the admin interface,
with full tracking of who created and edited each note and when. Notes are stored
in their own database table and never appear on the public site — ideal for
curatorial workflows, cataloging coordination, provenance research, and internal
communication.

![SideNotes Browse Page](images/Browse%20Page.png)

The plugin integrates with the existing admin screens and follows Omeka's native
admin theme, so it looks and behaves like the rest of the back office: notes can
be edited on Item/Collection forms, viewed in the right-hand sidebar, summarized
on the dashboard, and managed from a dedicated Notes browse page with tabs,
sortable columns, and a confirm-protected delete.

![SideNotes Config Screen](images/Config.png)

## Key features

- Attach one private note to any Item or Collection (admin only, never public).

- Track creator, last editor, and created/modified timestamps for each note.

- Sidebar panel and edit-form field for quick viewing and editing.

- Dashboard panels for recent Item and Collection notes.

- Dedicated Notes browse page with Item/Collection tabs, sortable columns, and delete.

- Configurable preview length, dashboard note count, and date/time format.

- Native admin styling — inherits the active Omeka admin theme (no custom skin).

- Upgrade path that preserves existing notes from earlier versions.

## Requirements

- Omeka Classic 2.0 or later (tested on 3.1.2).

- PHP 5.6 or later.

## Installation

Download the plugin and extract the archive. Inside you will find a folder named
SideNotes. Create a ZIP containing only that SideNotes folder (or use the
provided SideNotes.zip), upload it to your Omeka Classic plugins directory,
and extract it so the final path is omeka/plugins/SideNotes. The other
repository files (images/, README.md, CHANGELOG.md, test-plugin.php) are
not needed on the server. Then log in to the Omeka admin, go to Plugins, and
click Install next to Side Notes.

## Upgrading

Replace the SideNotes folder under omeka/plugins/ with the new version, then
go to Plugins and click Upgrade next to Side Notes. Existing notes are
preserved.

## Changelog

See [CHANGELOG.md](CHANGELOG.md). Latest release: 2.1.0.
