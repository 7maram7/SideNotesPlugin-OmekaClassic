<?php
/**
 * A single internal note attached to an Item or Collection.
 *
 * The admin screens still use direct SQL; this model exists so the note can
 * be exposed through Omeka's API, which only speaks to record objects.
 */
class SideNote extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $record_type;
    public $record_id;
    public $note;
    public $created;
    public $modified;
    public $created_by_user_id;
    public $modified_by_user_id;

    /**
     * ACL resource id.
     *
     * This MUST be defined. Omeka's API controller skips the permission check
     * on GET for records that have no ACL resource, so without this the notes
     * would be readable by anyone, including unauthenticated requests.
     */
    public function getResourceId()
    {
        return 'SideNotes';
    }

    protected function _validate()
    {
        if (!in_array($this->record_type, array('Item', 'Collection'), true)) {
            $this->addError('record_type', __('record_type must be "Item" or "Collection".'));
        }

        if (!(int)$this->record_id) {
            $this->addError('record_id', __('A record_id is required.'));
        }

        if (trim((string)$this->note) === '') {
            $this->addError('note', __('Note text cannot be empty.'));
        }

        // Don't allow notes to be attached to records that don't exist.
        if (in_array($this->record_type, array('Item', 'Collection'), true) && (int)$this->record_id) {
            if (!get_record_by_id($this->record_type, (int)$this->record_id)) {
                $this->addError('record_id', __('No %s exists with id %s.',
                    $this->record_type, (int)$this->record_id));
            }
        }
    }

    protected function beforeSave($args)
    {
        $now = date('Y-m-d H:i:s');
        $currentUser = current_user();
        $userId = $currentUser ? $currentUser->id : null;

        if ($args['insert']) {
            $this->created = $now;
            $this->created_by_user_id = $userId;
        }

        $this->modified = $now;
        $this->modified_by_user_id = $userId;
    }
}
