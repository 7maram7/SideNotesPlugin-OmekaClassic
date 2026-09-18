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
function side_notes_sort_th($label, $field, $currentSort, $currentDir, $tab, $q = '')
{
    $isActive = ($currentSort === $field);
    // Toggle direction on the active column; new columns start ascending.
    $newDir = ($isActive && $currentDir === 'a') ? 'd' : 'a';

    $thClass = '';
    if ($isActive) {
        $thClass = ' class="sorting ' . ($currentDir === 'a' ? 'asc' : 'desc') . '"';
    }

    // Changing the sort returns to page 1 but keeps any active search.
    $params = array(
        'tab'        => $tab,
        'sort_field' => $field,
        'sort_dir'   => $newDir,
    );
    if ($q !== '') {
        $params['q'] = $q;
    }
    $url = url('side-notes/index/browse', $params);

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
 * Build a browse URL for a given page, keeping tab, sort and search.
 */
if (!function_exists('side_notes_page_url')):
function side_notes_page_url($page, $tab, $sort, $dir, $q = '')
{
    $params = array('tab' => $tab, 'sort_field' => $sort, 'sort_dir' => $dir);
    if ($page > 1) {
        $params['page'] = $page;
    }
    if ($q !== '') {
        $params['q'] = $q;
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

    /* Render all three row actions identically regardless of element type.
       One is an <a> and two are <button>s, and below 768px the admin theme
       restyles browse actions as filled buttons (.browse .edit), which framed
       one action and left the others as plain links. Scoping by #side-notes
       outranks that rule without needing !important. */
    #side-notes .action-links a,
    #side-notes .action-links button.link-button {
        display: inline;
        background: none;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
        margin: 0;
        min-height: 0;
        font: inherit;
        font-weight: normal;
        line-height: inherit;
        vertical-align: baseline;
        text-align: left;
        text-decoration: underline;
        white-space: nowrap;
    }
    #side-notes .action-links a,
    #side-notes .action-links button.side-notes-edit-toggle { color: #003576; }
    #side-notes .action-links button.side-notes-delete-single { color: #B00D00; }
    /* Keep each action on its own line so the narrow column reads cleanly. */
    .action-links li { display: block; margin-bottom: 2px; }
    .side-notes-count { float: left; margin: 0 0 10px; color: #666; line-height: 38px; }

    /* Batch action bar. The theme's .small class carries margin-bottom: 20px,
       which leaves dead space inside a bar padded by only 5px. Omeka cancels
       this for its own batch bars via
       ".items .browse-items .table-actions button { float: left; height: 25px }",
       a selector this markup doesn't match, so apply the same treatment here. */
    .table-actions button {
        margin-bottom: 0;
        height: 25px;
    }

    /* Search box, built as Omeka's own admin search rather than invented:
       the theme's fields are 36px tall and its search submit is a 36px maroon
       square carrying the magnifier glyph (see #search-form). Placement copies
       #search-users -- the search on Omeka's Users browse page -- a
       right-floated flex row above the table. No heights are guessed here;
       the field keeps its native 36px. */
    .side-notes-searchbar { overflow: hidden; }

    .side-notes-searchform {
        float: right;
        display: flex;
        align-items: center;
        margin: 0 0 10px;
    }

    /* The control is built exactly like #search-form: a full-width field with
       the submit absolutely positioned on top of it, and padding-right on the
       field reserving the button's space. That structure is also why the focus
       ring in Omeka's header appears to wrap the whole control -- the input IS
       the whole control, with the button laid over it. A sibling button would
       leave the ring stopping at the field's edge. */
    #side-notes-search {
        position: relative;
        display: block;
        width: 260px;
    }
    #side-notes-search input[type=text] {
        width: 100%;
        height: 36px;
        padding-right: 36px;
        margin: 0;
    }
    #side-notes-search button {
        position: absolute;
        top: 0;
        right: 0;
        width: 36px;
        height: 36px;
        min-height: 36px;
        padding: 0 5px;
        margin: 0;
        border: none;
        border-radius: 0;
        background: #82423B;
        color: #fff;
        text-shadow: none;
        text-indent: -9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.5s all;
    }
    #side-notes-search button:after {
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        content: "\f002";
        position: absolute;
        left: 0;
        right: 0;
        text-align: center;
        text-indent: 0;
    }
    #side-notes-search button:hover,
    #side-notes-search button:focus {
        background: #68302C;
        color: #fff;
    }
    .side-notes-clear {
        margin-left: 10px;
        white-space: nowrap;
    }
    .side-notes-empty { color: #4f4f4f; font-style: italic; }

    /* Dim the results very briefly while a search request is in flight. */
    .side-notes-busy { opacity: 0.55; transition: opacity 0.15s ease; }

    /* Pagination. Two things in the theme make this sit crooked: the list
       items are floated (so nothing shares a centre line) and its own field
       rule reads "height: 38x" -- invalid, so the box falls back to the 36px
       base height and rides low between the 38px arrow buttons. Flex-align the
       row and give all three the same 38px height. */
    .pagination {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    .pagination li {
        float: none;
        display: flex;
        align-items: center;
        height: 38px;
    }
    /* The theme gives these links margin: 0 0 20px 0. In a centred flex row it
       is the margin box that gets centred, so the arrow rendered ~10px above
       the page box. */
    .pagination li a { margin-bottom: 0; }
    .pagination .page-input {
        color: #4f4f4f;
        white-space: nowrap;
        line-height: normal;
    }
    .pagination .page-input form {
        display: flex;
        align-items: center;
        margin: 0;
        padding: 0;
    }
    .pagination .page-input input[type=text] {
        height: 38px;
        width: 40px;
        margin: 0 5px;
        padding: 0;
        line-height: normal;
        text-align: center;
    }

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

        /* 25px is fine for a mouse but a poor tap target, so the full-width
           mobile button keeps a comfortable height. */
        .table-actions button {
            height: auto;
            padding: 9px 10px;
        }

        /* Search spans the width with the square button alongside, matching
           what #search-users does on a phone (it simply stops floating). */
        #side-notes-search { float: none; }
        #side-notes-search input[type=text] {
            flex: 1 1 auto;
            width: auto;
            min-width: 0;
        }

        /* Pagination centres instead of floating, and the arrows/page box sit
           on one line rather than stacking. */
        .pagination { float: none; text-align: center; }
        .pagination li { float: none; display: inline-block; vertical-align: middle; }
        .pagination_previous { margin: 0 8px 0 0; }
        .pagination_next { margin: 0 0 0 8px; }

        .side-notes-count {
            float: none;
            line-height: 1.5;
            margin: 0 0 14px;
            text-align: center;
        }
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

<?php
// Native-style pagination markup, reused above and below the table.
ob_start();
if ($totalPages > 1):
?>
<ul class="pagination">
    <?php if ($currentPage > 1): ?>
    <li class="pagination_previous">
        <a href="<?php echo html_escape(side_notes_page_url($currentPage - 1, $currentTab, $currentSort, $currentDir, $searchQuery)); ?>"><?php echo __('Previous'); ?></a>
    </li>
    <?php endif; ?>

    <?php // Omeka's native pagination widget: a page box rather than numbered
          // links. The theme styles .page-input for exactly this, and it stays
          // compact however many pages there are. ?>
    <li class="page-input">
        <form method="get" action="<?php echo html_escape(url('side-notes/index/browse')); ?>">
            <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>">
            <input type="hidden" name="sort_field" value="<?php echo html_escape($currentSort); ?>">
            <input type="hidden" name="sort_dir" value="<?php echo html_escape($currentDir); ?>">
            <?php if ($searchQuery !== ''): ?>
            <input type="hidden" name="q" value="<?php echo html_escape($searchQuery); ?>">
            <?php endif; ?>
            <?php echo __('Page'); ?>
            <input type="text" name="page" value="<?php echo (int)$currentPage; ?>"
                   aria-label="<?php echo __('Page number'); ?>">
            <?php echo __('of'); ?> <?php echo (int)$totalPages; ?>
        </form>
    </li>

    <?php if ($currentPage < $totalPages): ?>
    <li class="pagination_next">
        <a href="<?php echo html_escape(side_notes_page_url($currentPage + 1, $currentTab, $currentSort, $currentDir, $searchQuery)); ?>"><?php echo __('Next'); ?></a>
    </li>
    <?php endif; ?>
</ul>
<?php
endif;
$paginationHtml = ob_get_clean();
?>

<?php
// The search sits in its own row above the table, right-aligned, exactly where
// Omeka puts the search on its Users browse page (#search-users). Keeping it
// out of the batch bar matters for two reasons: a form nested inside the batch
// POST form would be invalid HTML, and anchoring it here means it does not
// shift position when a search returns nothing and the batch button is absent.
?>
<div class="side-notes-searchbar">
    <form method="get" class="side-notes-searchform"
          action="<?php echo html_escape(url('side-notes/index/browse')); ?>">
        <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>">
        <input type="hidden" name="sort_field" value="<?php echo html_escape($currentSort); ?>">
        <input type="hidden" name="sort_dir" value="<?php echo html_escape($currentDir); ?>">
        <?php // The field and its overlaid submit form one positioned control. ?>
        <span id="side-notes-search">
            <input type="text" id="side-notes-q" name="q" autocomplete="off"
                   value="<?php echo html_escape($searchQuery); ?>"
                   placeholder="<?php echo __('Search notes'); ?>"
                   aria-label="<?php echo __('Search note text'); ?>">
            <button type="submit"><?php echo __('Search'); ?></button>
        </span>
        <a class="side-notes-clear" id="side-notes-clear"
           style="<?php echo ($searchQuery === '') ? 'display:none;' : ''; ?>"
           href="<?php echo html_escape(url('side-notes/index/browse', array('tab' => $currentTab))); ?>"><?php echo __('Clear'); ?></a>
    </form>
</div>

<div id="side-notes-results">
<?php if (!empty($notes)): ?>

<?php echo $paginationHtml; ?>

<p class="side-notes-count">
    <?php if ($searchQuery !== ''): ?>
        <?php echo __('%s notes matching', $totalResults); ?>
        &ldquo;<?php echo html_escape($searchQuery); ?>&rdquo;
    <?php else: ?>
        <?php echo __('%s notes total', $totalResults); ?>
    <?php endif; ?>
</p>

<form method="post" id="side-notes-batch-form"
      action="<?php echo html_escape(url('side-notes/index/delete')); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>">
    <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>">
    <input type="hidden" name="sort_field" value="<?php echo html_escape($currentSort); ?>">
    <input type="hidden" name="sort_dir" value="<?php echo html_escape($currentDir); ?>">
    <input type="hidden" name="page" value="<?php echo (int)$currentPage; ?>">
    <input type="hidden" name="q" value="<?php echo html_escape($searchQuery); ?>">

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
                <?php echo side_notes_sort_th(__('Created'), 'created', $currentSort, $currentDir, $currentTab, $searchQuery); ?>
                <?php echo side_notes_sort_th(__('Modified'), 'modified', $currentSort, $currentDir, $currentTab, $searchQuery); ?>
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

<?php else: ?>

<?php if ($searchQuery !== ''): ?>
<p class="side-notes-empty">
    <?php echo __('No notes match'); ?>
    &ldquo;<?php echo html_escape($searchQuery); ?>&rdquo;.
</p>
<?php else: ?>
<p class="side-notes-empty"><?php echo __('There are no notes yet.'); ?></p>
<?php endif; ?>

<?php endif; ?>
</div><?php // /#side-notes-results ?>

<?php
// Encode strings and URLs as JSON so they are safe to embed in the script.
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$msgNone   = json_encode(__('Please select at least one note to delete.'), $jsonFlags);
$msgOne    = json_encode(__('Delete this note? This cannot be undone.'), $jsonFlags);
$msgMany   = json_encode(__('Delete the selected notes? This cannot be undone.'), $jsonFlags);
$jsBase    = json_encode(url('side-notes/index/browse'), $jsonFlags);
$jsTab     = json_encode($currentTab, $jsonFlags);
$jsSort    = json_encode($currentSort, $jsonFlags);
$jsDir     = json_encode($currentDir, $jsonFlags);
?>
<script type="text/javascript">
jQuery(function ($) {
    var results     = $('#side-notes-results');
    var boxSelector = 'input[name="note_ids[]"]';

    // Every handler is delegated from the results container. The container
    // itself persists while the live search below swaps its contents, so
    // bindings survive a refresh of the table.
    results.on('change', '#side-notes-check-all', function () {
        results.find(boxSelector).prop('checked', this.checked);
    });

    results.on('change', boxSelector, function () {
        var boxes = results.find(boxSelector);
        $('#side-notes-check-all')
            .prop('checked', boxes.length > 0 && boxes.length === boxes.filter(':checked').length);
    });

    results.on('click', '.side-notes-delete-single', function () {
        return confirm(<?php echo $msgOne; ?>);
    });

    // Inline note editing: swap the preview for a textarea in place.
    results.on('click', '.side-notes-edit-toggle', function () {
        var id     = $(this).data('noteId');
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

    results.on('click', '.side-notes-cancel', function () {
        var id       = $(this).data('noteId');
        var editor   = $('#note-edit-' + id);
        var textarea = editor.find('textarea');

        if (textarea.data('original') !== undefined) {
            textarea.val(textarea.data('original'));
        }
        editor.hide();
        $('#note-view-' + id).show();
        $(this).closest('tr').removeClass('is-editing');
    });

    // Confirm batch deletes, and block the action when nothing is selected.
    results.on('click', '#side-notes-batch-delete', function () {
        var count = results.find(boxSelector + ':checked').length;
        if (count === 0) {
            alert(<?php echo $msgNone; ?>);
            return false;
        }
        return confirm(count === 1 ? <?php echo $msgOne; ?> : <?php echo $msgMany; ?>);
    });

    /* ---- Live search ----------------------------------------------------
       Results are fetched and swapped in place. An earlier version submitted
       the form on a timer, which reloaded the page mid-word and discarded any
       keystrokes made while the request was in flight -- that is why the space
       bar appeared to be ignored. Nothing here touches the field, so typing is
       never interrupted. The form still works as a plain GET without JS. */
    var input = $('#side-notes-q');
    if (!input.length) {
        return;
    }
    var clear    = $('#side-notes-clear');
    var timer    = null;
    var request  = null;
    var lastSent = input.val();

    function buildUrl(term) {
        return <?php echo $jsBase; ?> + '?' + $.param({
            tab:        <?php echo $jsTab; ?>,
            sort_field: <?php echo $jsSort; ?>,
            sort_dir:   <?php echo $jsDir; ?>,
            q:          term
        });
    }

    function search(term) {
        if (request) {
            request.abort();
        }
        results.addClass('side-notes-busy');

        request = $.get(buildUrl(term))
            .done(function (html) {
                var fresh;
                try {
                    fresh = new DOMParser()
                        .parseFromString(html, 'text/html')
                        .getElementById('side-notes-results');
                } catch (e) {
                    return;
                }
                if (!fresh) {
                    return; // e.g. session expired and we got the login page
                }
                results.html(fresh.innerHTML);
                clear.toggle(term !== '');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', buildUrl(term));
                }
            })
            .always(function () {
                results.removeClass('side-notes-busy');
                request = null;
            });
    }

    input.on('input', function () {
        var field = this;
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(function () {
            if (field.value !== lastSent) {
                lastSent = field.value;
                search(lastSent);
            }
        }, 300);
    });

    // Enter refreshes in place rather than reloading the whole page.
    input.closest('form').on('submit', function (e) {
        if (!window.DOMParser) {
            return; // let the browser submit normally
        }
        e.preventDefault();
        if (timer) {
            clearTimeout(timer);
        }
        lastSent = input.val();
        search(lastSent);
    });
});
</script>

<?php echo foot(); ?>
