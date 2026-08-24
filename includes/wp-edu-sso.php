<?php
// --- Host Tarafı SSO (Tek Tıkla Giriş) Dinleyicisi ---
add_action( 'init', 'wp_edu_host_handle_sso_login' );
function wp_edu_host_handle_sso_login() {
    if ( ! isset( $_GET['wp_edu_sso'] ) ) {
        return;
    }

    $token = sanitize_text_field( $_GET['wp_edu_sso'] );
    
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

    // Oturumu açık hale getir (Login)
    wp_clear_auth_cookie();
    wp_set_current_user( $user->ID );
    wp_set_auth_cookie( $user->ID, true );

    // Başarılı giriş sonrası Host yönetim paneline yönlendir
    wp_redirect( admin_url( 'index.php' ) );
    exit;
}
?>