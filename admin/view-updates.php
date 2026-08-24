<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_students = $wpdb->prefix . 'lms_students';
$students = $wpdb->get_results( "SELECT * FROM $table_students ORDER BY connected_at DESC" );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Student Site Updates Monitor', 'wp-edu-manager' ); ?></h1>
    <p><?php esc_html_e( 'Check core, plugin, and theme update requirements across all connected student websites.', 'wp-edu-manager' ); ?></p>

    <div style="background:#fff; padding:0; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04); margin-top:20px;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;"><?php esc_html_e( 'Student Email', 'wp-edu-manager' ); ?></th>
                    <th style="width: 30%;"><?php esc_html_e( 'Site URL', 'wp-edu-manager' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Core Update', 'wp-edu-manager' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Plugins Update', 'wp-edu-manager' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Themes Update', 'wp-edu-manager' ); ?></th>
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
                                <span style="color: #d63638; font-weight: bold;"><?php esc_html_e( 'Update Available', 'wp-edu-manager' ); ?></span>
                            <?php else : ?>
                                <span style="color: #00a32a;"><?php esc_html_e( 'Up to date', 'wp-edu-manager' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $plugin_count > 0 ) : ?>
                                <span style="color: #d63638; font-weight: bold;">
                                    <?php echo esc_html( sprintf( __( '%d Plugins', 'wp-edu-manager' ), $plugin_count ) ); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #00a32a;"><?php esc_html_e( 'None', 'wp-edu-manager' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $theme_count > 0 ) : ?>
                                <span style="color: #d63638; font-weight: bold;">
                                    <?php echo esc_html( sprintf( __( '%d Themes', 'wp-edu-manager' ), $theme_count ) ); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #00a32a;"><?php esc_html_e( 'None', 'wp-edu-manager' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No students connected yet.', 'wp-edu-manager' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>