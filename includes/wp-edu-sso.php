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
        wp_die( 'SSO bağlantısı geçersiz. Lütfen öğrenci eklentinizi güncelleyin ve panonuzdan yeni bir bağlantı oluşturun.', 'Güvenlik Hatası', [ 'response' => 403 ] );
    }

    // 2. 24 Saat (86400 saniye) Zaman Aşımı Kontrolü
    if ( ( time() - $time ) > DAY_IN_SECONDS ) {
        wp_die( 'Bu güvenli giriş bağlantısının süresi (24 saat) dolmuş. Lütfen öğrenci panelinize dönüp butona tekrar tıklayın.', 'SSO Zaman Aşımı', [ 'response' => 403 ] );
    }

    // 3. Hash Bütünlük (Integrity) Kontrolü (Bağlantı kurcalanmış mı?)
    $expected_hash = hash( 'sha256', $token . $time );
    if ( ! hash_equals( $expected_hash, $hash ) ) {
        wp_die( 'SSO güvenlik doğrulaması başarısız. Bağlantı tahrif edilmiş.', 'Güvenlik İhlali', [ 'response' => 403 ] );
    }

    global $wpdb;
    $table_students = $wpdb->prefix . 'lms_students';

    // Bu token hangi öğrenciye ait veritabanından bul
    $student = $wpdb->get_row( $wpdb->prepare( 
        "SELECT * FROM $table_students WHERE api_token = %s", 
        $token 
    ) );

    if ( ! $student ) {
        wp_die( 'Geçersiz veya süresi dolmuş SSO oturum anahtarı.', 'SSO Hata', [ 'response' => 403 ] );
    }

    // Host sitesinde bu e-postaya ait WP kullanıcısını bul
    $user = get_user_by( 'email', $student->student_email );
    if ( ! $user ) {
        wp_die( 'Host sitesinde bu e-posta adresine karşılık gelen bir kullanıcı bulunamadı.', 'Kullanıcı Bulunamadı', [ 'response' => 404 ] );
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