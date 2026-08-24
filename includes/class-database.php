<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Database {
    
    public function __construct() {
        // Eklenti ana dosyasındaki yola referansla aktivasyon kancasını tetikler
        register_activation_hook( WP_EDU_PLUGIN_DIR . 'wp-edu-manager.php', [ $this, 'install_database_tables' ] );
    }

    public function install_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Semesters Table (Algoritmik Notlandırma Ağırlıkları Eklendi)
        $table_semesters = $wpdb->prefix . 'lms_semesters';
        $sql_semesters = "CREATE TABLE $table_semesters (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            semester_name varchar(255) NOT NULL,
            registration_code varchar(50) NOT NULL,
            expires_at datetime NOT NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            weight_word_count float DEFAULT 0.1 NOT NULL,
            weight_link float DEFAULT 2.0 NOT NULL,
            weight_image float DEFAULT 3.0 NOT NULL,
            penalty_alt float DEFAULT 5.0 NOT NULL,
            penalty_modified float DEFAULT 10.0 NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_semesters );

        // 2. Students Table
        $table_students = $wpdb->prefix . 'lms_students';
        $sql_students = "CREATE TABLE $table_students (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            semester_id mediumint(9) NOT NULL,
            site_url varchar(255) NOT NULL,
            student_email varchar(100) NOT NULL,
            api_token varchar(64) NOT NULL,
            connected_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_students );

        // 3. Posts Table (Önceki aşamada eklediğimiz süre sütunları da burada)
        $table_posts = $wpdb->prefix . 'lms_posts';
        $sql_posts = "CREATE TABLE $table_posts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) NOT NULL,
            remote_post_id bigint(20) NOT NULL,
            post_title text NOT NULL,
            post_url varchar(255) NOT NULL,
            full_content longtext NOT NULL,
            post_date datetime NOT NULL,
            post_modified datetime NOT NULL,
            post_start_time datetime DEFAULT NULL,
            post_end_time datetime DEFAULT NULL,
            word_count int(11) DEFAULT 0 NOT NULL,
            internal_links int(11) DEFAULT 0 NOT NULL,
            external_links int(11) DEFAULT 0 NOT NULL,
            total_images int(11) DEFAULT 0 NOT NULL,
            missing_alt_images int(11) DEFAULT 0 NOT NULL,
            post_tags varchar(255) DEFAULT '' NOT NULL,
            current_hash varchar(64) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_posts );

        // 4. Post Revisions Table
        $table_revisions = $wpdb->prefix . 'lms_post_revisions';
        $sql_revisions = "CREATE TABLE $table_revisions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            lms_post_id mediumint(9) NOT NULL,
            scanned_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            content_hash varchar(64) NOT NULL,
            modification_flag tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_revisions );
    }
}