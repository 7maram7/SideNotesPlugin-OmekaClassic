<?php
/**
 * SideNotes Index Controller
 */
class SideNotes_IndexController extends Omeka_Controller_AbstractActionController
{
    /** Fallback page size if Omeka's admin per-page setting is unavailable. */
    const DEFAULT_PER_PAGE = 25;

    /**
     * Browse all notes (paginated).
     */
    public function browseAction()
    {
        $request = $this->getRequest();

        // Native Omeka browse sorting uses sort_field / sort_dir (a|d).
        $sortField = $request->getParam('sort_field', 'created');
        $sortDir   = strtolower($request->getParam('sort_dir', 'd'));
        $tab       = $request->getParam('tab', 'items');
        $page      = (int)$request->getParam('page', 1);

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

        $recordType = ($tab === 'items') ? 'Item' : 'Collection';

        // Page size follows the site's admin "results per page" setting.
        $perPage = (int)get_option('per_page_admin');
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        // Total count drives pagination.
        $total = $this->_countNotes($recordType);
        $totalPages = ($total > 0) ? (int)ceil($total / $perPage) : 1;

        // Clamp the page so deleting the last row on the last page still lands
        // on a valid page instead of an empty one.
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $notes = $this->_getNotes($recordType, $sortField, $sortDir, $perPage, ($page - 1) * $perPage);

        // Pass data to view.
        $this->view->notes           = $notes;
        $this->view->currentSort     = $sortField;
        $this->view->currentDir      = $sortDir;
        $this->view->currentTab      = $tab;
        $this->view->currentPage     = $page;
        $this->view->totalPages      = $totalPages;
        $this->view->totalResults    = $total;
        $this->view->perPage         = $perPage;
        $this->view->recordType      = $recordType;
        $this->view->previewLength   = (int)get_option('side_notes_preview_length');
        $this->view->timestampFormat = get_option('side_notes_timestamp_format');
        $this->view->csrfToken       = $this->_getCsrfToken();
    }

    /**
     * Delete one or more notes.
     *
     * Accepts either a single note (single_delete=ID) or a batch selection
     * (note_ids[]). Requires POST with a valid CSRF token. Deleting a note
     * never touches the Item/Collection it is attached to.
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

        // Preserve the user's place in the list.
        $context = array(
            'tab'        => $request->getPost('tab'),
            'sort_field' => $request->getPost('sort_field'),
            'sort_dir'   => $request->getPost('sort_dir'),
            'page'       => $request->getPost('page'),
        );

        // A single-row Delete button wins over any checked boxes.
        $ids = array();
        $single = (int)$request->getPost('single_delete');
        if ($single > 0) {
            $ids[] = $single;
        } else {
            $postedIds = $request->getPost('note_ids');
            if (is_array($postedIds)) {
                foreach ($postedIds as $id) {
                    $id = (int)$id;
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }
        }

        $ids = array_unique($ids);

        if (empty($ids)) {
            $this->_helper->flashMessenger(__('No notes were selected.'), 'error');
            return $this->_redirectToBrowse($context);
        }

        $db = get_db();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->query(
            "DELETE FROM `{$db->prefix}side_notes` WHERE id IN ({$placeholders})",
            array_values($ids)
        );

        $count = count($ids);
        $this->_helper->flashMessenger(
            ($count === 1)
                ? __('The note was deleted.')
                : __('%s notes were deleted.', $count),
            'success'
        );

        return $this->_redirectToBrowse($context);
    }

    /**
     * Save an edited note from the browse page.
     *
     * Posted by the inline editor: save_note holds the note id and
     * note_text[<id>] the new text. Clearing the text removes the note,
     * matching the behaviour of the note field on the record edit form.
     */
    public function editAction()
    {
        $request = $this->getRequest();

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

        // Preserve the user's place in the list.
        $context = array(
            'tab'        => $request->getPost('tab'),
            'sort_field' => $request->getPost('sort_field'),
            'sort_dir'   => $request->getPost('sort_dir'),
            'page'       => $request->getPost('page'),
        );

        $noteId = (int)$request->getPost('save_note');
        if ($noteId < 1) {
            $this->_helper->flashMessenger(__('No note specified.'), 'error');
            return $this->_redirectToBrowse($context);
        }

        $texts = $request->getPost('note_text');
        if (!is_array($texts) || !array_key_exists($noteId, $texts)) {
            $this->_helper->flashMessenger(__('No note text was submitted.'), 'error');
            return $this->_redirectToBrowse($context);
        }

        $text = trim((string)$texts[$noteId]);

        $db = get_db();

        // Make sure the note still exists before writing.
        $exists = $db->fetchOne(
            "SELECT id FROM `{$db->prefix}side_notes` WHERE id = ?",
            array($noteId)
        );
        if (!$exists) {
            $this->_helper->flashMessenger(__('That note no longer exists.'), 'error');
            return $this->_redirectToBrowse($context);
        }

        // An emptied note is deleted, consistent with the record edit form.
        if ($text === '') {
            $db->query("DELETE FROM `{$db->prefix}side_notes` WHERE id = ?", array($noteId));
            $this->_helper->flashMessenger(__('The note was empty and has been deleted.'), 'success');
            return $this->_redirectToBrowse($context);
        }

        $currentUser = current_user();
        $userId = $currentUser ? $currentUser->id : null;

        $db->query(
            "UPDATE `{$db->prefix}side_notes`
                SET note = ?, modified = ?, modified_by_user_id = ?
              WHERE id = ?",
            array($text, date('Y-m-d H:i:s'), $userId, $noteId)
        );

        $this->_helper->flashMessenger(__('The note was saved.'), 'success');
        return $this->_redirectToBrowse($context);
    }

    /**
     * Redirect back to the browse page, preserving tab, sort and page.
     *
     * NOTE: url() already includes Omeka's admin base path, and the redirector
     * prepends the base again by default -- that produced /admin/admin/... and
     * a 404. prependBase => false keeps the URL intact.
     */
    protected function _redirectToBrowse($context = array())
    {
        $tab = isset($context['tab']) ? $context['tab'] : 'items';
        if (!in_array($tab, array('items', 'collections'), true)) {
            $tab = 'items';
        }

        $params = array('tab' => $tab);

        $sortField = isset($context['sort_field']) ? $context['sort_field'] : '';
        if (in_array($sortField, array('created', 'modified', 'created_by', 'modified_by'), true)) {
            $params['sort_field'] = $sortField;
        }

        $sortDir = isset($context['sort_dir']) ? strtolower($context['sort_dir']) : '';
        if (in_array($sortDir, array('a', 'd'), true)) {
            $params['sort_dir'] = $sortDir;
        }

        $page = isset($context['page']) ? (int)$context['page'] : 0;
        if ($page > 1) {
            $params['page'] = $page;
        }

        $this->_helper->redirector->gotoUrl(
            url('side-notes/index/browse', $params),
            array('prependBase' => false)
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
     * Count notes of a given record type.
     */
    protected function _countNotes($recordType)
    {
        $db = get_db();
        return (int)$db->fetchOne(
            "SELECT COUNT(*) FROM `{$db->prefix}side_notes` WHERE record_type = ?",
            array($recordType)
        );
    }

    /**
     * Get one page of notes, sorted.
     */
    protected function _getNotes($recordType, $sortField, $sortDir, $limit, $offset)
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

        // LIMIT/OFFSET are interpolated because PDO quotes bound values as
        // strings (LIMIT '25' is invalid SQL). Both are cast to int here.
        $limit  = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT sn.*,
                       cu.username as created_by_username,
                       mu.username as modified_by_username
                FROM `{$prefix}side_notes` sn
                LEFT JOIN `{$prefix}users` cu ON sn.created_by_user_id = cu.id
                LEFT JOIN `{$prefix}users` mu ON sn.modified_by_user_id = mu.id
                WHERE sn.record_type = ?
                ORDER BY {$sortColumn} {$order}, sn.id {$order}
                LIMIT {$limit} OFFSET {$offset}";

        $notes = $db->fetchAll($sql, array($recordType));

        // Resolve titles/identifiers only for the rows on this page.
        foreach ($notes as &$note) {
            $record = $this->_getRecord($recordType, $note['record_id']);
            $note['record_title']      = $this->_getRecordTitle($recordType, $note['record_id'], $record);
            $note['record_identifier'] = $this->_getRecordIdentifier($record, $note['record_id']);
            $note['record_url']        = $this->_getRecordUrl($recordType, $note['record_id']);
        }

        return $notes;
    }

    /**
     * Load the Item/Collection a note is attached to (or null).
     */
    protected function _getRecord($recordType, $recordId)
    {
        if ($recordType === 'Item' || $recordType === 'Collection') {
            return get_record_by_id($recordType, $recordId);
        }
        return null;
    }

    /**
     * Get title for a record.
     *
     * Returns the RAW (unescaped) title; callers escape on output. metadata()
     * escapes by default, so we pass no_escape to avoid double-escaping.
     */
    protected function _getRecordTitle($recordType, $recordId, $record = null)
    {
        if ($record === null) {
            $record = $this->_getRecord($recordType, $recordId);
        }

        if ($record) {
            $title = metadata($record, array('Dublin Core', 'Title'), array('no_escape' => true));
            if ($title !== null && $title !== '') {
                return $title;
            }
            return ($recordType === 'Collection')
                ? __('[Untitled Collection #%s]', $recordId)
                : __('[Untitled Item #%s]', $recordId);
        }

        return __('[Deleted Record #%s]', $recordId);
    }

    /**
     * Get a display identifier for a record (raw, unescaped).
     *
     * Uses the Dublin Core Identifier when one is set (e.g. "CANA-01988").
     * Collections generally have no DC Identifier, so fall back to the record
     * number, matching how Omeka itself refers to them ("Collection #150").
     */
    protected function _getRecordIdentifier($record, $recordId)
    {
        if (!$record) {
            return '';
        }

        $identifier = metadata($record, array('Dublin Core', 'Identifier'), array('no_escape' => true));
        if ($identifier !== null && trim($identifier) !== '') {
            return $identifier;
        }

        return '#' . (int)$recordId;
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
