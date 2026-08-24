<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_User_Manager {

    /**
     * Öğrenci e-postası ile Host sitesinde yeni bir WP Kullanıcısı (Contributor) oluşturur.
     * 
     * @param string $email Öğrenci e-posta adresi
     * @param string $site_url Öğrenci site adresi
     * @return int|bool Başarılıysa User ID, başarısızsa false
     */
    public static function provision_student_user( $email, $site_url ) {
        if ( ! is_email( $email ) ) {
            return false;
        }

        // 1. E-posta sistemde zaten kayıtlı mı kontrol et
        $existing_user_id = email_exists( $email );
        if ( $existing_user_id ) {
            // Kullanıcı zaten varsa, ID'sini geri dön
            return $existing_user_id;
        }

        // 2. Kullanıcı adı oluştur (e-postanın ilk kısmından)
        $email_parts = explode( '@', $email );
        $base_username = sanitize_user( $email_parts[0], true );
        $username = $base_username;
        
        $suffix = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . '_' . $suffix;
            $suffix++;
        }

        // 3. Rastgele güvenli şifre oluştur (Öğrenci buraya doğrudan giriş yapmayacak)
        $random_password = wp_generate_password( 16, true, true );

        // 4. Kullanıcıyı İçerik Sağlayıcı (Contributor) rolüyle oluştur
        $user_data = [
            'user_login' => $username,
            'user_pass'  => $random_password,
            'user_email' => $email,
            'user_url'   => $site_url,
            'role'       => 'contributor' // ÖNEMLİ: Kendi kendine yayınlama yetkisi yoktur.
        ];

        $new_user_id = wp_insert_user( $user_data );

        if ( is_wp_error( $new_user_id ) ) {
            return false;
        }

        return $new_user_id;
    }
}