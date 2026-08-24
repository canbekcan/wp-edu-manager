<?php
/**
 * Plugin Name: BEKCAN Institute (Manager)
 * Description: Advanced LMS center for monitoring student sites, tracking revisions, and evaluating content.
 * Version: 0.0.1
 * Author: BEKCAN Institute
 * Text Domain: wp-edu-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_EDU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Çeviri Altyapısını (i18n) Yükle
add_action( 'plugins_loaded', 'wp_edu_manager_load_textdomain' );
function wp_edu_manager_load_textdomain() {
    load_plugin_textdomain( 'wp-edu-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Alt modülleri dahil et
require_once WP_EDU_PLUGIN_DIR . 'includes/class-database.php';
require_once WP_EDU_PLUGIN_DIR . 'includes/class-user-manager.php';
require_once WP_EDU_PLUGIN_DIR . 'includes/wp-edu-sso.php';
require_once WP_EDU_PLUGIN_DIR . 'includes/class-api-host.php';
require_once WP_EDU_PLUGIN_DIR . 'includes/class-cron.php'; 
require_once WP_EDU_PLUGIN_DIR . 'admin/class-admin-menu.php';

// Sınıfları Başlat
new WP_EDU_Database();
new WP_EDU_API_Host();
new WP_EDU_Cron(); 
new WP_EDU_Admin_Menu();
