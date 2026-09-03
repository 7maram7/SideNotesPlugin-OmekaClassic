<?php
/**
 * API adapter for SideNote.
 *
 * Omeka resolves this by name: a resource with record_type "SideNote"
 * requires an adapter class called "Api_SideNote".
 */
class Api_SideNote extends Omeka_Record_Api_AbstractRecordAdapter
{
    /**
     * Build the JSON representation of a note.
     */
    public function getRepresentation(Omeka_Record_AbstractRecord $record)
    {
        return array(
            'id'          => (int)$record->id,
            'url'         => self::getResourceUrl("/side_notes/{$record->id}"),
            'record_type' => $record->record_type,
            'record_id'   => (int)$record->record_id,
            'note'        => $record->note,
            'created'     => $record->created,
            'modified'    => $record->modified,
            'created_by_user_id'  => $record->created_by_user_id ? (int)$record->created_by_user_id : null,
            'modified_by_user_id' => $record->modified_by_user_id ? (int)$record->modified_by_user_id : null,
        );
    }

    /**
     * Handle POST /api/side_notes.
     *
     * A record can hold only one note, so this is an upsert: if the target
     * Item/Collection already has a note, the incoming text replaces it
     * instead of failing on the unique constraint. The caller therefore never
     * has to check whether a note exists first.
     *
     * Adopting the existing row's id makes Omeka issue an UPDATE rather than
     * an INSERT (Omeka decides via exists(), which tests the id). The original
     * created / created_by values are preserved so the audit trail survives.
     */
    public function setPostData(Omeka_Record_AbstractRecord $record, $data)
    {
        $record->record_type = isset($data->record_type) ? (string)$data->record_type : null;
        $record->record_id   = isset($data->record_id) ? (int)$data->record_id : null;
        $record->note        = isset($data->note) ? (string)$data->note : null;

        $existing = get_db()->getTable('SideNote')
            ->findByRecord($record->record_type, $record->record_id);

        if ($existing) {
            $record->id                 = $existing->id;
            $record->created            = $existing->created;
            $record->created_by_user_id = $existing->created_by_user_id;
        }
    }

    /**
     * Handle PUT /api/side_notes/:id -- update the text of a known note.
     *
     * The record it is attached to cannot be changed; move the note by
     * deleting it and posting a new one.
     */
    public function setPutData(Omeka_Record_AbstractRecord $record, $data)
    {
        if (isset($data->note)) {
            $record->note = (string)$data->note;
        }
    }
}
