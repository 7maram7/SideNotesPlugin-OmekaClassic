<?php
$pageTitle = __('Notes');
echo head(array('title' => $pageTitle, 'bodyclass' => 'side-notes browse'));

/**
 * Render a native Omeka sortable column header.
 *
 * Reproduces the markup the admin theme styles: a <th> containing
 * <a><span>Label</span></a>. The active column gets "sorting asc|desc"
 * so the theme shows the bold label and the directional sort icon.
 */
if (!function_exists('side_notes_sort_th')):
function side_notes_sort_th($label, $field, $currentSort, $currentDir, $tab, $perPageParams = array())
{
    $isActive = ($currentSort === $field);
    // Toggle direction on the active column; new columns start ascending.
    $newDir = ($isActive && $currentDir === 'a') ? 'd' : 'a';

    $thClass = '';
    if ($isActive) {
        $thClass = ' class="sorting ' . ($currentDir === 'a' ? 'asc' : 'desc') . '"';
    }

    // Changing the sort returns to page 1.
    $url = url('side-notes/index/browse', array_merge($perPageParams, array(
        'tab'        => $tab,
        'sort_field' => $field,
        'sort_dir'   => $newDir,
    )));

    return '<th' . $thClass . '><a href="' . html_escape($url) . '">'
        . '<span>' . html_escape($label) . '</span></a></th>';
}
endif;

/**
 * Normalise a stored note to plain text.
 *
 * Notes are plain text, but some were saved with literal <br /> markup.
 * Convert those to newlines and collapse runs of blank lines.
 */
if (!function_exists('side_notes_plain_text')):
function side_notes_plain_text($text)
{
    $text = preg_replace('#<br\s*/?>#i', "\n", (string)$text);
    $text = str_replace("\r\n", "\n", $text);
    return preg_replace("/\n{3,}/", "\n\n", $text);
}
endif;

/**
 * Build a browse URL for a given page, keeping tab and sort.
 */
if (!function_exists('side_notes_page_url')):
function side_notes_page_url($page, $tab, $sort, $dir)
{
    $params = array('tab' => $tab, 'sort_field' => $sort, 'sort_dir' => $dir);
    if ($page > 1) {
        $params['page'] = $page;
    }
    return url('side-notes/index/browse', $params);
}
endif;
?>

<style>
    /* Minimal overrides: render the CSRF-protected Delete buttons as inline
       links. Tabs, table, headers, rows, pagination and the action bar all
       inherit the native admin theme. */
    .action-links button.link-button {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        cursor: pointer;
        font: inherit;
        line-height: inherit;
        vertical-align: baseline;
        text-decoration: underline; /* match the <a> actions exactly */
        color: #B00D00;
    }
    .action-links button.side-notes-edit-toggle { color: #003576; }
    /* Keep each action on its own line so the narrow column reads cleanly. */
    .action-links li { display: block; margin-bottom: 2px; }
    .side-notes-count { float: left; margin: 0 0 10px; color: #666; }

    /* Inline note editor */
    #side-notes .side-notes-editor textarea {
        width: 100%;
        box-sizing: border-box;
        margin: 0 0 6px;
        font-size: 0.95em;
    }
    #side-notes .side-notes-editor button {
        margin: 0 5px 0 0;
    }
    #side-notes tr.is-editing td { background-color: #fffdf3; }

    #side-notes th,
    #side-notes td {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* ---- Desktop / tablet (>= 768px) ----
       Omeka's own rule (.batch-edit-heading + th { width: 50% }) would give the
       Record column half the table once a checkbox column exists, squeezing
       the Note text. Fixed layout so these widths stick. */
    @media (min-width: 768px) {
        #side-notes { table-layout: fixed; width: 100%; }
        #side-notes .batch-edit-heading { width: 3%; }
        #side-notes .batch-edit-heading + th { width: 16%; } /* Record */
        #side-notes th:nth-child(3) { width: 11%; }          /* Identifier */
        #side-notes th:nth-child(4) { width: 38%; }          /* Note  */
        #side-notes th:nth-child(5),
        #side-notes th:nth-child(6) { width: 12%; }          /* Created / Modified */
        #side-notes th:nth-child(7) { width: 8%; }           /* Actions */
    }

    /* ---- Phones (< 768px) ----
       Seven columns can't fit a phone: the headers collapse to one letter per
       line. Each row becomes a labelled card instead, reusing the theme's
       borders and colours so it still reads as Omeka. */
    @media (max-width: 767px) {
        #side-notes thead { display: none; }

        #side-notes,
        #side-notes tbody,
        #side-notes tr,
        #side-notes td {
            display: block;
            width: auto;
        }

        #side-notes tr {
            border: 1px solid #DCDCDC;
            background: #fff;
            margin: 0 0 12px;
            padding: 10px 12px;
            overflow: hidden;
        }
        #side-notes tr.is-editing { background: #fffdf3; }
        #side-notes tr.is-editing td { background: none; }

        #side-notes td {
            border: none;
            border-bottom: 1px solid #ececec;
            padding: 7px 0;
        }
        #side-notes td:last-child { border-bottom: none; }

        /* Name each value, since the header row is hidden. */
        #side-notes td[data-label]:before {
            content: attr(data-label);
            display: block;
            margin-bottom: 2px;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6f6f6f;
        }

        /* Record title leads the card. */
        #side-notes td.side-notes-record { font-size: 1.05em; font-weight: bold; }

        /* Checkbox and its label share a line. */
        #side-notes td.batch-edit-heading { text-align: left; }
        #side-notes td.batch-edit-heading:before {
            display: inline;
            margin: 0 6px 0 0;
        }

        /* Actions read across, not stacked. */
        #side-notes .action-links li {
            display: inline-block;
            margin: 0 16px 0 0;
        }

        .side-notes-count { float: none; margin-bottom: 14px; }
    }
</style>

<?php echo flash(); ?>

<ul id="section-nav" class="navigation tabs">
    <li<?php echo ($currentTab === 'items') ? ' class="current"' : ''; ?>>
        <a class="<?php echo ($currentTab === 'items') ? 'active' : ''; ?>"
           href="<?php echo html_escape(url('side-notes/index/browse', array('tab' => 'items'))); ?>">
            <?php echo __('Item Notes'); ?>
        </a>
    </li>
    <li<?php echo ($currentTab === 'collections') ? ' class="current"' : ''; ?>>
        <a class="<?php echo ($currentTab === 'collections') ? 'active' : ''; ?>"
           href="<?php echo html_escape(url('side-notes/index/browse', array('tab' => 'collections'))); ?>">
            <?php echo __('Collection Notes'); ?>
        </a>
    </li>
</ul>

<?php if (!empty($notes)): ?>

<?php
// Native-style pagination markup, reused above and below the table.
ob_start();
if ($totalPages > 1):
?>
<ul class="pagination">
    <?php if ($currentPage > 1): ?>
    <li class="pagination_previous">
        <a href="<?php echo html_escape(side_notes_page_url($currentPage - 1, $currentTab, $currentSort, $currentDir)); ?>"><?php echo __('Previous'); ?></a>
    </li>
    <?php endif; ?>

    <?php
    // Show a compact window of page numbers around the current page.
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $start + 4);
    $start = max(1, $end - 4);
    for ($p = $start; $p <= $end; $p++):
    ?>
        <li>
            <?php if ($p == $currentPage): ?>
                <a href="<?php echo html_escape(side_notes_page_url($p, $currentTab, $currentSort, $currentDir)); ?>"><strong><?php echo $p; ?></strong></a>
            <?php else: ?>
                <a href="<?php echo html_escape(side_notes_page_url($p, $currentTab, $currentSort, $currentDir)); ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        </li>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
    <li class="pagination_next">
        <a href="<?php echo html_escape(side_notes_page_url($currentPage + 1, $currentTab, $currentSort, $currentDir)); ?>"><?php echo __('Next'); ?></a>
    </li>
    <?php endif; ?>
</ul>
<?php
endif;
$paginationHtml = ob_get_clean();
?>

<?php echo $paginationHtml; ?>

<p class="side-notes-count">
    <?php echo __('%s notes total', $totalResults); ?>
    <?php if ($totalPages > 1): ?>
        &middot;
        <?php echo __('Page'); ?> <?php echo (int)$currentPage; ?>
        <?php echo __('of'); ?> <?php echo (int)$totalPages; ?>
    <?php endif; ?>
</p>

<form method="post" id="side-notes-batch-form"
      action="<?php echo html_escape(url('side-notes/index/delete')); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>">
    <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>">
    <input type="hidden" name="sort_field" value="<?php echo html_escape($currentSort); ?>">
    <input type="hidden" name="sort_dir" value="<?php echo html_escape($currentDir); ?>">
    <input type="hidden" name="page" value="<?php echo (int)$currentPage; ?>">

    <div class="table-actions">
        <button type="submit" name="batch_delete" value="1"
                class="red button small full-width-mobile"
                id="side-notes-batch-delete">
            <?php echo __('Delete Selected'); ?>
        </button>
    </div>

    <table id="side-notes">
        <thead>
            <tr>
                <th class="batch-edit-heading">
                    <input type="checkbox" id="side-notes-check-all"
                           title="<?php echo __('Select all on this page'); ?>">
                </th>
                <th><?php echo __('Record'); ?></th>
                <th><?php echo __('Identifier'); ?></th>
                <th><?php echo __('Note'); ?></th>
                <?php echo side_notes_sort_th(__('Created'), 'created', $currentSort, $currentDir, $currentTab); ?>
                <?php echo side_notes_sort_th(__('Modified'), 'modified', $currentSort, $currentDir, $currentTab); ?>
                <th><?php echo __('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notes as $i => $note): ?>
            <tr class="<?php echo ($i % 2) ? 'even' : 'odd'; ?>">
                <td class="batch-edit-heading" data-label="<?php echo __('Select'); ?>">
                    <input type="checkbox" name="note_ids[]" value="<?php echo (int)$note['id']; ?>"
                           aria-label="<?php echo __('Select this note'); ?>">
                </td>
                <td class="side-notes-record" data-label="<?php echo __('Record'); ?>">
                    <a href="<?php echo html_escape($note['record_url']); ?>">
                        <?php echo html_escape($note['record_title']); ?>
                    </a>
                </td>
                <td class="side-notes-identifier" data-label="<?php echo __('Identifier'); ?>">
                    <?php echo html_escape($note['record_identifier']); ?>
                </td>
                <td data-label="<?php echo __('Note'); ?>">
                    <?php
                    // Older notes were stored with literal <br /> tags. Notes are
                    // plain text, so turn those back into real line breaks rather
                    // than showing markup in the editor. Saving stores the clean
                    // version, so rows heal themselves as they are edited.
                    $noteText = side_notes_plain_text($note['note']);

                    $preview = $noteText;
                    if (mb_strlen($preview) > $previewLength) {
                        $preview = mb_substr($preview, 0, $previewLength) . '...';
                    }
                    ?>
                    <div class="side-notes-preview" id="note-view-<?php echo (int)$note['id']; ?>">
                        <?php echo html_escape($preview); ?>
                    </div>
                    <div class="side-notes-editor" id="note-edit-<?php echo (int)$note['id']; ?>" style="display:none;">
                        <textarea name="note_text[<?php echo (int)$note['id']; ?>]" rows="6"
                                  ><?php echo html_escape($noteText); ?></textarea>
                        <button type="submit" class="green button small" name="save_note"
                                value="<?php echo (int)$note['id']; ?>"
                                formaction="<?php echo html_escape(url('side-notes/index/edit')); ?>">
                            <?php echo __('Save'); ?>
                        </button>
                        <button type="button" class="button small side-notes-cancel"
                                data-note-id="<?php echo (int)$note['id']; ?>">
                            <?php echo __('Cancel'); ?>
                        </button>
                    </div>
                </td>
                <td data-label="<?php echo __('Created'); ?>">
                    <?php if (!empty($note['created'])): ?>
                        <?php echo html_escape(date($timestampFormat, strtotime($note['created']))); ?>
                        <?php if (!empty($note['created_by_username'])): ?>
                            <br><small><?php echo __('by %s', html_escape($note['created_by_username'])); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo __('Modified'); ?>">
                    <?php if (!empty($note['modified'])): ?>
                        <?php echo html_escape(date($timestampFormat, strtotime($note['modified']))); ?>
                        <?php if (!empty($note['modified_by_username'])): ?>
                            <br><small><?php echo __('by %s', html_escape($note['modified_by_username'])); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td data-label="<?php echo __('Actions'); ?>">
                    <ul class="action-links">
                        <li>
                            <button type="button" class="link-button side-notes-edit-toggle"
                                    data-note-id="<?php echo (int)$note['id']; ?>">
                                <?php echo __('Edit'); ?>
                            </button>
                        </li>
                        <li><a href="<?php echo html_escape($note['record_url']); ?>"><?php echo __('View'); ?></a></li>
                        <li>
                            <button type="submit" class="link-button side-notes-delete-single"
                                    name="single_delete" value="<?php echo (int)$note['id']; ?>">
                                <?php echo __('Delete'); ?>
                            </button>
                        </li>
                    </ul>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<?php echo $paginationHtml; ?>

<?php
// Encode UI strings as JSON so they are safe to embed in the script block.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$msgNone   = json_encode(__('Please select at least one note to delete.'), $jsonFlags);
$msgOne    = json_encode(__('Delete this note? This cannot be undone.'), $jsonFlags);
$msgMany   = json_encode(__('Delete the selected notes? This cannot be undone.'), $jsonFlags);
?>
<script type="text/javascript">
jQuery(function ($) {
    var form = $('#side-notes-batch-form');
    var boxSelector = 'input[name="note_ids[]"]';

    // Select / deselect every row on this page.
    $('#side-notes-check-all').on('change', function () {
        form.find(boxSelector).prop('checked', this.checked);
    });

    // Keep the header checkbox in sync with the rows.
    form.on('change', boxSelector, function () {
        var boxes = form.find(boxSelector);
        $('#side-notes-check-all').prop('checked', boxes.length === boxes.filter(':checked').length);
    });

    // Confirm single-row deletes.
    form.on('click', '.side-notes-delete-single', function () {
        return confirm(<?php echo $msgOne; ?>);
    });

    // Inline note editing: swap the preview for a textarea in place.
    form.on('click', '.side-notes-edit-toggle', function () {
        var id = $(this).data('noteId');
        var editor = $('#note-edit-' + id);

        $('#note-view-' + id).hide();
        editor.show();
        $(this).closest('tr').addClass('is-editing');

        var textarea = editor.find('textarea');
        // Remember the original text so Cancel can restore it.
        if (textarea.data('original') === undefined) {
            textarea.data('original', textarea.val());
        }
        textarea.focus();
    });

    form.on('click', '.side-notes-cancel', function () {
        var id = $(this).data('noteId');
        var editor = $('#note-edit-' + id);
        var textarea = editor.find('textarea');

        if (textarea.data('original') !== undefined) {
            textarea.val(textarea.data('original'));
        }
        editor.hide();
        $('#note-view-' + id).show();
        $(this).closest('tr').removeClass('is-editing');
    });

    // Confirm batch deletes, and block the action when nothing is selected.
    $('#side-notes-batch-delete').on('click', function () {
        var count = form.find(boxSelector + ':checked').length;
        if (count === 0) {
            alert(<?php echo $msgNone; ?>);
            return false;
        }
        return confirm(count === 1 ? <?php echo $msgOne; ?> : <?php echo $msgMany; ?>);
    });
});
</script>

<?php else: ?>

<p><?php echo __('There are no notes yet.'); ?></p>

<?php endif; ?>

<?php echo foot(); ?>
