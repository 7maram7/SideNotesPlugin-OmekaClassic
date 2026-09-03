<?php
/**
 * Table class for SideNote.
 *
 * Omeka derives the table name from the record class, so this maps to the
 * existing `side_notes` table -- no schema changes and no duplicated data.
 */
class Table_SideNote extends Omeka_Db_Table
{
    /**
     * Find the note attached to a record, if there is one.
     *
     * The schema allows only one note per record, so this returns a single
     * record or null.
     */
    public function findByRecord($recordType, $recordId)
    {
        if (!in_array($recordType, array('Item', 'Collection'), true) || !(int)$recordId) {
            return null;
        }

        $select = $this->getSelect()
            ->where('record_type = ?', $recordType)
            ->where('record_id = ?', (int)$recordId)
            ->limit(1);

        return $this->fetchObject($select);
    }

    /**
     * Is the current user allowed to see notes at all?
     *
     * Fails closed: any problem resolving the ACL denies access.
     */
    protected function _currentUserMayViewNotes()
    {
        try {
            $bootstrap = Zend_Registry::get('bootstrap');
            $acl  = $bootstrap->getResource('Acl');
            $user = $bootstrap->getResource('CurrentUser');
        } catch (Exception $e) {
            return false;
        }

        if (!$acl) {
            return false;
        }

        return (bool)$acl->isAllowed($user, 'SideNotes', 'show');
    }

    /**
     * Support ?record_type= and ?record_id= on the API index action.
     *
     * IMPORTANT: Omeka's API index action performs no per-record permission
     * check -- it queries the table and returns whatever comes back (core
     * hides private items the same way, by filtering in the table). So the
     * permission check has to happen here, or notes would be listable by
     * anonymous callers.
     */
    public function applySearchFilters($select, $params)
    {
        if (!$this->_currentUserMayViewNotes()) {
            $select->where('1 = 0');
            return;
        }

        if (!empty($params['record_type'])) {
            $select->where('record_type = ?', $params['record_type']);
        }

        if (!empty($params['record_id'])) {
            $select->where('record_id = ?', (int)$params['record_id']);
        }

        $select->order('modified DESC');
    }
}
