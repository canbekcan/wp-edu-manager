<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_semesters = $wpdb->prefix . 'lms_semesters';

// Form gönderildiyse veritabanına kaydet
if ( isset( $_POST['add_semester'] ) && check_admin_referer( 'edu_add_semester_nonce' ) ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Bu işlemi gerçekleştirmek için yeterli yetkiniz yok.' );
    }
    $semester_name = sanitize_text_field( $_POST['semester_name'] );
    $reg_code      = sanitize_text_field( $_POST['registration_code'] );
    $expires_at    = sanitize_text_field( $_POST['expires_at'] );

    // Yeni eklenen notlandırma ağırlıklarını float (ondalık sayı) olarak alıyoruz
    $w_word  = floatval( $_POST['weight_word_count'] ?? 0.1 );
    $w_link  = floatval( $_POST['weight_link'] ?? 2.0 );
    $w_image = floatval( $_POST['weight_image'] ?? 3.0 );
    $p_alt   = floatval( $_POST['penalty_alt'] ?? 5.0 );
    $p_mod   = floatval( $_POST['penalty_modified'] ?? 10.0 );

    $wpdb->insert(
        $table_semesters,
        [
            'semester_name'     => $semester_name,
            'registration_code' => strtoupper($reg_code),
            'expires_at'        => date('Y-m-d H:i:s', strtotime($expires_at)),
            'is_active'         => 1,
            'weight_word_count' => $w_word,
            'weight_link'       => $w_link,
            'weight_image'      => $w_image,
            'penalty_alt'       => $p_alt,
            'penalty_modified'  => $p_mod
        ],
        // Veri tipleri: string, string, string, int, float, float, float, float, float
        [ '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%f' ]
    );
    echo '<div class="updated notice is-dismissible"><p>New semester, registration code, and grading weights created successfully.</p></div>';
}

// Mevcut dönemleri çek
$semesters = $wpdb->get_results( "SELECT * FROM $table_semesters ORDER BY id DESC" );
?>

<div class="wrap">
    <h1>Semester & Grading Management</h1>
    
    <div style="display:flex; gap: 20px; margin-top:20px;">
        
        <!-- FORM KISMI -->
        <div style="flex: 1; background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <h3>Create New Semester</h3>
            <form method="POST" action="">
                <?php wp_nonce_field( 'edu_add_semester_nonce' ); ?>
                
                <p>
                    <label>Semester Name (e.g., Fall 2026)</label><br/>
                    <input type="text" name="semester_name" required class="regular-text">
                </p>
                <p>
                    <label>Registration Code (e.g., NEWS-F26)</label><br/>
                    <input type="text" name="registration_code" required class="regular-text" style="text-transform: uppercase;">
                    <br/><small>Students will use this code to connect their sites.</small>
                </p>
                <p>
                    <label>Expiration Date</label><br/>
                    <input type="datetime-local" name="expires_at" required class="regular-text">
                </p>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                <h4>Algorithmic Grading Weights</h4>
                <p style="font-size: 12px; color: #666;">Set the scoring criteria for this specific semester.</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Word Count (Per Word)</label><br/>
                        <input type="number" step="0.01" name="weight_word_count" value="0.1" required style="width:100%;">
                    </div>
                    <div>
                        <label>Links (Per Link)</label><br/>
                        <input type="number" step="0.1" name="weight_link" value="2.0" required style="width:100%;">
                    </div>
                    <div>
                        <label>Images (Per Image)</label><br/>
                        <input type="number" step="0.1" name="weight_image" value="3.0" required style="width:100%;">
                    </div>
                    <div>
                        <label>Missing ALT (Penalty)</label><br/>
                        <input type="number" step="0.1" name="penalty_alt" value="5.0" required style="width:100%;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Modified Revision (Penalty)</label><br/>
                        <input type="number" step="0.1" name="penalty_modified" value="10.0" required style="width:100%;">
                    </div>
                </div>

                <p style="margin-top: 20px;">
                    <input type="submit" name="add_semester" class="button button-primary" value="Create Semester">
                </p>
            </form>
        </div>

        <!-- TABLO KISMI -->
        <div style="flex: 2;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 20%;">Semester Name</th>
                        <th style="width: 15%;">Code</th>
                        <th style="width: 40%;">Grading Weights (Word / Link / Img / ALT / Mod)</th>
                        <th style="width: 10%;">Expires At</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $semesters ) : foreach ( $semesters as $sem ) : ?>
                        <tr>
                            <td><?php echo esc_html( $sem->id ); ?></td>
                            <td><strong><?php echo esc_html( $sem->semester_name ); ?></strong></td>
                            <td><code><?php echo esc_html( $sem->registration_code ); ?></code></td>
                            <td>
                                <small style="color:#555;">
                                    W: <strong><?php echo esc_html( $sem->weight_word_count ); ?></strong> | 
                                    L: <strong><?php echo esc_html( $sem->weight_link ); ?></strong> | 
                                    I: <strong><?php echo esc_html( $sem->weight_image ); ?></strong> | 
                                    A: <span style="color:#d63638;">-<?php echo esc_html( $sem->penalty_alt ); ?></span> | 
                                    M: <span style="color:#d63638;">-<?php echo esc_html( $sem->penalty_modified ); ?></span>
                                </small>
                            </td>
                            <td><?php echo esc_html( date('Y-m-d', strtotime($sem->expires_at)) ); ?></td>
                            <td>
                                <?php if ( $sem->is_active ) : ?>
                                    <span style="color:green; font-weight:bold;">Active</span>
                                <?php else : ?>
                                    <span style="color:red; font-weight:bold;">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="6">No semesters found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>