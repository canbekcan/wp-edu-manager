<?php
/**
 * Plugin Name: WP EDU Manager (Host)
 * Description: Advanced LMS center for monitoring student sites, tracking revisions, and evaluating content.
 * Version: 0.0.1
 * Author: Can Bekcan
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_EDU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

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
