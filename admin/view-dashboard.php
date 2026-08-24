<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Manuel tetikleme yapıldıysa Cron görevini asenkron olarak kuyruğa ekle
if ( isset( $_POST['run_manual_sync'] ) && check_admin_referer( 'edu_run_sync_nonce' ) ) {
    WP_EDU_Cron::queue_sync_tasks();
    echo '<div class="updated notice is-dismissible"><p>Senkronizasyon görevleri başarıyla arka plana alındı. Siteler sırayla taranacaktır.</p></div>';
}

global $wpdb;
$total_students = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}lms_students" );
$total_posts    = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}lms_posts" );
?>

<div class="wrap">
    <h1>LMS Dashboard & Real-Time Sync</h1>
    
    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <!-- İstatistik Kartı -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3>System Overview</h3>
            <p><strong>Connected Students:</strong> <?php echo intval( $total_students ); ?></p>
            <p><strong>Tracked Posts:</strong> <?php echo intval( $total_posts ); ?></p>
        </div>

        <!-- Manuel Tarama Kartı -->
        <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3>Run Real-Time Scan</h3>
            <p>Fetch latest posts, word counts, and SEO metrics from all connected student sites immediately.</p>
            <form method="POST" action="">
                <?php wp_nonce_field( 'edu_run_sync_nonce' ); ?>
                <button type="submit" name="run_manual_sync" class="button button-primary button-hero">Fetch Data Now</button>
            </form>
            <p class="description">Note: Automated daily scan runs at 23:50.</p>
        </div>
    </div>
</div>