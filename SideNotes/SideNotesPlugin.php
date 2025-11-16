<?php
/**
 * SideNotesPlugin: Allows admin users to attach internal notes to items and collections.
 */
class SideNotesPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'config_form',
        'config',
        'after_save_item',
        'after_save_collection',
        'admin_items_show_sidebar',
        'admin_collections_show_sidebar',
        'admin_items_panel_fields',
        'admin_collections_panel_fields',
        'admin_head',
        'admin_dashboard',
        'define_routes',
    );

    protected $_filters = array(
        'admin_navigation_main',
    );

    public function hookInstall()
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $db->query("
            CREATE TABLE IF NOT EXISTS `{$prefix}side_notes` (
                `id`                  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `record_type`         VARCHAR(50)       NOT NULL,
                `record_id`           INT(10) UNSIGNED  NOT NULL,
                `note`                TEXT              NULL,
                `created`             DATETIME          NULL,
                `modified`            DATETIME          NULL,
                `created_by_user_id`  INT(10) UNSIGNED  NULL,
                `modified_by_user_id` INT(10) UNSIGNED  NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `record` (`record_type`, `record_id`),
                KEY `created` (`created`),
                KEY `modified` (`modified`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        // Set default configuration options
        set_option('side_notes_preview_length', 150);
        set_option('side_notes_timestamp_format', 'F j, Y g:i A');
        set_option('side_notes_dashboard_count', 10);
    }

    public function hookUninstall()
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $db->query("DROP TABLE IF EXISTS `{$prefix}side_notes`");

        // Delete configuration options
        delete_option('side_notes_preview_length');
        delete_option('side_notes_timestamp_format');
        delete_option('side_notes_dashboard_count');
    }

    public function hookUpgrade($args)
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        // Upgrade from version 1.0 to 2.0 - add new columns
        if (version_compare($oldVersion, '2.0', '<')) {
            // Check if columns already exist before adding
            $tableInfo = $db->fetchAll("SHOW COLUMNS FROM `{$prefix}side_notes`");
            $columnNames = array();
            foreach ($tableInfo as $column) {
                $columnNames[] = $column['Field'];
            }

            if (!in_array('created', $columnNames)) {
                $db->query("ALTER TABLE `{$prefix}side_notes`
                    ADD COLUMN `created` DATETIME NULL AFTER `note`");
            }
            if (!in_array('modified', $columnNames)) {
                $db->query("ALTER TABLE `{$prefix}side_notes`
                    ADD COLUMN `modified` DATETIME NULL AFTER `created`");
            }
            if (!in_array('created_by_user_id', $columnNames)) {
                $db->query("ALTER TABLE `{$prefix}side_notes`
                    ADD COLUMN `created_by_user_id` INT(10) UNSIGNED NULL AFTER `modified`");
            }
            if (!in_array('modified_by_user_id', $columnNames)) {
                $db->query("ALTER TABLE `{$prefix}side_notes`
                    ADD COLUMN `modified_by_user_id` INT(10) UNSIGNED NULL AFTER `created_by_user_id`");
            }

            // Add indexes if they don't exist
            try {
                $indexes = $db->fetchAll("SHOW INDEX FROM `{$prefix}side_notes` WHERE Key_name = 'created'");
                if (empty($indexes)) {
                    $db->query("ALTER TABLE `{$prefix}side_notes` ADD KEY `created` (`created`)");
                }
            } catch (Exception $e) {
                // Index might already exist, continue
            }

            try {
                $indexes = $db->fetchAll("SHOW INDEX FROM `{$prefix}side_notes` WHERE Key_name = 'modified'");
                if (empty($indexes)) {
                    $db->query("ALTER TABLE `{$prefix}side_notes` ADD KEY `modified` (`modified`)");
                }
            } catch (Exception $e) {
                // Index might already exist, continue
            }

            // Set existing notes to current timestamp
            $now = date('Y-m-d H:i:s');
            $db->query("UPDATE `{$prefix}side_notes`
                SET created = ?, modified = ?
                WHERE created IS NULL OR modified IS NULL",
                array($now, $now));

            // Set default configuration options if not exist
            if (!get_option('side_notes_preview_length')) {
                set_option('side_notes_preview_length', 150);
            }
            if (!get_option('side_notes_timestamp_format')) {
                set_option('side_notes_timestamp_format', 'F j, Y g:i A');
            }
            if (!get_option('side_notes_dashboard_count')) {
                set_option('side_notes_dashboard_count', 10);
            }
        }
    }

    public function hookAfterSaveItem($args)
    {
        $this->_saveNote($args, 'Item');
    }

    public function hookAfterSaveCollection($args)
    {
        $this->_saveNote($args, 'Collection');
    }

    protected function _saveNote(array $args, $recordType)
    {
        $record = $args['record'];
        $post   = isset($args['post']) ? (array)$args['post'] : array();
        $db     = $this->_db;
        $prefix = $db->prefix;
        $id     = (int)$record->id;

        if (!array_key_exists('side_notes', $post)) {
            return;
        }

        $note = trim($post['side_notes']);
        $now = date('Y-m-d H:i:s');
        $currentUser = current_user();
        $userId = $currentUser ? $currentUser->id : null;

        if ($note === '') {
            $db->query(
                "DELETE FROM `{$prefix}side_notes`
                   WHERE record_type = ?
                     AND record_id   = ?",
                array($recordType, $id)
            );
            return;
        }

        // Check if note exists to determine if this is create or update
        $existingNote = $db->fetchOne(
            "SELECT id FROM `{$prefix}side_notes`
             WHERE record_type = ? AND record_id = ?",
            array($recordType, $id)
        );

        if ($existingNote) {
            // Update existing note
            $db->query(
                "UPDATE `{$prefix}side_notes`
                 SET note = ?, modified = ?, modified_by_user_id = ?
                 WHERE record_type = ? AND record_id = ?",
                array($note, $now, $userId, $recordType, $id)
            );
        } else {
            // Insert new note
            $db->query(
                "INSERT INTO `{$prefix}side_notes`
                    (record_type, record_id, note, created, modified, created_by_user_id, modified_by_user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                array($recordType, $id, $note, $now, $now, $userId, $userId)
            );
        }
    }

    public function hookAdminItemsShowSidebar($args)
    {
        $this->_renderSidebar('Item', $args['item']->id);
    }

    public function hookAdminCollectionsShowSidebar($args)
    {
        $this->_renderSidebar('Collection', $args['collection']->id);
    }

    protected function _renderSidebar($recordType, $recordId)
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $note   = $db->fetchOne(
            "SELECT note
               FROM `{$prefix}side_notes`
              WHERE record_type = ?
                AND record_id   = ?",
            array($recordType, (int)$recordId)
        );

        // Give our panel its own class so we can target it with JS
        echo '<div class="panel side-notes-panel">';
        echo '<h4>' . __('Side Notes') . '</h4>';
        if ($note) {
            echo '<p>' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            echo '<p>' . __('No notes.') . '</p>';
        }
        echo '</div>';

        // jQuery to move Side Notes just above the "Public / Featured" panel
        echo <<<HTML
<script type="text/javascript">
jQuery(function($){
  var ours   = $('.side-notes-panel').last();
  var target = $('.public-featured.panel').first();
  if (ours.length && target.length) {
    ours.insertBefore(target);
  }
});
</script>
HTML;
    }

    public function hookAdminItemsPanelFields($args)
    {
        $this->_renderEditTextarea(
            'Item',
            isset($args['record']->id) ? $args['record']->id : null
        );
    }

    public function hookAdminCollectionsPanelFields($args)
    {
        $this->_renderEditTextarea(
            'Collection',
            isset($args['record']->id) ? $args['record']->id : null
        );
    }

    protected function _renderEditTextarea($recordType, $recordId = null)
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $note   = '';

        if ($recordId) {
            $note = $db->fetchOne(
                "SELECT note
                   FROM `{$prefix}side_notes`
                  WHERE record_type = ?
                    AND record_id   = ?",
                array($recordType, (int)$recordId)
            );
        }

        echo '<div class="field">';
        echo '  <div class="two columns alpha">';
        echo '    <label for="side_notes">' . __('Side Notes') . '</label>';
        echo '  </div>';
        echo '  <div class="inputs five columns omega">';
        echo '    <p class="explanation">'
             . __('Internal note (visible only to site staff).')
             . '</p>';
        echo '    <textarea name="side_notes" id="side_notes" rows="5">'
             . htmlspecialchars($note, ENT_QUOTES, 'UTF-8')
             . '</textarea>';
        echo '<br/><br/>';
        echo '  </div>';
        echo '</div>';
    }

    /**
     * Configuration form hook
     */
    public function hookConfigForm()
    {
        include 'config_form.php';
    }

    /**
     * Save configuration
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        set_option('side_notes_preview_length', (int)$post['side_notes_preview_length']);
        set_option('side_notes_timestamp_format', $post['side_notes_timestamp_format']);
        set_option('side_notes_dashboard_count', (int)$post['side_notes_dashboard_count']);
    }

    /**
     * Add Notes to admin navigation
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Notes'),
            'uri'   => url('side-notes/index/browse'),
        );
        return $nav;
    }

    /**
     * Define custom routes
     */
    public function hookDefineRoutes($args)
    {
        // Only define routes for admin interface
        if (!is_admin_theme()) {
            return;
        }

        $router = $args['router'];

        // Main browse route
        $router->addRoute(
            'sideNotesBrowse',
            new Zend_Controller_Router_Route(
                'side-notes/index/browse',
                array(
                    'module'     => 'side-notes',
                    'controller' => 'index',
                    'action'     => 'browse'
                )
            )
        );

        // Delete action route
        $router->addRoute(
            'sideNotesDelete',
            new Zend_Controller_Router_Route(
                'side-notes/index/delete',
                array(
                    'module'     => 'side-notes',
                    'controller' => 'index',
                    'action'     => 'delete'
                )
            )
        );
    }

    /**
     * Display recent notes on admin dashboard
     */
    public function hookAdminDashboard($args)
    {
        echo $this->_renderRecentNotesPanel('Item', __('Recent Item Notes'));
        echo $this->_renderRecentNotesPanel('Collection', __('Recent Collection Notes'));
    }

    /**
     * Add custom CSS for notes panels
     */
    public function hookAdminHead($args)
    {
        echo '<style>
            .side-notes-dashboard-panel { margin-bottom: 20px; }
            .side-notes-dashboard-panel .note-item {
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e7e7e7;
            }
            .side-notes-dashboard-panel .note-item:last-child {
                border-bottom: none;
            }
            .side-notes-dashboard-panel .note-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .side-notes-dashboard-panel .note-title a {
                color: #1b1b1b;
                text-decoration: none;
            }
            .side-notes-dashboard-panel .note-title a:hover {
                color: #ff6700;
            }
            .side-notes-dashboard-panel .note-preview {
                margin-bottom: 5px;
                color: #555;
            }
            .side-notes-dashboard-panel .note-timestamp {
                font-size: 0.9em;
                color: #888;
            }
        </style>';
    }

    /**
     * Render recent notes panel for dashboard
     */
    protected function _renderRecentNotesPanel($recordType, $title)
    {
        $notes = $this->_getRecentNotes($recordType);

        if (empty($notes)) {
            return '';
        }

        $previewLength = (int)get_option('side_notes_preview_length');
        $timestampFormat = get_option('side_notes_timestamp_format');

        $html = '<div class="panel side-notes-dashboard-panel">';
        $html .= '<h2>' . $title . '</h2>';

        foreach ($notes as $note) {
            $recordTitle = $this->_getRecordTitle($recordType, $note['record_id']);
            $recordUrl = $this->_getRecordUrl($recordType, $note['record_id']);

            // Skip if record no longer exists (returns '[Unknown]')
            if ($recordTitle === __('[Unknown]')) {
                continue;
            }

            // Truncate note preview
            $preview = $note['note'];
            if (mb_strlen($preview) > $previewLength) {
                $preview = mb_substr($preview, 0, $previewLength) . '...';
            }

            // Format timestamp safely
            $timestamp = '';
            if (!empty($note['created'])) {
                $timestamp = date($timestampFormat, strtotime($note['created']));
            }

            $html .= '<div class="note-item">';
            $html .= '<div class="note-title"><a href="' . htmlspecialchars($recordUrl, ENT_QUOTES, 'UTF-8') . '">'
                  . htmlspecialchars($recordTitle, ENT_QUOTES, 'UTF-8') . '</a></div>';
            $html .= '<div class="note-preview">' . htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') . '</div>';
            if ($timestamp) {
                $html .= '<div class="note-timestamp">' . __('Created: ') . htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get recent notes for a record type
     */
    protected function _getRecentNotes($recordType)
    {
        $db = $this->_db;
        $prefix = $db->prefix;
        $count = (int)get_option('side_notes_dashboard_count');

        if (!$count) {
            $count = 10; // Default fallback
        }

        $sql = "SELECT record_id, note, created
                FROM `{$prefix}side_notes`
                WHERE record_type = ?
                  AND created IS NOT NULL
                ORDER BY created DESC
                LIMIT ?";

        return $db->fetchAll($sql, array($recordType, $count));
    }

    /**
     * Get title for a record
     */
    protected function _getRecordTitle($recordType, $recordId)
    {
        if ($recordType === 'Item') {
            $item = get_record_by_id('Item', $recordId);
            if ($item) {
                return metadata($item, array('Dublin Core', 'Title')) ?: __('[Untitled Item #%s]', $recordId);
            }
        } elseif ($recordType === 'Collection') {
            $collection = get_record_by_id('Collection', $recordId);
            if ($collection) {
                return metadata($collection, array('Dublin Core', 'Title')) ?: __('[Untitled Collection #%s]', $recordId);
            }
        }
        return __('[Unknown]');
    }

    /**
     * Get admin URL for a record
     */
    protected function _getRecordUrl($recordType, $recordId)
    {
        if ($recordType === 'Item') {
            return admin_url('items/show/' . $recordId);
        } elseif ($recordType === 'Collection') {
            return admin_url('collections/show/' . $recordId);
        }
        return '#';
    }
}