# SideNotesPlugin-OmekaClassic
Easily attach, edit, and store staff-only notes on any Item or Collection right in the sidebar. Notes are saved in a dedicated database table and never surface on the public site, making it perfect for curatorial reminders, cataloging tips, or back-office commentary.



SideNotesPlugin

SideNotesPlugin adds private, internal note-taking capabilities to the Omeka admin interface. It allows site administrators and staff to attach and edit staff-only "side notes" on Items and Collections without exposing them on the public site.

Features
	•	Attach, update, and delete internal notes for Items and Collections.
	•	Notes are stored in a dedicated database table and never rendered in the public theme.
	•	Admin sidebar integration: notes appear alongside the public/featured panel.
	•	Admin edit form panel integration: a textarea field for editing notes appears in the edit form.
	•	Flexible placement: uses jQuery to position notes in the sidebar.

Requirements
	•	Omeka Classic 2.x or later
	•	PHP 5.6+ (or the version supported by your Omeka install)

Installation

Download or clone this repository into your Omeka plugins/ directory and extract

git clone https://github.com/7maram7/SideNotesPlugin-OmekaClassic.git 

In the Omeka admin, go to Plugins, find SideNotesPlugin, and click Install.

The plugin will automatically create a side_notes table in your database.


Usage
	•	View Sidebar Notes: When viewing an Item or Collection in the admin, staff-only notes will appear in the right-hand sidebar underneath Public / Featured.
	•	Edit Notes: On the Item/Collection edit form, a Side Notes textarea appears. Enter or update your notes and click Save Changes.
	•	Delete Notes: Clearing the text area and saving will remove the note from the database.
Hooks
	•	install / uninstall – creates or drops the side_notes database table.
	•	after_save_item / after_save_collection – persists note changes on save.
	•	admin_items_show_sidebar / admin_collections_show_sidebar – renders the sidebar note panel.
	•	admin_items_panel_fields / admin_collections_panel_fields – injects the edit-form textarea.
Customization
	•	Panel Positioning: The plugin outputs a small inline script to move the note panel above the Public / Featured panel. You can disable or adjust this by editing the _renderSidebar method in SideNotesPlugin.php.
	•	Styling: Add CSS overrides in your admin theme targeting .side-notes-panel if you’d like to adjust margins or appearance.


