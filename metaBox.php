<?php 
/**
 * Adds a meta box to the post editing screen
 */

function nowpushnotifys_exception_meta() {
    $pluginSettings = get_option('now_push_notify_name');
    if (!empty($pluginSettings['postTypes']) && is_array($pluginSettings['postTypes'])) {
        foreach ($pluginSettings['postTypes'] as $screen) {
            add_meta_box('prfx_meta', __('Now Push Notify', 'iseNotifications-textdomain'), 'iseNotifications_meta_callback', $screen, 'side', 'high');
        }
    }
}
add_action( 'add_meta_boxes', 'nowpushnotifys_exception_meta' );
 
/**
 * Outputs the content of the meta box
 */
 
function iseNotifications_meta_callback( $post ) {
    wp_nonce_field(basename(__FILE__), 'nowpushnotifys_nonce');
    $nowpushnotifys_stored_meta = get_post_meta( $post->ID );
    ?>
 
 <p>
    <div class="iseNotifications-row-content">
        <label for="nowpushnotifys-checkbox">
            <input type="checkbox" name="nowpushnotifys-checkbox" id="nowpushnotifys-checkbox" value="1" <?php checked($post->post_status === "auto-draft" || $nowpushnotifys_stored_meta['nowpushnotifys-checkbox'][0], 1) ?> />
            <?php _e('Send notifications for this post', 'iseNotifications-textdomain')?>
        </label>
 
    </div>
</p>   
 
    <?php
}
 
/**
 * Saves the custom meta input
 */
function nowpushnotifys_meta_save($post_id) {
 
    // Checks save status - overcome autosave, etc.
    $is_autosave = wp_is_post_autosave($post_id);
    $is_revision = wp_is_post_revision($post_id);
    $is_valid_nonce = (isset($_POST['nowpushnotifys_nonce']) && wp_verify_nonce($_POST['nowpushnotifys_nonce'], basename(__FILE__))) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ($is_autosave || $is_revision || !$is_valid_nonce) {
        return;
    }
 
    // Checks for input and saves - save checked as yes and unchecked at no
    if (isset( $_POST['nowpushnotifys-checkbox'])) {
        update_post_meta($post_id, 'nowpushnotifys-checkbox', 1);
    } else {
        update_post_meta($post_id, 'nowpushnotifys-checkbox', 0);
    }
 
}
add_action('save_post', 'nowpushnotifys_meta_save');

?>