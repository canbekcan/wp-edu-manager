<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Manager_Github_Updater {
    
    private $repo_user;
    private $repo_name;
    private $plugin_file;
    private $plugin_slug;
    private $plugin_basename;
    private $plugin_data;

    public function __construct( $repo_user, $repo_name, $plugin_file ) {
        $this->repo_user       = $repo_user;
        $this->repo_name       = $repo_name;
        $this->plugin_file     = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $this->plugin_slug     = dirname( $this->plugin_basename );

        // admin_init kaldırıldı. Transient filtreleri eklendi.
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup' ], 10, 3 );
        
        // YENİ: Client dosyasından alınan sağlam klasör isimlendirme yöntemi
        add_filter( 'upgrader_source_selection', [ $this, 'fix_github_folder_name' ], 10, 3 );
    }

    private function ensure_plugin_data() {
        if ( empty( $this->plugin_data ) ) {
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $this->plugin_data = get_plugin_data( $this->plugin_file );
        }
    }

    private function get_github_readme() {
        $transient_name = 'wp_edu_readme_' . $this->repo_name;
        $readme_html = get_transient( $transient_name );

        if ( false === $readme_html ) {
            $url = "https://api.github.com/repos/{$this->repo_user}/{$this->repo_name}/readme";
            $response = wp_remote_get( $url, [
                'headers' => [
                    'User-Agent' => 'WordPress-Plugin-Updater',
                    'Accept'     => 'application/vnd.github.v3.html' 
                ],
                'timeout' => 10
            ]);

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $readme_html = wp_remote_retrieve_body( $response );
                set_transient( $transient_name, $readme_html, 6 * HOUR_IN_SECONDS );
            } else {
                $readme_html = '<p>' . esc_html__( 'Description could not be loaded from GitHub.', 'wp-edu-manager' ) . '</p>';
            }
        }

        return $readme_html;
    }

    private function get_github_release() {
        static $runtime_cache = null;
        if ( null !== $runtime_cache ) {
            return $runtime_cache;
        }

        $transient_name = 'wp_edu_updater_' . $this->repo_name;
        $release = get_transient( $transient_name );

        if ( false === $release ) {
            $url = "https://api.github.com/repos/{$this->repo_user}/{$this->repo_name}/releases/latest";
            $response = wp_remote_get( $url, [
                'headers' => [
                    'User-Agent' => 'WordPress-Plugin-Updater',
                    'Accept'     => 'application/vnd.github.v3.html+json' 
                ],
                'timeout' => 10
            ]);

            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code === 200 ) {
                    $release = json_decode( wp_remote_retrieve_body( $response ) );
                    set_transient( $transient_name, $release, 6 * HOUR_IN_SECONDS );
                } elseif ( $code === 403 ) {
                    set_transient( $transient_name, 'rate_limited', 15 * MINUTE_IN_SECONDS );
                }
            }
        }

        $runtime_cache = ( $release === 'rate_limited' ) ? false : $release;
        return $runtime_cache;
    }

    public function check_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        $release = $this->get_github_release();
        if ( ! $release || empty( $release->tag_name ) ) {
            return $transient;
        }

        $this->ensure_plugin_data();
        
        $current_version = $this->plugin_data['Version'];
        $new_version     = ltrim( $release->tag_name, 'v' );

        $plugin_info = new stdClass();
        $plugin_info->slug        = $this->plugin_slug;
        $plugin_info->plugin      = $this->plugin_basename;
        $plugin_info->new_version = $new_version;
        $plugin_info->url         = $release->html_url;
        $plugin_info->package     = $release->zipball_url;

        // YENİ: no_update dizisi eklendi
        if ( version_compare( $current_version, $new_version, '<' ) ) {
            $transient->response[$this->plugin_basename] = $plugin_info;
        } else {
            if ( ! isset( $transient->no_update ) ) {
                $transient->no_update = [];
            }
            $transient->no_update[$this->plugin_basename] = $plugin_info;
            
            if ( isset( $transient->response[$this->plugin_basename] ) ) {
                unset( $transient->response[$this->plugin_basename] );
            }
        }

        return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || empty( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_github_release();
        if ( ! $release ) {
            return $result;
        }

        $this->ensure_plugin_data();

        $plugin_info = new stdClass();
        $plugin_info->name          = $this->plugin_data['Name'];
        $plugin_info->slug          = $this->plugin_slug;
        $plugin_info->version       = ltrim( $release->tag_name, 'v' );
        $plugin_info->author        = $this->plugin_data['Author'];
        $plugin_info->homepage      = $this->plugin_data['PluginURI'];
        $plugin_info->download_link = $release->zipball_url;
        
        $readme_content = $this->get_github_readme();
        $changelog_content = isset( $release->body_html ) ? $release->body_html : nl2br( esc_html( $release->body ) );

        $plugin_info->sections = [
            'description' => $readme_content,
            'changelog'   => $changelog_content
        ];

        return $plugin_info;
    }

    // YENİ: Client'tan alınan klasör ismi sabitleme fonksiyonu
    public function fix_github_folder_name( $source, $remote_source, $upgrader ) {
        global $wp_filesystem;
        
        if ( ! $wp_filesystem || ! isset( $source ) ) {
            return $source;
        }
        
        $source_clean = untrailingslashit( $source );
        $parent_dir   = dirname( $source_clean ); 
        $expected_dir = trailingslashit( $parent_dir ) . $this->plugin_slug; 
        
        $source_trail = trailingslashit( $source );
        $main_file    = basename( $this->plugin_file );
        
        if ( $wp_filesystem->exists( $source_trail . $main_file ) ) {
            if ( $source_clean !== $expected_dir ) {
                if ( $wp_filesystem->exists( $expected_dir ) ) {
                    $wp_filesystem->delete( $expected_dir, true );
                }
                
                if ( $wp_filesystem->move( $source_clean, $expected_dir, true ) ) {
                    return trailingslashit( $expected_dir );
                }
            }
        }
        
        return $source;
    }
}