<?php
if (!defined('ABSPATH')) {
    exit;
}

function display_patient_card_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'patient_card';

    // Fetch all patients with their patient_card data
    $patients = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, 
            fn.meta_value AS first_name, 
            ln.meta_value AS last_name, 
            p.*
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
        LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id = u.ID AND fn.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id = u.ID AND ln.meta_key = 'last_name'
        LEFT JOIN {$table_name} p ON p.user_id = u.ID
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND um.meta_value LIKE '%\"Patient\"%'
    ");

    ?>
    <div class="wrap">
        <h1><?php _e('All Patients', 'patient-card-plugin'); ?></h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('ID', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Username', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Email', 'patient-card-plugin'); ?></th>
                    <th><?php _e('First Name', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Last Name', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Date of Birth', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Blood Group', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Address', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Marital Status', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Weight (kg)', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Height (cm)', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Insurance', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Profile Image', 'patient-card-plugin'); ?></th>
                    <th><?php _e('Attachments', 'patient-card-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($patients) : ?>
                    <?php foreach ($patients as $patient) : ?>
                        <tr>
                            <td><?php echo esc_html($patient->ID); ?></td>
                            <td><?php echo esc_html($patient->user_login); ?></td>
                            <td><?php echo esc_html($patient->user_email); ?></td>
                            <td><?php echo esc_html($patient->first_name); ?></td>
                            <td><?php echo esc_html($patient->last_name); ?></td>
                            <td><?php echo esc_html($patient->dob); ?></td>
                            <td><?php echo esc_html($patient->blood_group); ?></td>
                            <td><?php echo esc_html($patient->address); ?></td>
                            <td><?php echo esc_html($patient->marital_status); ?></td>
                            <td><?php echo esc_html($patient->weight); ?></td>
                            <td><?php echo esc_html($patient->height); ?></td>
                            <td><?php echo esc_html($patient->insurance); ?></td>
                            <td>
                                <?php 
                                if ($patient->profile_image) {
                                    $profile_image_url = esc_url($patient->profile_image);
                                    echo '<img src="' . $profile_image_url . '" alt="Profile Image" style="max-width: 100px;">';
                                } else {
                                    echo '<img src="' . plugin_dir_url(__FILE__) . 'images/placeholder.png' . '" alt="Profile Image" style="max-width: 100px;">';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $attachments = maybe_unserialize($patient->attachments);
                                if (!empty($attachments)) {
                                    foreach ($attachments as $attachment) {
                                        echo '<a href="' . esc_url($attachment) . '" target="_blank">' . basename($attachment) . '</a><br>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="15"><?php _e('No patients found.', 'patient-card-plugin'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
