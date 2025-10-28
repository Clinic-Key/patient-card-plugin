<?php
if (!defined('ABSPATH')) {
    exit;
}

class Patient_Card {
    public static function init() {
        add_action('init', array(__CLASS__, 'load_textdomain'));
        add_shortcode('patient_card', array(__CLASS__, 'display_patient_card'));
        add_shortcode('edit_patient_card', array(__CLASS__, 'edit_patient_card'));
        add_shortcode('add_edit_patient_form', array(__CLASS__, 'add_edit_patient_form'));
    }

    public static function load_textdomain() {
        load_plugin_textdomain('patient-card-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public static function display_patient_card() {
        if (!is_user_logged_in()) {
            return __('You need to be logged in to view this page.', 'patient-card-plugin');
        }

        $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $first_name = $user_info->first_name;
        $last_name = $user_info->last_name;

        global $wpdb;
        $table_name = $wpdb->prefix . 'patient_card';
        $patient_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

        $dob = $patient_card->dob ?? '';
        $blood_group = $patient_card->blood_group ?? '';
        $address = $patient_card->address ?? '';
        $marital_status = $patient_card->marital_status ?? '';
        $weight = $patient_card->weight ?? '';
        $height = $patient_card->height ?? '';
        $insurance = $patient_card->insurance ?? '';
        $profile_image_url = $patient_card && $patient_card->profile_image ? esc_url($patient_card->profile_image) : plugin_dir_url(__FILE__) . 'images/placeholder.png';

        ob_start();
        ?>
        <div class="patient-card">
            <div class="top-div"><h3 class="patient-card-title">Clinic Key Patient Card</h3></div>
            <div class="patient-card-content">
                <div class="patient-card-left">
                    <img src="<?php echo $profile_image_url; ?>" alt="Patient Image">
                    <p style="margin-block-end: 0px;"><strong><?php _e('Blood Group:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($blood_group); ?></p>
                    <p><?php echo esc_html($marital_status); ?></p>
                </div>
                <div class="patient-card-right">
                    <p><strong><?php _e('Full Name:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($first_name . ' ' . $last_name); ?></p>
                    <p><strong><?php _e('Date of Birth:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($dob); ?></p>
                    <p><strong><?php _e('Weight:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($weight); ?> kg, <strong><?php _e('Height:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($height); ?> cm</p>
                    <p><strong><?php _e('Insurance:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($insurance); ?></p>
                    <p><strong><?php _e('Address:', 'patient-card-plugin'); ?></strong> <?php echo esc_html($address); ?></p>
                </div>
                <div class="edit-icon">
                    <a href="<?php echo esc_url(home_url('/patient-dashboard/patient-details')); ?>"><i class="fas fa-edit"></i></a>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    public static function edit_patient_card() {
        if (!is_user_logged_in()) {
            return __('You need to be logged in to view this page.', 'patient-card-plugin');
        }

        $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $first_name = $user_info->first_name;
        $last_name = $user_info->last_name;

        global $wpdb;
        $table_name = $wpdb->prefix . 'patient_card';
        $patient_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_patient_card'])) {
            $dob = sanitize_text_field($_POST['dob']);
            $blood_group = sanitize_text_field($_POST['blood_group']);
            $address = sanitize_textarea_field($_POST['address']);
            $marital_status = sanitize_text_field($_POST['marital_status']);
            $weight = sanitize_text_field($_POST['weight']);
            $height = sanitize_text_field($_POST['height']);
            $insurance = sanitize_text_field($_POST['insurance']);
            $attachments = [];
            $profile_image = '';

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

            if (!empty($_FILES['profile_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_image', 0);
                if (!is_wp_error($attachment_id)) {
                    $profile_image = wp_get_attachment_url($attachment_id);
                }
            } else {
                $profile_image = $patient_card ? $patient_card->profile_image : '';
            }

            $attachments = maybe_serialize($attachments);

            if ($patient_card) {
                $wpdb->update(
                    $table_name,
                    [
                        'dob' => $dob,
                        'blood_group' => $blood_group,
                        'address' => $address,
                        'profile_image' => $profile_image,
                        'attachments' => $attachments,
                        'marital_status' => $marital_status,
                        'weight' => $weight,
                        'height' => $height,
                        'insurance' => $insurance
                    ],
                    ['user_id' => $user_id]
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    [
                        'user_id' => $user_id,
                        'dob' => $dob,
                        'blood_group' => $blood_group,
                        'address' => $address,
                        'profile_image' => $profile_image,
                        'attachments' => $attachments,
                        'marital_status' => $marital_status,
                        'weight' => $weight,
                        'height' => $height,
                        'insurance' => $insurance
                    ]
                );
            }

            echo '<p>' . __('Information saved successfully.', 'patient-card-plugin') . '</p>';
        }

        $dob = $patient_card ? esc_attr($patient_card->dob) : '';
        $blood_group = $patient_card ? esc_attr($patient_card->blood_group) : '';
        $address = $patient_card ? esc_textarea($patient_card->address) : '';
        $marital_status = $patient_card ? esc_attr($patient_card->marital_status) : '';
        $weight = $patient_card ? esc_attr($patient_card->weight) : '';
        $height = $patient_card ? esc_attr($patient_card->height) : '';
        $insurance = $patient_card ? esc_attr($patient_card->insurance) : '';
        $profile_image_url = $patient_card && $patient_card->profile_image ? esc_url($patient_card->profile_image) : plugin_dir_url(__FILE__) . 'images/placeholder.png';

        ob_start();
        ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="dob"><?php _e('Date of Birth:', 'patient-card-plugin'); ?></label>
                <input type="date" id="dob" name="dob" value="<?php echo $dob; ?>" required>
            </div>
            <div class="form-group">
                <label for="blood_group"><?php _e('Blood Group:', 'patient-card-plugin'); ?></label>
                <input type="text" id="blood_group" name="blood_group" value="<?php echo $blood_group; ?>" required>
            </div>
            <div class="form-group">
                <label for="address"><?php _e('Address:', 'patient-card-plugin'); ?></label>
                <textarea id="address" name="address" required><?php echo $address; ?></textarea>
            </div>
            <div class="form-group">
                <label for="marital_status"><?php _e('Marital Status:', 'patient-card-plugin'); ?></label>
                <select id="marital_status" name="marital_status">
                    <option value="single" <?php selected($marital_status, 'single'); ?>><?php _e('Single', 'patient-card-plugin'); ?></option>
                    <option value="married" <?php selected($marital_status, 'married'); ?>><?php _e('Married', 'patient-card-plugin'); ?></option>
                    <option value="divorced" <?php selected($marital_status, 'divorced'); ?>><?php _e('Divorced', 'patient-card-plugin'); ?></option>
                    <option value="widowed" <?php selected($marital_status, 'widowed'); ?>><?php _e('Widowed', 'patient-card-plugin'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="weight"><?php _e('Weight (kg):', 'patient-card-plugin'); ?></label>
                <input type="number" id="weight" name="weight" value="<?php echo $weight; ?>">
            </div>
            <div class="form-group">
                <label for="height"><?php _e('Height (cm):', 'patient-card-plugin'); ?></label>
                <input type="number" id="height" name="height" value="<?php echo $height; ?>">
            </div>
            <div class="form-group">
                <label for="insurance"><?php _e('Insurance:', 'patient-card-plugin'); ?></label>
                <select id="insurance" name="insurance">
                    <option value="yes" <?php selected($insurance, 'yes'); ?>><?php _e('Yes', 'patient-card-plugin'); ?></option>
                    <option value="no" <?php selected($insurance, 'no'); ?>><?php _e('No', 'patient-card-plugin'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="attachments"><?php _e('Attachments:', 'patient-card-plugin'); ?></label>
                <input type="file" id="attachments" name="attachments[]" multiple>
            </div>
            <div class="form-group">
                <label for="profile_image"><?php _e('Profile Image:', 'patient-card-plugin'); ?></label>
                <input type="file" id="profile_image" name="profile_image">
                <?php if ($profile_image_url) : ?>
                    <img src="<?php echo esc_url($profile_image_url); ?>" alt="Profile Image" style="max-width: 100px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            <input type="submit" name="save_patient_card" value="<?php _e('Save', 'patient-card-plugin'); ?>">
        </form>
        <?php
        return ob_get_clean();
    }

    public static function add_edit_patient_form() {
        if (!is_user_logged_in()) {
            return __('You need to be logged in to view this page.', 'patient-card-plugin');
        }

        $user_id = get_current_user_id();
        $user_info = get_userdata($user_id);
        $first_name = $user_info->first_name;
        $last_name = $user_info->last_name;

        global $wpdb;
        $table_name = $wpdb->prefix . 'patient_card';
        $patient_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

        $profile_image_url = ''; // Initialize the variable to avoid undefined variable warnings

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_patient_details'])) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $dob = sanitize_text_field($_POST['dob']);
            $blood_group = sanitize_text_field($_POST['blood_group']);
            $address = sanitize_textarea_field($_POST['address']);
            $marital_status = sanitize_text_field($_POST['marital_status']);
            $weight = sanitize_text_field($_POST['weight']);
            $height = sanitize_text_field($_POST['height']);
            $insurance = sanitize_text_field($_POST['insurance']);
            $profile_image = '';

            // Handle profile image upload
            if (!empty($_FILES['profile_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_image', 0);
                if (!is_wp_error($attachment_id)) {
                    $profile_image = wp_get_attachment_url($attachment_id);
                }
            } else {
                $profile_image = $patient_card->profile_image ?? '';
            }

            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
            ));

            if ($patient_card) {
                $wpdb->update(
                    $table_name,
                    array(
                        'dob' => $dob,
                        'blood_group' => $blood_group,
                        'address' => $address,
                        'profile_image' => $profile_image,
                        'marital_status' => $marital_status,
                        'weight' => $weight,
                        'height' => $height,
                        'insurance' => $insurance
                    ),
                    array('user_id' => $user_id)
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'dob' => $dob,
                        'blood_group' => $blood_group,
                        'address' => $address,
                        'profile_image' => $profile_image,
                        'marital_status' => $marital_status,
                        'weight' => $weight,
                        'height' => $height,
                        'insurance' => $insurance
                    )
                );
            }

            echo '<p>' . __('Information saved successfully.', 'patient-card-plugin') . '</p>';
        } else {
            $dob = $patient_card->dob ?? '';
            $blood_group = $patient_card->blood_group ?? '';
            $address = $patient_card->address ?? '';
            $marital_status = $patient_card->marital_status ?? '';
            $weight = $patient_card->weight ?? '';
            $height = $patient_card->height ?? '';
            $insurance = $patient_card->insurance ?? '';
            $profile_image_url = $patient_card->profile_image ?? plugin_dir_url(__FILE__) . 'images/placeholder.png';
        }

        ob_start();
        ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="first_name"><?php _e('First Name:', 'patient-card-plugin'); ?></label>
                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name"><?php _e('Last Name:', 'patient-card-plugin'); ?></label>
                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="dob"><?php _e('Date of Birth:', 'patient-card-plugin'); ?></label>
                <input type="date" id="dob" name="dob" value="<?php echo esc_attr($dob); ?>" required>
            </div>
            <div class="form-group">
                <label for="blood_group"><?php _e('Blood Group:', 'patient-card-plugin'); ?></label>
                <select id="blood_group" name="blood_group" required>
                    <option value="A+" <?php selected($blood_group, 'A+'); ?>>A+</option>
                    <option value="A-" <?php selected($blood_group, 'A-'); ?>>A-</option>
                    <option value="B+" <?php selected($blood_group, 'B+'); ?>>B+</option>
                    <option value="B-" <?php selected($blood_group, 'B-'); ?>>B-</option>
                    <option value="AB+" <?php selected($blood_group, 'AB+'); ?>>AB+</option>
                    <option value="AB-" <?php selected($blood_group, 'AB-'); ?>>AB-</option>
                    <option value="O+" <?php selected($blood_group, 'O+'); ?>>O+</option>
                    <option value="O-" <?php selected($blood_group, 'O-'); ?>>O-</option>
                </select>
            </div>
            <div class="form-group">
                <label for="address"><?php _e('Address:', 'patient-card-plugin'); ?></label>
                <textarea id="address" name="address" required><?php echo esc_textarea($address); ?></textarea>
            </div>
            <div class="form-group">
                <label for="marital_status"><?php _e('Marital Status:', 'patient-card-plugin'); ?></label>
                <select id="marital_status" name="marital_status">
                    <option value="single" <?php selected($marital_status, 'single'); ?>><?php _e('Single', 'patient-card-plugin'); ?></option>
                    <option value="married" <?php selected($marital_status, 'married'); ?>><?php _e('Married', 'patient-card-plugin'); ?></option>
                    <option value="divorced" <?php selected($marital_status, 'divorced'); ?>><?php _e('Divorced', 'patient-card-plugin'); ?></option>
                    <option value="widowed" <?php selected($marital_status, 'widowed'); ?>><?php _e('Widowed', 'patient-card-plugin'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="weight"><?php _e('Weight (kg):', 'patient-card-plugin'); ?></label>
                <input type="number" id="weight" name="weight" value="<?php echo esc_attr($weight); ?>">
            </div>
            <div class="form-group">
                <label for="height"><?php _e('Height (cm):', 'patient-card-plugin'); ?></label>
                <input type="number" id="height" name="height" value="<?php echo esc_attr($height); ?>">
            </div>
            <div class="form-group">
                <label for="insurance"><?php _e('Insurance:', 'patient-card-plugin'); ?></label>
                <select id="insurance" name="insurance">
                    <option value="yes" <?php selected($insurance, 'yes'); ?>><?php _e('Yes', 'patient-card-plugin'); ?></option>
                    <option value="no" <?php selected($insurance, 'no'); ?>><?php _e('No', 'patient-card-plugin'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="profile_image"><?php _e('Profile Image:', 'patient-card-plugin'); ?></label>
                <input type="file" id="profile_image" name="profile_image">
                <?php if ($profile_image_url) : ?>
                    <img src="<?php echo esc_url($profile_image_url); ?>" alt="Profile Image" style="max-width: 100px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            <input type="submit" name="save_patient_details" value="<?php _e('Save', 'patient-card-plugin'); ?>">
        </form>
        <?php
        return ob_get_clean();
    }
}

Patient_Card::init();
