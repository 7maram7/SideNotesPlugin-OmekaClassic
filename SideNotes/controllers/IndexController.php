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
        // Native Omeka browse sorting uses sort_field / sort_dir (a|d).
        $sortField = $this->getRequest()->getParam('sort_field', 'created');
        $sortDir   = strtolower($this->getRequest()->getParam('sort_dir', 'd'));
        $tab       = $this->getRequest()->getParam('tab', 'items');

        // Validate sort field against a whitelist.
        $allowedSorts = array('created', 'modified', 'created_by', 'modified_by');
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created';
        }

        // Validate direction (Omeka uses 'a' for ascending, 'd' for descending).
        if (!in_array($sortDir, array('a', 'd'), true)) {
            $sortDir = 'd';
        }

        // Validate tab.
        if (!in_array($tab, array('items', 'collections'), true)) {
            $tab = 'items';
        }

        // Get notes based on tab.
        $recordType = ($tab === 'items') ? 'Item' : 'Collection';
        $notes = $this->_getNotes($recordType, $sortField, $sortDir);

        // Pass data to view.
        $this->view->notes          = $notes;
        $this->view->currentSort    = $sortField;
        $this->view->currentDir     = $sortDir;
        $this->view->currentTab     = $tab;
        $this->view->recordType     = $recordType;
        $this->view->previewLength  = (int)get_option('side_notes_preview_length');
        $this->view->timestampFormat = get_option('side_notes_timestamp_format');
        $this->view->csrfToken      = $this->_getCsrfToken();
    }

    /**
     * Delete a single note.
     *
     * Expects a POST with a valid CSRF token. Notes are attached to records,
     * so deleting here only removes the note, not the Item/Collection.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        // Only accept POST to avoid CSRF via GET / link prefetching.
        if (!$request->isPost()) {
            $this->_helper->flashMessenger(__('Invalid request.'), 'error');
            return $this->_redirectToBrowse();
        }

        // Validate CSRF token.
        $posted   = (string)$request->getPost('csrf_token');
        $expected = $this->_getCsrfToken();
        if ($posted === '' || !hash_equals($expected, $posted)) {
            $this->_helper->flashMessenger(__('Security check failed. Please try again.'), 'error');
            return $this->_redirectToBrowse();
        }

        $noteId = (int)$request->getPost('note_id');
        $tab    = $request->getPost('tab');

        if ($noteId > 0) {
            $db = get_db();
            $db->query(
                "DELETE FROM `{$db->prefix}side_notes` WHERE id = ?",
                array($noteId)
            );
            $this->_helper->flashMessenger(__('The note was deleted.'), 'success');
        } else {
            $this->_helper->flashMessenger(__('No note specified.'), 'error');
        }

        return $this->_redirectToBrowse($tab);
    }

    /**
     * Redirect back to the browse page, preserving the active tab.
     */
    protected function _redirectToBrowse($tab = 'items')
    {
        if (!in_array($tab, array('items', 'collections'), true)) {
            $tab = 'items';
        }
        $this->_helper->redirector->gotoUrl(
            url('side-notes/index/browse', array('tab' => $tab))
        );
    }

    /**
     * Get or create a per-session CSRF token.
     */
    protected function _getCsrfToken()
    {
        $session = new Zend_Session_Namespace('side_notes_csrf');
        if (empty($session->token)) {
            $session->token = hash('sha256', uniqid(mt_rand(), true));
        }
        return $session->token;
    }

    /**
     * Get notes with sorting.
     */
    protected function _getNotes($recordType, $sortField, $sortDir)
    {
        $db = get_db();
        $prefix = $db->prefix;

        // Whitelist for sort columns (prevent SQL injection).
        $allowedColumns = array(
            'created'     => 'sn.created',
            'modified'    => 'sn.modified',
            'created_by'  => 'cu.username',
            'modified_by' => 'mu.username',
        );

        $sortColumn = isset($allowedColumns[$sortField]) ? $allowedColumns[$sortField] : 'sn.created';
        $order = ($sortDir === 'a') ? 'ASC' : 'DESC';

        $sql = "SELECT sn.*,
                       cu.username as created_by_username,
                       mu.username as modified_by_username
                FROM `{$prefix}side_notes` sn
                LEFT JOIN `{$prefix}users` cu ON sn.created_by_user_id = cu.id
                LEFT JOIN `{$prefix}users` mu ON sn.modified_by_user_id = mu.id
                WHERE sn.record_type = ?
                ORDER BY {$sortColumn} {$order}";

        $notes = $db->fetchAll($sql, array($recordType));

        // Enhance notes with record titles.
        foreach ($notes as &$note) {
            $note['record_title'] = $this->_getRecordTitle($recordType, $note['record_id']);
            $note['record_url']   = $this->_getRecordUrl($recordType, $note['record_id']);
        }

        return $notes;
    }

    /**
     * Get title for a record.
     *
     * Returns the RAW (unescaped) title; callers are responsible for escaping
     * on output. metadata() escapes by default, so we pass no_escape to avoid
     * double-escaping (e.g. "A &amp; B" showing literally in the UI).
     */
    protected function _getRecordTitle($recordType, $recordId)
    {
        if ($recordType === 'Item') {
            $item = get_record_by_id('Item', $recordId);
            if ($item) {
                return metadata($item, array('Dublin Core', 'Title'), array('no_escape' => true))
                    ?: __('[Untitled Item #%s]', $recordId);
            }
        } elseif ($recordType === 'Collection') {
            $collection = get_record_by_id('Collection', $recordId);
            if ($collection) {
                return metadata($collection, array('Dublin Core', 'Title'), array('no_escape' => true))
                    ?: __('[Untitled Collection #%s]', $recordId);
            }
        }
        return __('[Deleted Record #%s]', $recordId);
    }

    /**
     * Get admin URL for a record.
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
