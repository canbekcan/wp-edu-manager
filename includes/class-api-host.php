<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_API_Host {
    
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_host_rest_routes' ] );
    }

    public function register_host_rest_routes() {
        register_rest_route( 'lms/v1', '/register', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_student_registration' ],
            'permission_callback' => '__return_true'
        ] );

        // METOT GÜNCELLENDİ: Katı okuma protokolü (WP_REST_Server::READABLE)
        register_rest_route( 'lms/v1', '/grades', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_student_grades' ],
            'permission_callback' => '__return_true'
        ] );
    }

    public function get_student_grades( WP_REST_Request $request ) {
        global $wpdb;
        
        $auth_header = $request->get_header( 'authorization' );
        $token = '';
        
        if ( ! empty( $auth_header ) ) {
            $token = str_replace( 'Bearer ', '', $auth_header );
        } elseif ( ! empty( $request->get_param( 'token' ) ) ) {
            $token = sanitize_text_field( $request->get_param( 'token' ) );
        }

        if ( empty( $token ) ) {
            return new WP_Error( 'unauthorized', 'Missing token. Check server headers.', [ 'status' => 401 ] );
        }

        $table_students = $wpdb->prefix . 'lms_students';
        $student = $wpdb->get_row( $wpdb->prepare( 
            "SELECT id, semester_id FROM $table_students WHERE api_token = %s", 
            $token 
        ) );

        if ( ! $student ) {
            return new WP_Error( 'forbidden', 'Invalid or revoked API Token.', [ 'status' => 403 ] );
        }

        $table_posts     = $wpdb->prefix . 'lms_posts';
        $table_semesters = $wpdb->prefix . 'lms_semesters';
        $table_revisions = $wpdb->prefix . 'lms_post_revisions';

        $query = $wpdb->prepare("
            SELECT p.*, 
            sem.weight_word_count, sem.weight_link, sem.weight_image, sem.penalty_alt, sem.penalty_modified,
            (SELECT modification_flag FROM $table_revisions r WHERE r.lms_post_id = p.id ORDER BY r.scanned_at DESC LIMIT 1) as is_modified
            FROM $table_posts p
            LEFT JOIN $table_semesters sem ON sem.id = %d
            WHERE p.student_id = %d
            ORDER BY p.post_date DESC
        ", $student->semester_id, $student->id);

        $posts = $wpdb->get_results( $query );
        $grades_data = [];

        if ( $posts ) {
            foreach ( $posts as $post ) {
                $start_time_raw = !empty( $post->post_start_time ) ? $post->post_start_time : $post->post_date;
                $end_time_raw   = !empty( $post->post_end_time ) ? $post->post_end_time : $post->post_modified;
                
                $start = strtotime( $start_time_raw ) ?: time();
                $end   = strtotime( $end_time_raw ) ?: time();
                
                $dur     = max( 1, round( ($end - $start) / 60 ) );
                $raw_wpm = $post->word_count / $dur;
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

                $grades_data[] = [
                    'title'      => $post->post_title,
                    'url'        => $post->post_url,
                    'date'       => date('Y-m-d H:i', strtotime($post->post_date)),
                    'word_count' => $post->word_count,
                    'wpm'        => $wpm,
                    'duration'   => $dur,
                    'grade'      => $final_grade,
                    'is_modified'=> $post->is_modified
                ];
            }
        }

        return rest_ensure_response( [
            'status' => 'success',
            'data'   => $grades_data
        ] );
    }

    public function handle_student_registration( WP_REST_Request $request ) {
        global $wpdb;

        $params = $request->get_json_params();
        if ( empty( $params ) ) { $params = $request->get_body_params(); }

        $reg_code      = sanitize_text_field( $params['registration_code'] ?? '' );
        $site_url      = esc_url_raw( $params['site_url'] ?? '' );
        $student_email = sanitize_email( $params['student_email'] ?? '' );

        if ( empty( $reg_code ) || empty( $site_url ) || empty( $student_email ) ) {
            return new WP_Error( 'missing_data', 'Missing required fields.', [ 'status' => 400 ] );
        }

        $table_semesters = $wpdb->prefix . 'lms_semesters';
        $semester = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, expires_at FROM $table_semesters WHERE registration_code = %s AND is_active = 1",
            $reg_code
        ) );

        if ( ! $semester ) { return new WP_Error( 'invalid_code', 'Invalid or inactive registration code.', [ 'status' => 403 ] ); }

        if ( current_time( 'mysql' ) > $semester->expires_at ) {
            return new WP_Error( 'expired_code', 'Registration period has ended.', [ 'status' => 403 ] );
        }

        $table_students = $wpdb->prefix . 'lms_students';
        $existing_student = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, api_token FROM $table_students WHERE student_email = %s AND semester_id = %d",
            $student_email, $semester->id
        ) );

        if ( $existing_student ) {
            return rest_ensure_response( [ 'status' => 'success', 'message' => 'Already registered.', 'api_token' => $existing_student->api_token ] );
        }

        $api_token = bin2hex( random_bytes( 32 ) );
        $inserted = $wpdb->insert(
            $table_students,
            [
                'semester_id'   => $semester->id,
                'site_url'      => $site_url,
                'student_email' => $student_email,
                'api_token'     => $api_token,
                'connected_at'  => current_time( 'mysql' )
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) { return new WP_Error( 'db_error', 'Database insertion failed.', [ 'status' => 500 ] ); }

        if ( class_exists( 'WP_EDU_User_Manager' ) ) {
            WP_EDU_User_Manager::provision_student_user( $student_email, $site_url );
        }

        return rest_ensure_response( [ 'status' => 'success', 'message' => 'Registration successful.', 'api_token' => $api_token ] );
    }
}