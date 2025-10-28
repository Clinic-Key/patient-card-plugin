<?php
if (!defined('ABSPATH')) {
    exit;
}

class Patient_Card_Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'patient_card';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create or update the table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            dob date NOT NULL,
            blood_group varchar(3) NOT NULL,
            address text NOT NULL,
            profile_image varchar(255),
            attachments text,
            marital_status varchar(20),
            weight float,
            height float,
            insurance enum('yes', 'no') NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
