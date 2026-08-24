<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_students = $wpdb->prefix . 'lms_students';
$students = $wpdb->get_results( "SELECT * FROM $table_students ORDER BY connected_at DESC" );
?>

<div class="wrap">
    <h1>Student Site Updates Monitor</h1>
    <p>Check core, plugin, and theme update requirements across all connected student websites.</p>

    <div style="background:#fff; padding:0; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04); margin-top:20px;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Student Email</th>
                    <th style="width: 30%;">Site URL</th>
                    <th style="width: 15%;">Core Update</th>
                    <th style="width: 15%;">Plugins Update</th>
                    <th style="width: 15%;">Themes Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $students ) : foreach ( $students as $student ) : 
                    // Her öğrenci sitesine anlık REST API isteği atıyoruz
                    $endpoint = rtrim( $student->site_url, '/' ) . '/wp-json/lms/v1/updates';
                    $response = wp_remote_get( $endpoint, [
                        'headers' => [ 'Authorization' => 'Bearer ' . $student->api_token ],
                        'timeout' => 10
                    ]);

                    $has_core_update = false;
                    $plugin_count = 0;
                    $theme_count = 0;

                    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                        $body = json_decode( wp_remote_retrieve_body( $response ), true );
                        if ( isset( $body['updates'] ) ) {
                            $has_core_update = ! empty( $body['updates']['core'] );
                            $plugin_count    = intval( $body['updates']['plugins'] );
                            $theme_count     = intval( $body['updates']['themes'] );
                        }
                    }
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $student->student_email ); ?></strong></td>
                        <td><a href="<?php echo esc_url( $student->site_url ); ?>" target="_blank"><?php echo esc_html( $student->site_url ); ?></a></td>
                        <td>
                            <?php if ( $has_core_update ) : ?>
                                <span style="color: #d63638; font-weight: bold;">Update Available</span>
                            <?php else : ?>
                                <span style="color: #00a32a;">Up to date</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $plugin_count > 0 ) : ?>
                                <span style="color: #d63638; font-weight: bold;"><?php echo $plugin_count; ?> Plugins</span>
                            <?php else : ?>
                                <span style="color: #00a32a;">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $theme_count > 0 ) : ?>
                                <span style="color: #d63638; font-weight: bold;"><?php echo $theme_count; ?> Themes</span>
                            <?php else : ?>
                                <span style="color: #00a32a;">None</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5">No students connected yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>