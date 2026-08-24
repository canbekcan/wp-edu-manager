<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$table_students  = $wpdb->prefix . 'lms_students';
$table_semesters = $wpdb->prefix . 'lms_semesters';
$table_posts     = $wpdb->prefix . 'lms_posts';
$table_revisions = $wpdb->prefix . 'lms_post_revisions';

// --- Mesaj Gönderme İşlemi (POST Yakalama) ---
$notice_status = '';
if ( isset( $_POST['send_lms_notice'] ) && check_admin_referer( 'send_lms_notice_nonce' ) ) {
    $student_id  = intval( $_POST['target_student_id'] );
    $notice_msg  = sanitize_textarea_field( $_POST['notice_message'] );
    $notice_type = sanitize_text_field( $_POST['notice_type'] );

    $student = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_students WHERE id = %d", $student_id ) );
    
    if ( $student ) {
        $endpoint = rtrim( $student->site_url, '/' ) . '/wp-json/lms/v1/notice';
        
        $response = wp_remote_post( $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $student->api_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode([
                'message' => $notice_msg,
                'type'    => $notice_type,
                'id'      => time()
            ]),
            'timeout' => 15
        ]);

        if ( is_wp_error( $response ) ) {
            $notice_status = '<div class="error"><p>Failed to reach student site: ' . esc_html( $response->get_error_message() ) . '</p></div>';
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code === 200 ) {
                $notice_status = '<div class="updated"><p>Notice successfully pushed to ' . esc_html( $student->student_email ) . '!</p></div>';
            } else {
                $notice_status = '<div class="error"><p>Failed to set notice. HTTP Code: ' . esc_html( $code ) . '</p></div>';
            }
        }
    }
}

// 1. Öğrencileri ve ait oldukları dönemin notlandırma ağırlıklarını çek
$query = "
    SELECT st.*, sem.semester_name, 
           sem.weight_word_count, sem.weight_link, sem.weight_image, sem.penalty_alt, sem.penalty_modified
    FROM $table_students st
    LEFT JOIN $table_semesters sem ON st.semester_id = sem.id
    ORDER BY st.connected_at DESC
";
$students = $wpdb->get_results( $query );

// 2. Performans için tüm yazıları (posts) tek seferde çekip öğrencilere göre grupla
$posts_query = "
    SELECT p.*, 
    (SELECT modification_flag FROM $table_revisions r WHERE r.lms_post_id = p.id ORDER BY r.scanned_at DESC LIMIT 1) as is_modified
    FROM $table_posts p
";
$all_posts = $wpdb->get_results( $posts_query );

$student_posts = [];
if ( $all_posts ) {
    foreach ( $all_posts as $p ) {
        $student_posts[$p->student_id][] = $p;
    }
}
?>

<div class="wrap">
    <h1>Student Roster & Communication</h1>
    <p>List of all connected students, their overall academic performance, and direct admin notification panel.</p>

    <?php echo $notice_status; ?>

    <!-- Yönetici Mesaj Gönderim Paneli -->
    <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-bottom:20px; max-width: 600px;">
        <h3 style="margin-top:0;">Push Admin Notice to Student</h3>
        <form method="POST" action="">
            <?php wp_nonce_field( 'send_lms_notice_nonce' ); ?>
            
            <p>
                <label style="font-weight:bold;">Select Student:</label><br/>
                <select name="target_student_id" required style="width:100%; max-width: 400px;">
                    <option value="">-- Choose a student --</option>
                    <?php if ( $students ) : foreach ( $students as $s ) : ?>
                        <option value="<?php echo intval( $s->id ); ?>"><?php echo esc_html( $s->student_email . ' (' . $s->site_url . ')' ); ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </p>

            <p>
                <label style="font-weight:bold;">Notice Type:</label><br/>
                <select name="notice_type">
                    <option value="info">Info (Blue)</option>
                    <option value="success">Success (Green)</option>
                    <option value="warning">Warning (Orange)</option>
                    <option value="error">Error (Red)</option>
                </select>
            </p>

            <p>
                <label style="font-weight:bold;">Message (Leave empty to clear current notice):</label><br/>
                <textarea name="notice_message" rows="3" style="width:100%; max-width: 400px;"></textarea>
            </p>

            <button type="submit" name="send_lms_notice" class="button button-primary">Send Notice</button>
        </form>
    </div>

    <!-- Öğrenci Tablosu -->
    <div style="background:#fff; padding:0; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 20%;">Student Email</th>
                    <th style="width: 20%;">Site URL</th>
                    <th style="width: 15%;">Semester</th>
                    <th style="width: 12%;"><center><span title="Total Content Produced"><span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> Contents</span></center></th>
                    <th style="width: 12%;"><center><span title="Cumulative Average Grade"><span class="dashicons dashicons-awards" style="vertical-align: middle;"></span> Avg Grade</span></center></th>
                    <th style="width: 16%;">Connected At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $students ) : foreach ( $students as $student ) : 
                    
                    // --- 3. DİNAMİK ÖĞRENCİ NOT ORTALAMASI HESAPLAMA ---
                    $total_grade = 0;
                    $post_count  = 0;
                    $avg_grade   = 0;

                    if ( isset( $student_posts[$student->id] ) ) {
                        $s_posts = $student_posts[$student->id];
                        $post_count = count( $s_posts );

                        $w_word  = floatval( $student->weight_word_count ?? 0.1 );
                        $w_link  = floatval( $student->weight_link ?? 2.0 );
                        $w_image = floatval( $student->weight_image ?? 3.0 );
                        $p_alt   = floatval( $student->penalty_alt ?? 5.0 );
                        $p_mod   = floatval( $student->penalty_modified ?? 10.0 );

                        foreach ( $s_posts as $post ) {
                            $raw_grade = ($post->word_count * $w_word) 
                                       + (($post->internal_links + $post->external_links) * $w_link) 
                                       + ($post->total_images * $w_image) 
                                       - ($post->missing_alt_images * $p_alt);
                            
                            if ( $post->is_modified == 1 ) {
                                $raw_grade -= $p_mod;
                            }

                            // İçerik notunu 0-100 arasında sınırla ve toplama ekle
                            $total_grade += max( 0, min( 100, round( $raw_grade ) ) );
                        }
                    }

                    // Sıfıra bölünme hatasını engelle (Fallback)
                    if ( $post_count > 0 ) {
                        $avg_grade = round( $total_grade / $post_count );
                    }

                    // Renk Kodu Standardı (0-49: Kırmızı, 50-74: Turuncu, 75-100: Yeşil)
                    $grade_color = '#00a32a';
                    if ( $avg_grade < 50 ) $grade_color = '#d63638';
                    elseif ( $avg_grade < 75 ) $grade_color = '#dba617';
                    // ----------------------------------------------------
                ?>
                    <tr>
                        <td><?php echo esc_html( $student->id ); ?></td>
                        <td><strong><?php echo esc_html( $student->student_email ); ?></strong></td>
                        <td><a href="<?php echo esc_url( $student->site_url ); ?>" target="_blank"><?php echo esc_html( $student->site_url ); ?></a></td>
                        <td>
                            <span style="display:inline-block; padding:3px 6px; background:#f0f0f1; border:1px solid #c3c4c7; border-radius:3px; font-size:11px; font-weight:600;">
                                <?php echo esc_html( $student->semester_name ? $student->semester_name : 'Unknown' ); ?>
                            </span>
                        </td>
                        <td>
                            <center>
                                <span style="font-size:14px; font-weight:bold; color:#2271b1;">
                                    <?php echo intval( $post_count ); ?>
                                </span>
                            </center>
                        </td>
                        <td>
                            <center>
                                <?php if ( $post_count > 0 ) : ?>
                                    <span style="font-size:16px; font-weight:bold; color:<?php echo $grade_color; ?>;">
                                        <?php echo intval( $avg_grade ); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="font-size:12px; color:#666;">No Data</span>
                                <?php endif; ?>
                            </center>
                        </td>
                        <td><?php echo esc_html( date( 'Y-m-d H:i', strtotime( $student->connected_at ) ) ); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7">No students found. Awaiting connections.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>