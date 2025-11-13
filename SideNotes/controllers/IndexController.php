<?php
/**
 * SideNotes Index Controller
 */
class SideNotes_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * Browse all notes
     */
    public function browseAction()
    {
        // Get sorting parameters
        $sort = $this->getRequest()->getParam('sort', 'created');
        $order = $this->getRequest()->getParam('order', 'DESC');
        $tab = $this->getRequest()->getParam('tab', 'items');

        // Validate sort field
        $allowedSorts = array('created', 'modified', 'created_by', 'modified_by');
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created';
        }

        // Validate order
        if (!in_array(strtoupper($order), array('ASC', 'DESC'))) {
            $order = 'DESC';
        }

        // Validate tab
        if (!in_array($tab, array('items', 'collections'))) {
            $tab = 'items';
        }

        // Get notes based on tab
        $recordType = ($tab === 'items') ? 'Item' : 'Collection';
        $notes = $this->_getNotes($recordType, $sort, $order);

        // Pass data to view
        $this->view->notes = $notes;
        $this->view->currentSort = $sort;
        $this->view->currentOrder = $order;
        $this->view->currentTab = $tab;
        $this->view->recordType = $recordType;
        $this->view->previewLength = (int)get_option('side_notes_preview_length');
        $this->view->timestampFormat = get_option('side_notes_timestamp_format');
    }

    /**
     * Delete a note
     */
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $id = (int)$this->getRequest()->getPost('id');
            $tab = $this->getRequest()->getPost('tab', 'items');

            $db = get_db();
            $prefix = $db->prefix;

            $db->query("DELETE FROM `{$prefix}side_notes` WHERE id = ?", array($id));

            $this->_helper->flashMessenger(__('Note deleted successfully.'), 'success');
            $this->_helper->redirector->gotoUrl(url('side-notes/browse', array('tab' => $tab)));
        }
    }

    /**
     * Get notes with sorting
     */
    protected function _getNotes($recordType, $sort, $order)
    {
        $db = get_db();
        $prefix = $db->prefix;

        // Map sort field to actual column names
        $sortColumn = $sort;
        if ($sort === 'created_by') {
            $sortColumn = 'created_by_user_id';
        } elseif ($sort === 'modified_by') {
            $sortColumn = 'modified_by_user_id';
        }

        $sql = "SELECT sn.*,
                       cu.username as created_by_username,
                       mu.username as modified_by_username
                FROM `{$prefix}side_notes` sn
                LEFT JOIN `{$prefix}users` cu ON sn.created_by_user_id = cu.id
                LEFT JOIN `{$prefix}users` mu ON sn.modified_by_user_id = mu.id
                WHERE sn.record_type = ?
                ORDER BY sn.{$sortColumn} {$order}";

        $notes = $db->fetchAll($sql, array($recordType));

        // Enhance notes with record titles
        foreach ($notes as &$note) {
            $note['record_title'] = $this->_getRecordTitle($recordType, $note['record_id']);
            $note['record_url'] = $this->_getRecordUrl($recordType, $note['record_id']);
        }

        return $notes;
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
        return __('[Deleted Record #%s]', $recordId);
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
