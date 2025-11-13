<?php
$pageTitle = __('Browse Notes');
echo head(array('title' => $pageTitle, 'bodyclass' => 'side-notes browse'));
?>

<style>
    .side-notes-tabs {
        margin-bottom: 20px;
        border-bottom: 2px solid #ddd;
    }
    .side-notes-tabs a {
        display: inline-block;
        padding: 10px 20px;
        margin-right: 5px;
        text-decoration: none;
        color: #333;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-bottom: none;
    }
    .side-notes-tabs a.active {
        background: #fff;
        font-weight: bold;
        border-bottom: 2px solid #fff;
        margin-bottom: -2px;
    }
    .side-notes-tabs a:hover {
        background: #e9e9e9;
    }
    .side-notes-sorting {
        margin-bottom: 20px;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #ddd;
    }
    .side-notes-sorting label {
        margin-right: 10px;
        font-weight: bold;
    }
    .side-notes-sorting select {
        margin-right: 20px;
    }
    .notes-table {
        width: 100%;
        border-collapse: collapse;
    }
    .notes-table th {
        background: #f5f5f5;
        padding: 10px;
        text-align: left;
        border-bottom: 2px solid #ddd;
        font-weight: bold;
    }
    .notes-table td {
        padding: 10px;
        border-bottom: 1px solid #e7e7e7;
        vertical-align: top;
    }
    .notes-table tr:hover {
        background: #f9f9f9;
    }
    .note-preview {
        color: #555;
        max-width: 400px;
    }
    .note-timestamp {
        color: #888;
        font-size: 0.9em;
    }
    .note-user {
        color: #666;
        font-size: 0.9em;
    }
    .note-actions {
        white-space: nowrap;
    }
    .delete-note-form {
        display: inline;
    }
    .no-notes {
        padding: 40px;
        text-align: center;
        color: #888;
        font-style: italic;
    }
</style>

<h1><?php echo $pageTitle; ?></h1>

<div class="side-notes-tabs">
    <a href="<?php echo url('side-notes/browse', array('tab' => 'items')); ?>"
       class="<?php echo ($currentTab === 'items') ? 'active' : ''; ?>">
        <?php echo __('Item Notes'); ?>
    </a>
    <a href="<?php echo url('side-notes/browse', array('tab' => 'collections')); ?>"
       class="<?php echo ($currentTab === 'collections') ? 'active' : ''; ?>">
        <?php echo __('Collection Notes'); ?>
    </a>
</div>

<?php if (!empty($notes)): ?>

<div class="side-notes-sorting">
    <form method="get" action="<?php echo url('side-notes/browse'); ?>">
        <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>" />

        <label for="sort"><?php echo __('Sort by:'); ?></label>
        <select name="sort" id="sort" onchange="this.form.submit()">
            <option value="created" <?php echo ($currentSort === 'created') ? 'selected' : ''; ?>>
                <?php echo __('Date Created'); ?>
            </option>
            <option value="modified" <?php echo ($currentSort === 'modified') ? 'selected' : ''; ?>>
                <?php echo __('Date Modified'); ?>
            </option>
            <option value="created_by" <?php echo ($currentSort === 'created_by') ? 'selected' : ''; ?>>
                <?php echo __('Created By User'); ?>
            </option>
            <option value="modified_by" <?php echo ($currentSort === 'modified_by') ? 'selected' : ''; ?>>
                <?php echo __('Modified By User'); ?>
            </option>
        </select>

        <label for="order"><?php echo __('Order:'); ?></label>
        <select name="order" id="order" onchange="this.form.submit()">
            <option value="DESC" <?php echo ($currentOrder === 'DESC') ? 'selected' : ''; ?>>
                <?php echo __('Descending'); ?>
            </option>
            <option value="ASC" <?php echo ($currentOrder === 'ASC') ? 'selected' : ''; ?>>
                <?php echo __('Ascending'); ?>
            </option>
        </select>
    </form>
</div>

<table class="notes-table">
    <thead>
        <tr>
            <th><?php echo __('Record'); ?></th>
            <th><?php echo __('Note Preview'); ?></th>
            <th><?php echo __('Created'); ?></th>
            <th><?php echo __('Modified'); ?></th>
            <th><?php echo __('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($notes as $note): ?>
        <tr>
            <td>
                <a href="<?php echo html_escape($note['record_url']); ?>">
                    <?php echo html_escape($note['record_title']); ?>
                </a>
            </td>
            <td class="note-preview">
                <?php
                $preview = $note['note'];
                if (mb_strlen($preview) > $previewLength) {
                    $preview = mb_substr($preview, 0, $previewLength) . '...';
                }
                echo html_escape($preview);
                ?>
            </td>
            <td>
                <div class="note-timestamp">
                    <?php echo date($timestampFormat, strtotime($note['created'])); ?>
                </div>
                <?php if (!empty($note['created_by_username'])): ?>
                <div class="note-user">
                    <?php echo __('by %s', html_escape($note['created_by_username'])); ?>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <div class="note-timestamp">
                    <?php echo date($timestampFormat, strtotime($note['modified'])); ?>
                </div>
                <?php if (!empty($note['modified_by_username'])): ?>
                <div class="note-user">
                    <?php echo __('by %s', html_escape($note['modified_by_username'])); ?>
                </div>
                <?php endif; ?>
            </td>
            <td class="note-actions">
                <a href="<?php echo html_escape($note['record_url']); ?>" class="view-record">
                    <?php echo __('View'); ?>
                </a>
                |
                <form method="post" action="<?php echo url('side-notes/delete'); ?>" class="delete-note-form"
                      onsubmit="return confirm('<?php echo __('Are you sure you want to delete this note?'); ?>');">
                    <input type="hidden" name="id" value="<?php echo $note['id']; ?>" />
                    <input type="hidden" name="tab" value="<?php echo html_escape($currentTab); ?>" />
                    <button type="submit" class="delete" style="background:none;border:none;color:#c00;cursor:pointer;text-decoration:underline;">
                        <?php echo __('Delete'); ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>

<div class="no-notes">
    <?php echo __('No notes found.'); ?>
</div>

<?php endif; ?>

<?php echo foot(); ?>
