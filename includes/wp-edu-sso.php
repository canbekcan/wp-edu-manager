<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// --- Host Tarafı SSO (Tek Tıkla Giriş) Dinleyicisi ---
add_action( 'init', 'wp_edu_host_handle_sso_login' );
function wp_edu_host_handle_sso_login() {
    if ( ! isset( $_GET['wp_edu_sso'] ) ) {
        return;
    }

    $token = sanitize_text_field( $_GET['wp_edu_sso'] );
    $time  = isset( $_GET['t'] ) ? intval( $_GET['t'] ) : 0;
    $hash  = isset( $_GET['h'] ) ? sanitize_text_field( $_GET['h'] ) : '';
    
    // 1. Dinamik Zaman Damgası Kontrolü (Geriye dönük uyumluluğu zorunlu kılıyoruz)
    if ( empty( $time ) || empty( $hash ) ) {
        wp_die( 
            __( 'Invalid SSO connection. Please update your student plugin and generate a new connection from your dashboard.', 'wp-edu-manager' ), 
            __( 'Security Error', 'wp-edu-manager' ), 
            [ 'response' => 403 ] 
        );
    }

    // 2. 24 Saat (86400 saniye) Zaman Aşımı Kontrolü
    if ( ( time() - $time ) > DAY_IN_SECONDS ) {
        wp_die( 
            __( 'This secure login link has expired (24 hours). Please return to your student dashboard and click the button again.', 'wp-edu-manager' ), 
            __( 'SSO Timeout', 'wp-edu-manager' ), 
            [ 'response' => 403 ] 
        );
    }

    // 3. Hash Bütünlük (Integrity) Kontrolü (Bağlantı kurcalanmış mı?)
    $expected_hash = hash( 'sha256', $token . $time );
    if ( ! hash_equals( $expected_hash, $hash ) ) {
        wp_die( 
            __( 'SSO security verification failed. The link has been tampered with.', 'wp-edu-manager' ), 
            __( 'Security Violation', 'wp-edu-manager' ), 
            [ 'response' => 403 ] 
        );
    }

    global $wpdb;
    $table_students = $wpdb->prefix . 'lms_students';

    // Bu token hangi öğrenciye ait veritabanından bul
    $student = $wpdb->get_row( $wpdb->prepare( 
        "SELECT * FROM $table_students WHERE api_token = %s", 
        $token 
    ) );

    if ( ! $student ) {
        wp_die( 
            __( 'Invalid or expired SSO session key.', 'wp-edu-manager' ), 
            __( 'SSO Error', 'wp-edu-manager' ), 
            [ 'response' => 403 ] 
        );
    }

    // Host sitesinde bu e-postaya ait WP kullanıcısını bul
    $user = get_user_by( 'email', $student->student_email );
    if ( ! $user ) {
        wp_die( 
            __( 'No user found on the Host site corresponding to this email address.', 'wp-edu-manager' ), 
            __( 'User Not Found', 'wp-edu-manager' ), 
            [ 'response' => 404 ] 
        );
    }

    // 4. Oturum Çerezini (Cookie) 24 Saat ile Sınırla
    add_filter( 'auth_cookie_expiration', function() { return DAY_IN_SECONDS; } );

    // Oturumu açık hale getir (Login)
    wp_clear_auth_cookie();
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, false ); // 'Remember Me' (Kalıcı çerez) false yapılarak engellendi

    // Başarılı giriş sonrası Host yönetim paneline yönlendir
    wp_safe_redirect( admin_url( 'index.php' ) );
    exit;
}