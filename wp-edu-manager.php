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
require_once WP_EDU_PLUGIN_DIR . 'includes/class-github-updater.php';

// Sınıfları Başlat
new WP_EDU_Database();
new WP_EDU_API_Host();
new WP_EDU_Cron(); 
new WP_EDU_Admin_Menu();

// --- GÜNCELLEME VE ÖNBELLEK TEMİZLEME MEKANİZMASI ---
if ( is_admin() ) {
    
    // Updater Sınıfını Başlat
    new WP_EDU_Manager_Github_Updater( 'canbekcan', 'wp-edu-manager', __FILE__ );
    
    // Önbellek Temizleyici (admin_init kancası)
    add_action( 'admin_init', function() {
        if ( isset( $_GET['force_gh_manager_check'] ) && $_GET['force_gh_manager_check'] == '1' ) {
            
            // WordPress genel önbelleği ve eklentiye özel önbellekleri sil
            delete_site_transient( 'update_plugins' );
            delete_transient( 'wp_edu_updater_wp-edu-manager' );
            delete_transient( 'wp_edu_readme_wp-edu-manager' ); // YENİ: Readme önbelleğini de temizler
            
            // Parametreyi temizleyip sayfayı yenile
            wp_safe_redirect( remove_query_arg( 'force_gh_manager_check' ) );
            exit;
        }
    });

    // Test ve Sıfırlama Butonu (Yönetici Bildirimi)
    add_action( 'admin_notices', function() {
        $url = "https://api.github.com/repos/canbekcan/wp-edu-manager/releases/latest";
        $response = wp_remote_get( $url, ['headers' => ['User-Agent' => 'WordPress-Debug']] );
        
        if ( is_wp_error( $response ) ) return;
        
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ) );
            $tag  = isset( $body->tag_name ) ? $body->tag_name : 'Bilinmiyor';
            
            $reset_url = add_query_arg( 'force_gh_manager_check', '1' );

            echo "<div class='notice notice-info is-dismissible' style='display:flex; justify-content:space-between; align-items:center; padding:10px;'>
                    <p style='margin:0;'><strong>" . esc_html__( 'WP EDU Manager (Host) GitHub Status:', 'wp-edu-manager' ) . "</strong> " . esc_html__( 'Latest Release:', 'wp-edu-manager' ) . " <strong>{$tag}</strong></p>
                    <a href='" . esc_url( $reset_url ) . "' class='button button-primary'>" . esc_html__( 'Check for Updates', 'wp-edu-manager' ) . "</a>
                </div>";
        }
    });
}