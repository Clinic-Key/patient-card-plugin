<?php
if (!defined('ABSPATH')) {
    exit;
}

class Patient_Card_Upgrader {
    public static function upgrade() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'patient_card';

        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'profile_image'");

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD profile_image varchar(255)");
        }
    }
}
