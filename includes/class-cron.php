<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Cron {
    
    public function __construct() {
        // Ana günlük tetikleyici
        if ( ! wp_next_scheduled( 'lms_daily_sync_event' ) ) {
            $timestamp = strtotime( '23:50:00' );
            if ( $timestamp < time() ) {
                $timestamp += DAY_IN_SECONDS;
            }
            wp_schedule_event( $timestamp, 'daily', 'lms_daily_sync_event' );
        }
        
        // Ana görev artık sadece alt görevleri kuyruğa ekler
        add_action( 'lms_daily_sync_event', [ __CLASS__, 'queue_sync_tasks' ] );
        
        // YENİ: Tekil öğrenci işleme kancası (Asenkron çalışır)
        add_action( 'lms_sync_single_student', [ __CLASS__, 'process_single_student' ], 10, 1 );
    }

    // Taramayı başlatmak yerine kuyruğa görev ekleyen yeni metot
    public static function queue_sync_tasks() {
        global $wpdb;
        $table_students = $wpdb->prefix . 'lms_students';
        
        // Sadece ID'leri çekerek belleği rahatlatıyoruz
        $students = $wpdb->get_results( "SELECT id FROM $table_students" );
        
        if ( empty( $students ) ) {
            return;
        }

        $delay = 0; 
        foreach ( $students as $student ) {
            // Sunucunun aynı anda kilitlenmesini önlemek için her isteği 10 saniye arayla zamanla
            wp_schedule_single_event( time() + $delay, 'lms_sync_single_student', [ $student->id ] );
            $delay += 10;
        }
    }

    // YENİ: Sadece tek bir öğrencinin API'sine bağlanan metot
    public static function process_single_student( $student_id ) {
        global $wpdb;
        $table_students = $wpdb->prefix . 'lms_students';
        
        $student = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_students WHERE id = %d", $student_id ) );
        if ( ! $student ) return;

        self::process_student_pages( $student );
    }

    private static function process_student_pages( $student ) {
        $paged = 1;
        do {
            $endpoint = rtrim( $student->site_url, '/' ) . '/wp-json/lms/v1/content?page=' . $paged . '&uid=' . intval( $student->id ) . '&_t=' . time();
            $response = wp_remote_get( $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $student->api_token
                ],
                'timeout' => 25
            ]);
            
            if ( is_wp_error( $response ) ) {
                break;
            }
            
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            
            if ( ! isset( $data['posts'] ) || empty( $data['posts'] ) ) {
                break;
            }
            
            self::save_posts_batch( $student->id, $data['posts'] );
            $total_pages = intval( $data['total_pages'] ?? 1 );
            $paged++;
        } while ( $paged <= $total_pages );
    }

    private static function save_posts_batch( $student_id, $posts ) {
        global $wpdb;
        $table_posts     = $wpdb->prefix . 'lms_posts';
        $table_revisions = $wpdb->prefix . 'lms_post_revisions';
        
        foreach ( $posts as $post ) {
            $existing_post = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, current_hash FROM $table_posts WHERE student_id = %d AND remote_post_id = %d",
                $student_id,
                $post['id']
            ));
            
            if ( $existing_post ) {
                if ( $existing_post->current_hash !== $post['hash'] ) {
                    $wpdb->update(
                        $table_posts,
                        [
                            'post_title'         => sanitize_text_field( $post['title'] ),
                            'full_content'       => wp_kses_post( $post['content'] ),
                            'post_date'          => sanitize_text_field( $post['post_date'] ),
                            'post_modified'      => sanitize_text_field( $post['post_modified'] ),
                            'post_start_time'    => sanitize_text_field( $post['post_start_time'] ?? $post['post_date'] ),
                            'post_end_time'      => sanitize_text_field( $post['post_end_time'] ?? $post['post_modified'] ),
                            'word_count'         => intval( $post['word_count'] ),
                            'internal_links'     => intval( $post['internal_links'] ),
                            'external_links'     => intval( $post['external_links'] ),
                            'total_images'       => intval( $post['total_images'] ),
                            'missing_alt_images' => intval( $post['missing_alt'] ),
                            'post_tags'          => sanitize_text_field( $post['post_tags'] ),
                            'current_hash'       => sanitize_text_field( $post['hash'] )
                        ],
                        [ 'id' => $existing_post->id ],
                        [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ],
                        [ '%d' ]
                    );
                    $wpdb->insert(
                        $table_revisions,
                        [
                            'lms_post_id'       => $existing_post->id,
                            'content_hash'      => sanitize_text_field( $post['hash'] ),
                            'modification_flag' => 1
                        ],
                        [ '%d', '%s', '%d' ]
                    );
                }
            } else {
                $wpdb->insert(
                    $table_posts,
                    [
                        'student_id'         => $student_id,
                        'remote_post_id'     => intval( $post['id'] ),
                        'post_title'         => sanitize_text_field( $post['title'] ),
                        'post_url'           => esc_url_raw( $post['url'] ),
                        'full_content'       => wp_kses_post( $post['content'] ),
                        'post_date'          => sanitize_text_field( $post['post_date'] ),
                        'post_modified'      => sanitize_text_field( $post['post_modified'] ),
                        'post_start_time'    => sanitize_text_field( $post['post_start_time'] ?? $post['post_date'] ),
                        'post_end_time'      => sanitize_text_field( $post['post_end_time'] ?? $post['post_modified'] ),
                        'word_count'         => intval( $post['word_count'] ),
                        'internal_links'     => intval( $post['internal_links'] ),
                        'external_links'     => intval( $post['external_links'] ),
                        'total_images'       => intval( $post['total_images'] ),
                        'missing_alt_images' => intval( $post['missing_alt'] ),
                        'post_tags'          => sanitize_text_field( $post['post_tags'] ),
                        'current_hash'       => sanitize_text_field( $post['hash'] )
                    ],
                    [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ]
                );
                $new_post_id = $wpdb->insert_id;
                $wpdb->insert(
                    $table_revisions,
                    [
                        'lms_post_id'       => $new_post_id,
                        'content_hash'      => sanitize_text_field( $post['hash'] ),
                        'modification_flag' => 0
                    ],
                    [ '%d', '%s', '%d' ]
                );
            }
        }
    }
}