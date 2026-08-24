<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Admin_Menu {
    
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
    }

    public function register_admin_menus() {
        // Main Menu
        add_menu_page( 
            'LMS Dashboard', 
            'LMS Manager', 
            'manage_options', 
            'lms-manager', 
            [ $this, 'render_dashboard' ], 
            'dashicons-welcome-learn-more', 
            3 
        );
        
        // Sub Menus
        add_submenu_page( 'lms-manager', 'Semesters', 'Semesters', 'manage_options', 'lms-semesters', [ $this, 'render_semesters' ] );
        add_submenu_page( 'lms-manager', 'Students', 'Students', 'manage_options', 'lms-students', [ $this, 'render_students' ] );
        add_submenu_page( 'lms-manager', 'Content Audit', 'Content Audit', 'manage_options', 'lms-audit', [ $this, 'render_audit' ] );
        add_submenu_page( 'lms-manager', 'Site Updates', 'Site Updates', 'manage_options', 'lms-updates', [ $this, 'render_updates_page' ] );
    }

    public function render_dashboard() { 
        $file_path = WP_EDU_PLUGIN_DIR . 'admin/view-dashboard.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>Error: view-dashboard.php file is missing.</p></div>';
        }
    }

    public function render_semesters() { 
        // Bir önceki mesajda verilen form sayfasını buraya yüklüyoruz
        $file_path = WP_EDU_PLUGIN_DIR . 'admin/view-semesters.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>Error: view-semesters.php file is missing in the admin folder.</p></div>';
        }
    }

    public function render_students() { 
        $file_path = WP_EDU_PLUGIN_DIR . 'admin/view-students.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>Error: view-students.php file is missing.</p></div>';
        }
    }

    public function render_audit() { 
        $file_path = WP_EDU_PLUGIN_DIR . 'admin/view-audit.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>Error: view-audit.php file is missing.</p></div>';
        }
    }

    public function render_updates_page() {
        $file_path = WP_EDU_PLUGIN_DIR . 'admin/view-updates.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="error"><p>Error: view-updates.php file is missing.</p></div>';
        }
    }
}