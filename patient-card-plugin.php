<?php
/*
 * Plugin Name:       Patient card - Patient portal
 * Description:       A custom plugin made for allowing patients to create their cards
 * Version:           1.0.4
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kazmi Webwhiz
 * Author URI:        https://kazmiwebwhiz.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PATIENT_CARD_PLUGIN_VERSION', '1.0');
define('PATIENT_CARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PATIENT_CARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the activation/deactivation/upgrader classes
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/class-patient-card-activator.php';
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/class-patient-card-deactivator.php';
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/class-patient-card-upgrader.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Patient_Card_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Patient_Card_Deactivator', 'deactivate'));

// Run the upgrader
add_action('plugins_loaded', array('Patient_Card_Upgrader', 'upgrade'));

// Include the main plugin class
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/class-patient-card.php';

// Include admin pages
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/admin-pages.php';

// Include the patients attachments handling file
require_once PATIENT_CARD_PLUGIN_DIR . 'includes/patients-attachments.php';

// Initialize the plugin
Patient_Card::init();

// Enqueue styles
function patient_card_enqueue_styles() {
    wp_enqueue_style('patient-card-style', PATIENT_CARD_PLUGIN_URL . 'css/style.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'patient_card_enqueue_styles');

// Add the menu item
function patient_card_admin_menu() {
    add_menu_page(
        __('All Patients', 'patient-card-plugin'),
        __('All Patients', 'patient-card-plugin'),
        'manage_options',
        'patient-card-admin',
        'display_patient_card_admin_page',
        'dashicons-id-alt',
        20
    );
}
add_action('admin_menu', 'patient_card_admin_menu');
