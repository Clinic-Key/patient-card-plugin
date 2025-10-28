<?php
if (!defined('ABSPATH')) {
    exit;
}

function patient_card_attachments_form() {
    if (!is_user_logged_in()) {
        return __('You need to be logged in to view this page.', 'patient-card-plugin');
    }

    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . 'patient_card';
    $patient_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save_attachments']) || isset($_POST['delete_attachment']))) {
        $attachments = $patient_card ? maybe_unserialize($patient_card->attachments) : [];

        if (isset($_POST['delete_attachment'])) {
            $delete_index = intval($_POST['delete_index']);
            if (isset($attachments[$delete_index])) {
                unset($attachments[$delete_index]);
                $attachments = array_values($attachments); // Reindex the array
            }
        } else {
            if (!empty($_FILES['attachments']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                foreach ($_FILES['attachments']['name'] as $key => $value) {
                    if ($_FILES['attachments']['name'][$key]) {
                        $file = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key]
                        ];
                        $upload = wp_handle_upload($file, ['test_form' => false]);
                        if (!isset($upload['error']) && isset($upload['url'])) {
                            $attachments[] = $upload['url'];
                        }
                    }
                }
            }
        }

        $attachments = maybe_serialize($attachments);

        if ($patient_card) {
            $wpdb->update(
                $table_name,
                [
                    'attachments' => $attachments
                ],
                ['user_id' => $user_id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'attachments' => $attachments
                ]
            );
        }

        ?><script>
    location.href = '<?php echo esc_url(home_url('/patient-dashboard/attachments/')); ?>';
</script>
<?php

    }
    

    $attachments = $patient_card ? maybe_unserialize($patient_card->attachments) : [];

    ob_start();
    ?>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="attachments"><?php _e('Please upload any relevant medical history or documents:', 'patient-card-plugin'); ?></label>
            <input type="file" id="attachments" name="attachments[]" multiple>
        </div>
        <div class="form-group">
            <label><?php _e('Existing Attachments:', 'patient-card-plugin'); ?></label>
            <?php if (!empty($attachments)) : ?>
                <ul>
                    <?php foreach ($attachments as $index => $attachment) : ?>
                        <li>
                            <a href="<?php echo esc_url($attachment); ?>" target="_blank"><?php echo basename($attachment); ?></a>
                            <button type="submit" name="delete_attachment" value="1" onclick="document.getElementById('delete_index').value = '<?php echo $index; ?>';"><?php _e('Delete', 'patient-card-plugin'); ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" id="delete_index" name="delete_index" value="">
            <?php else : ?>
                <p><?php _e('No attachments found.', 'patient-card-plugin'); ?></p>
            <?php endif; ?>
        </div>
        <input type="submit" name="save_attachments" value="<?php _e('Save Attachments', 'patient-card-plugin'); ?>">
    </form>
    <?php
    return ob_get_clean();
}

// Shortcode to display the attachments form
add_shortcode('patient_card_attachments_form', 'patient_card_attachments_form');
?>
