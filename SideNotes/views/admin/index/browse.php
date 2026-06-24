<?php
$pageTitle = __('Notes');
echo head(array('title' => $pageTitle, 'bodyclass' => 'side-notes browse'));

/**
 * Render a native Omeka sortable column header.
 *
 * Reproduces the markup Omeka's admin theme styles: a <th> containing
 * <a><span>Label</span></a>. The active column gets "sorting asc|desc"
 * so the theme shows the bold label and the directional sort icon.
 */
if (!function_exists('side_notes_sort_th')):
function side_notes_sort_th($label, $field, $currentSort, $currentDir, $tab)
{
    $isActive = ($currentSort === $field);
    // Toggle direction on the active column; new columns start ascending.
    $newDir = ($isActive && $currentDir === 'a') ? 'd' : 'a';

    $thClass = '';
    if ($isActive) {
        $thClass = ' class="sorting ' . ($currentDir === 'a' ? 'asc' : 'desc') . '"';
    }

    $url = url('side-notes/index/browse', array(
        'tab'        => $tab,
        'sort_field' => $field,
        'sort_dir'   => $newDir,
    ));

    return '<th' . $thClass . '><a href="' . html_escape($url) . '">'
        . '<span>' . html_escape($label) . '</span></a></th>';
}
endif;
?>

<style>
    /* Only override needed: make the POST-based Delete look like the other
       native action links instead of a full button. Everything else
       (tabs, table, headers, rows) inherits the admin theme. */
    .action-links form { display: inline; margin: 0; padding: 0; }
    .action-links button {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        cursor: pointer;
        font: inherit;
        color: #B00D00; /* native delete red */
    }
    .action-links button:hover { text-decoration: underline; }
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

<table id="side-notes">
    <thead>
        <tr>
            <th><?php echo __('Record'); ?></th>
            <th><?php echo __('Note'); ?></th>
            <?php echo side_notes_sort_th(__('Created'), 'created', $currentSort, $currentDir, $currentTab); ?>
            <?php echo side_notes_sort_th(__('Modified'), 'modified', $currentSort, $currentDir, $currentTab); ?>
            <th><?php echo __('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($notes as $i => $note): ?>
        <tr class="<?php echo ($i % 2) ? 'even' : 'odd'; ?>">
            <td>
                <a href="<?php echo html_escape($note['record_url']); ?>">
                    <?php echo html_escape($note['record_title']); ?>
                </a>
            </td>
            <td>
                <?php
                $preview = $note['note'];
                if (mb_strlen($preview) > $previewLength) {
                    $preview = mb_substr($preview, 0, $previewLength) . '...';
                }
                echo html_escape($preview);
                ?>
            </td>
            <td>
                <?php if (!empty($note['created'])): ?>
                    <?php echo html_escape(date($timestampFormat, strtotime($note['created']))); ?>
                    <?php if (!empty($note['created_by_username'])): ?>
                        <br><small><?php echo __('by %s', html_escape($note['created_by_username'])); ?></small>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($note['modified'])): ?>
                    <?php echo html_escape(date($timestampFormat, strtotime($note['modified']))); ?>
                    <?php if (!empty($note['modified_by_username'])): ?>
                        <br><small><?php echo __('by %s', html_escape($note['modified_by_username'])); ?></small>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>
                <ul class="action-links">
                    <li><a href="<?php echo html_escape($note['record_url']); ?>"><?php echo __('View'); ?></a></li>
                    <li>
                        <form class="side-notes-delete" method="post"
                              action="<?php echo html_escape(url('side-notes/index/delete')); ?>"
                              onsubmit="return confirm('<?php echo js_escape(__('Delete this note? This cannot be undone.')); ?>');">
                            <input type="hidden" name="note_id" value="<?php echo (int)$note['id']; ?>">
                            <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo html_escape($csrfToken); ?>">
                            <button type="submit"><?php echo __('Delete'); ?></button>
                        </form>
                    </li>
                </ul>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>

<p><?php echo __('There are no notes yet.'); ?></p>

<?php endif; ?>

<?php echo foot(); ?>
