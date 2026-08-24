<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$table_posts    = $wpdb->prefix . 'lms_posts';
$table_students = $wpdb->prefix . 'lms_students';
$table_semesters= $wpdb->prefix . 'lms_semesters';
$table_revisions= $wpdb->prefix . 'lms_post_revisions';

$selected_author   = sanitize_text_field( $_GET['filter_author'] ?? '' );
$selected_semester = intval( $_GET['filter_semester'] ?? 0 ); 
$orderby           = sanitize_text_field( $_GET['orderby'] ?? 'id' );
$order             = sanitize_text_field( $_GET['order'] ?? 'DESC' );

$allowed_orderby = [ 'post_title', 'word_count', 'post_date', 'post_modified' ];
if ( ! in_array( $orderby, $allowed_orderby ) ) {
    $orderby = 'id';
}
$order = ( strtoupper( $order ) === 'ASC' ) ? 'ASC' : 'DESC';

$where_clauses = [];
$query_args    = [];

if ( ! empty( $selected_author ) ) {
    $where_clauses[] = "s.student_email = %s";
    $query_args[]    = $selected_author;
}

if ( $selected_semester > 0 ) {
    $where_clauses[] = "sem.id = %d";
    $query_args[]    = $selected_semester;
}

$where_sql = '';
if ( ! empty( $where_clauses ) ) {
    $where_sql = "WHERE " . implode( ' AND ', $where_clauses );
}

$query = "
    SELECT p.*, s.student_email, s.site_url, 
    sem.semester_name, sem.weight_word_count, sem.weight_link, sem.weight_image, sem.penalty_alt, sem.penalty_modified,
    (SELECT modification_flag FROM $table_revisions r WHERE r.lms_post_id = p.id ORDER BY r.scanned_at DESC LIMIT 1) as is_modified
    FROM $table_posts p
    LEFT JOIN $table_students s ON p.student_id = s.id
    LEFT JOIN $table_semesters sem ON s.semester_id = sem.id
    $where_sql
    ORDER BY p.$orderby $order
";

if ( ! empty( $query_args ) ) {
    $posts = $wpdb->get_results( $wpdb->prepare( $query, $query_args ) );
} else {
    $posts = $wpdb->get_results( $query );
}

$authors   = $wpdb->get_results( "SELECT DISTINCT student_email FROM $table_students ORDER BY student_email ASC" );
$semesters = $wpdb->get_results( "SELECT id, semester_name FROM $table_semesters ORDER BY id DESC" );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Content Audit & Revision Tracking', 'wp-edu-manager' ); ?></h1>
    <p><?php esc_html_e( 'Detailed SEO metrics, publishing dates, integrity flags, and dynamic grading for all student submissions.', 'wp-edu-manager' ); ?></p>

    <form method="GET" action="" style="margin: 15px 0; background: #fff; padding: 15px; border: 1px solid #ccd0d4; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="page" value="lms-audit">
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <label><strong><?php esc_html_e( 'Filter by Semester:', 'wp-edu-manager' ); ?></strong></label>
            <select name="filter_semester" style="min-width: 180px;">
                <option value=""><?php esc_html_e( 'All Semesters', 'wp-edu-manager' ); ?></option>
                <?php foreach ( $semesters as $sem ) : ?>
                    <option value="<?php echo esc_attr( $sem->id ); ?>" <?php selected( $selected_semester, $sem->id ); ?>>
                        <?php echo esc_html( $sem->semester_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <label><strong><?php esc_html_e( 'Filter by Author:', 'wp-edu-manager' ); ?></strong></label>
            <select name="filter_author" style="min-width: 200px;">
                <option value=""><?php esc_html_e( 'All Authors', 'wp-edu-manager' ); ?></option>
                <?php foreach ( $authors as $author ) : ?>
                    <option value="<?php echo esc_attr( $author->student_email ); ?>" <?php selected( $selected_author, $author->student_email ); ?>>
                        <?php echo esc_html( $author->student_email ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Filters', 'wp-edu-manager' ); ?></button>
        <?php if ( ! empty( $selected_author ) || $selected_semester > 0 ) : ?>
            <a href="admin.php?page=lms-audit" class="button"><?php esc_html_e( 'Reset', 'wp-edu-manager' ); ?></a>
        <?php endif; ?>
    </form>

    <div style="background:#fff; padding:0; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;"><span title="<?php esc_attr_e( 'Post Title, URL and Assigned Tags', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-admin-page" style="vertical-align: middle;"></span></span></th>
                    <th style="width: 15%;"><center><span title="<?php esc_attr_e( 'Student Author Email and Semester', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span></span></center></th>
                    <th style="width: 11%;"><span title="<?php esc_attr_e( 'Original Publication Date and Last Modified Date', 'wp-edu-manager' ); ?>"><?php esc_html_e( 'Publish / Modified', 'wp-edu-manager' ); ?></span></th>
                    <th style="width: 6%;">
                        <a href="?page=lms-audit&orderby=word_count&order=<?php echo ($order === 'ASC') ? 'DESC' : 'ASC'; ?>">
                            <span title="<?php esc_attr_e( 'Total Word Count of Content', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-text-page" style="vertical-align: middle;"></span></span>
                        </a>
                    </th>
                    <th style="width: 4%;"><span title="<?php esc_attr_e( 'Internal Links Count', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-fullscreen-exit-alt" style="vertical-align: middle;"></span></span></th>
                    <th style="width: 4%;"><span title="<?php esc_attr_e( 'External Links Count', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-fullscreen-alt" style="vertical-align: middle;"></span></span></th>
                    <th style="width: 4%;"><span title="<?php esc_attr_e( 'Total Images Count', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-format-image" style="vertical-align: middle;"></span></span></th>
                    <th style="width: 6%;"><span title="<?php esc_attr_e( 'Images Missing ALT Attributes', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-images-alt" style="vertical-align: middle;"></span></span></th>
                    <th style="width: 10%;"><span title="<?php esc_attr_e( 'Production Time & Speed (Words per Minute)', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-clock" style="vertical-align: middle;"></span> <?php esc_html_e( 'Speed', 'wp-edu-manager' ); ?></span></th>
                    <th style="width: 10%;"><center><span title="<?php esc_attr_e( 'Algorithmic Suggested Grade (0-100)', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-awards" style="vertical-align: middle;"></span> <?php esc_html_e( 'Grade', 'wp-edu-manager' ); ?></span></center></th>
                    <th style="width: 10%;"><center><span title="<?php esc_attr_e( 'Modification Integrity Status', 'wp-edu-manager' ); ?>"><span class="dashicons dashicons-format-status" style="vertical-align: middle;"></span></span></center></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $posts ) : foreach ( $posts as $post ) : 
                    
                    // -- ÜRETİM HIZI VE SÜRESİ HESAPLAMASI --
                    $start_timestamp = strtotime( $post->post_start_time );
                    $end_timestamp   = strtotime( $post->post_end_time );
                    $duration_mins   = max( 1, round( ($end_timestamp - $start_timestamp) / 60 ) );
                    
                    // YENİ: Okunabilir Süre Formatı
                    $d_days  = floor( $duration_mins / 1440 );
                    $d_hours = floor( ($duration_mins % 1440) / 60 );
                    $d_mins  = $duration_mins % 60;
                    
                    $duration_text = '';
                    if ( $d_days > 0 )  $duration_text .= $d_days . __( 'd', 'wp-edu-manager' ) . ' ';
                    if ( $d_hours > 0 ) $duration_text .= $d_hours . __( 'h', 'wp-edu-manager' ) . ' ';
                    if ( $d_mins > 0 || $duration_text === '' ) $duration_text .= $d_mins . __( 'm', 'wp-edu-manager' );
                    $duration_text = trim( $duration_text );

                    $raw_wpm = $post->word_count / $duration_mins;
                    $wpm     = ( $raw_wpm > 0 && $raw_wpm < 1 ) ? round( $raw_wpm, 2 ) : round( $raw_wpm );

                    $w_word  = isset( $post->weight_word_count ) ? floatval( $post->weight_word_count ) : 0.1;
                    $w_link  = isset( $post->weight_link ) ? floatval( $post->weight_link ) : 2.0;
                    $w_image = isset( $post->weight_image ) ? floatval( $post->weight_image ) : 3.0;
                    $p_alt   = isset( $post->penalty_alt ) ? floatval( $post->penalty_alt ) : 5.0;
                    $p_mod   = isset( $post->penalty_modified ) ? floatval( $post->penalty_modified ) : 10.0;

                    $raw_grade = ($post->word_count * $w_word) 
                               + (($post->internal_links + $post->external_links) * $w_link) 
                               + ($post->total_images * $w_image) 
                               - ($post->missing_alt_images * $p_alt);
                    
                    if ( $post->is_modified == 1 ) {
                        $raw_grade -= $p_mod;
                    }

                    $final_grade = max( 0, min( 100, round( $raw_grade ) ) );
                    
                    $grade_color = '#00a32a';
                    if ( $final_grade < 50 ) $grade_color = '#d63638';
                    elseif ( $final_grade < 75 ) $grade_color = '#dba617';
                ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url( $post->post_url ); ?>" target="_blank"><?php echo esc_html( $post->post_title ); ?></a></strong>
                            <?php if ( ! empty( $post->post_tags ) ) : ?>
                                <br/><small style="color: #666;"><strong><?php esc_html_e( 'Tags:', 'wp-edu-manager' ); ?></strong> <?php echo esc_html( $post->post_tags ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <center>
                                <?php echo esc_html( $post->student_email ); ?>
                                <br/>
                                <span style="display:inline-block; margin-top:5px; padding:2px 6px; background:#f0f0f1; border:1px solid #c3c4c7; border-radius:3px; font-size:10px; color:#50575e; font-weight:600;">
                                    <?php echo esc_html( $post->semester_name ?? __( 'Unknown Semester', 'wp-edu-manager' ) ); ?>
                                </span>
                            </center>
                        </td>
                        <td>
                            <small><strong><?php esc_html_e( 'Pub:', 'wp-edu-manager' ); ?></strong> <?php echo esc_html( $post->post_date ); ?></small><br/>
                            <small><strong><?php esc_html_e( 'Mod:', 'wp-edu-manager' ); ?></strong> <?php echo esc_html( $post->post_modified ); ?></small>
                        </td>
                        <td>
                            <span style="font-weight:bold; color: <?php echo ( $post->word_count < 300 ) ? '#d63638' : '#00a32a'; ?>">
                                <?php echo intval( $post->word_count ); ?>
                            </span>
                        </td>
                        <td><?php echo intval( $post->internal_links ); ?></td>
                        <td><?php echo intval( $post->external_links ); ?></td>
                        <td><?php echo intval( $post->total_images ); ?></td>
                        <td>
                            <span style="color: <?php echo ( $post->missing_alt_images > 0 ) ? '#d63638' : 'inherit'; ?>; font-weight: bold;">
                                <?php echo intval( $post->missing_alt_images ); ?>
                            </span>
                        </td>
                        <td>
                            <center>
                                <span style="display:block; font-size:13px; font-weight:bold; color:#2271b1;">
                                    <?php echo esc_html( $duration_text ); ?>
                                </span>
                                <small style="color: #666;"><?php echo $wpm; ?> <?php esc_html_e( 'WPM', 'wp-edu-manager' ); ?></small>
                            </center>
                        </td>
                        <td>
                            <center>
                                <span style="display:inline-block; font-size:16px; font-weight:bold; color:<?php echo $grade_color; ?>;">
                                    <?php echo $final_grade; ?>
                                </span>
                            </center>
                        </td>
                        <td><center>
                            <?php if ( $post->is_modified == 1 ) : ?>
                                <span style="display:inline-block; padding:3px 8px; background:#f8d7da; color:#721c24; border-radius:3px; font-size:11px; font-weight:bold;"><?php esc_html_e( 'MODIFIED', 'wp-edu-manager' ); ?></span>
                            <?php else : ?>
                                <span style="display:inline-block; padding:3px 8px; background:#d4edda; color:#155724; border-radius:3px; font-size:11px; font-weight:bold;"><?php esc_html_e( 'ORIGINAL', 'wp-edu-manager' ); ?></span>
                            <?php endif; ?>
                            </center>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="11"><?php esc_html_e( 'No post data collected yet.', 'wp-edu-manager' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>