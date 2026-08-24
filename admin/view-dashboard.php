<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Manuel tetikleme yapıldıysa Cron görevini asenkron olarak kuyruğa ekle
if ( isset( $_POST['run_manual_sync'] ) && check_admin_referer( 'edu_run_sync_nonce' ) ) {
    WP_EDU_Cron::queue_sync_tasks();
    echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Synchronization tasks have been successfully queued in the background. Sites will be scanned sequentially.', 'wp-edu-manager' ) . '</p></div>';
}

global $wpdb;
$total_students = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}lms_students" );
$total_posts    = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}lms_posts" );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'LMS Dashboard & Real-Time Sync', 'wp-edu-manager' ); ?></h1>
    
    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <!-- İstatistik Kartı -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3><?php esc_html_e( 'System Overview', 'wp-edu-manager' ); ?></h3>
            <p><strong><?php esc_html_e( 'Connected Students:', 'wp-edu-manager' ); ?></strong> <?php echo intval( $total_students ); ?></p>
            <p><strong><?php esc_html_e( 'Tracked Posts:', 'wp-edu-manager' ); ?></strong> <?php echo intval( $total_posts ); ?></p>
        </div>

        <!-- Manuel Tarama Kartı -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3><?php esc_html_e( 'Run Real-Time Scan', 'wp-edu-manager' ); ?></h3>
            <p><?php esc_html_e( 'Fetch latest posts, word counts, and SEO metrics from all connected student sites immediately.', 'wp-edu-manager' ); ?></p>
            <form method="POST" action="">
                <?php wp_nonce_field( 'edu_run_sync_nonce' ); ?>
                <button type="submit" name="run_manual_sync" class="button button-primary button-hero"><?php esc_html_e( 'Fetch Data Now', 'wp-edu-manager' ); ?></button>
            </form>
            <p class="description"><?php esc_html_e( 'Note: Automated daily scan runs at 23:50.', 'wp-edu-manager' ); ?></p>
        </div>
    </div>
</div>