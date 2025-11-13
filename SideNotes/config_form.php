
<div class="field">
    <div class="two columns alpha">
        <label for="side_notes_preview_length"><?php echo __('Note Preview Length'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Number of characters to show in note previews on the dashboard.'); ?>
        </p>
        <input type="number"
               name="side_notes_preview_length"
               id="side_notes_preview_length"
               value="<?php echo get_option('side_notes_preview_length'); ?>"
               min="50"
               max="500" />
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="side_notes_timestamp_format"><?php echo __('Timestamp Format'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Format for displaying timestamps. Uses PHP date() format.'); ?>
        </p>
        <select name="side_notes_timestamp_format" id="side_notes_timestamp_format">
            <option value="F j, Y g:i A" <?php if (get_option('side_notes_timestamp_format') == 'F j, Y g:i A') echo 'selected'; ?>>
                <?php echo __('Month DD, YYYY hh:mm AM/PM'); ?> (<?php echo date('F j, Y g:i A'); ?>)
            </option>
            <option value="Y-m-d H:i" <?php if (get_option('side_notes_timestamp_format') == 'Y-m-d H:i') echo 'selected'; ?>>
                <?php echo __('YYYY-MM-DD HH:mm'); ?> (<?php echo date('Y-m-d H:i'); ?>)
            </option>
            <option value="m/d/Y g:i A" <?php if (get_option('side_notes_timestamp_format') == 'm/d/Y g:i A') echo 'selected'; ?>>
                <?php echo __('MM/DD/YYYY hh:mm AM/PM'); ?> (<?php echo date('m/d/Y g:i A'); ?>)
            </option>
            <option value="d/m/Y H:i" <?php if (get_option('side_notes_timestamp_format') == 'd/m/Y H:i') echo 'selected'; ?>>
                <?php echo __('DD/MM/YYYY HH:mm'); ?> (<?php echo date('d/m/Y H:i'); ?>)
            </option>
        </select>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="side_notes_dashboard_count"><?php echo __('Dashboard Note Count'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('Number of recent notes to display on the admin dashboard.'); ?>
        </p>
        <input type="number"
               name="side_notes_dashboard_count"
               id="side_notes_dashboard_count"
               value="<?php echo get_option('side_notes_dashboard_count'); ?>"
               min="1"
               max="50" />
    </div>
</div>
