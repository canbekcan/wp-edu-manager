<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_EDU_Manager_Github_Updater {
    
    private $user;
    private $repo;
    private $plugin_file;
    private $plugin_slug;
    private $plugin_data;

    public function __construct( $user, $repo, $plugin_file ) {
        $this->user        = $user;
        $this->repo        = $repo;
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename( $plugin_file );

        add_action( 'admin_init', [ $this, 'init_plugin_data' ] );
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_popup' ], 10, 3 );
        add_filter( 'upgrader_post_install', [ $this, 'post_install' ], 10, 3 );
    }

    public function init_plugin_data() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data( $this->plugin_file );
    }

    private function get_github_release() {
        $transient_name = 'wp_edu_updater_' . $this->repo;
        $release = get_transient( $transient_name );

        if ( false === $release ) {
            $url = "https://api.github.com/repos/{$this->user}/{$this->repo}/releases/latest";
            $response = wp_remote_get( $url, [
                'headers' => [
                    'User-Agent' => 'WordPress-Plugin-Updater'
                ]
            ]);

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $release = json_decode( wp_remote_retrieve_body( $response ) );
            set_transient( $transient_name, $release, 6 * HOUR_IN_SECONDS );
        }

        return $release;
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_github_release();
        if ( ! $release || ! isset( $release->tag_name ) ) {
            return $transient;
        }

        $version = ltrim( $release->tag_name, 'v' );

        if ( version_compare( $this->plugin_data['Version'], $version, '<' ) ) {
            $plugin_info = new stdClass();
            $plugin_info->slug        = $this->plugin_slug;
            $plugin_info->plugin      = $this->plugin_slug;
            $plugin_info->new_version = $version;
            $plugin_info->url         = $release->html_url;
            $plugin_info->package     = $release->zipball_url;

            $transient->response[ $this->plugin_slug ] = $plugin_info;
        }

        return $transient;
    }

    public function plugin_popup( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_github_release();
        if ( ! $release ) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name          = $this->plugin_data['Name'];
        $plugin_info->slug          = $this->plugin_slug;
        $plugin_info->version       = ltrim( $release->tag_name, 'v' );
        $plugin_info->author        = $this->plugin_data['Author'];
        $plugin_info->homepage      = $this->plugin_data['PluginURI'];
        $plugin_info->download_link = $release->zipball_url;
        $plugin_info->sections      = [
            'description' => esc_html__( 'This plugin is updated via GitHub.', 'wp-edu-manager' ),
            'changelog'   => nl2br( esc_html( $release->body ) )
        ];

        return $plugin_info;
    }

    public function post_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
            $plugin_dir = plugin_dir_path( $this->plugin_file );
            $wp_filesystem->move( $result['destination'], $plugin_dir );
            $result['destination'] = $plugin_dir;
        }

        return $response;
    }
}