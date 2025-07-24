<?php
/**
 * SideNotesPlugin: Allows admin users to attach internal notes to items and collections.
 */
class SideNotesPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'after_save_item',
        'after_save_collection',
        'admin_items_show_sidebar',
        'admin_collections_show_sidebar',
        'admin_items_panel_fields',
        'admin_collections_panel_fields',
    );

    public function hookInstall()
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $db->query("
            CREATE TABLE IF NOT EXISTS `{$prefix}side_notes` (
                `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `record_type` VARCHAR(50)       NOT NULL,
                `record_id`   INT(10) UNSIGNED  NOT NULL,
                `note`        TEXT              NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `record` (`record_type`, `record_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
    }

    public function hookUninstall()
    {
        $db     = $this->_db;
        $prefix = $db->prefix;
        $db->query("DROP TABLE IF EXISTS `{$prefix}side_notes`");
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
        if ($note === '') {
            $db->query(
                "DELETE FROM `{$prefix}side_notes`
                   WHERE record_type = ?
                     AND record_id   = ?",
                array($recordType, $id)
            );
            return;
        }

        // Upsert in one query to avoid duplicates.
        $db->query(
            "INSERT INTO `{$prefix}side_notes`
                (record_type, record_id, note)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
               note = VALUES(note)",
            array($recordType, $id, $note)
        );
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
}
?>