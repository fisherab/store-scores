<?php

/**
 * This is the core plugin class which is instantiated and run by the bootstrap file
 */
class Store_Scores {

    protected $loader;

    protected $plugin_name;

    protected $version;

    public function __construct() {
        if ( defined( 'STORE_SCORES_VERSION' ) ) {
            $this->version = STORE_SCORES_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'store-scores';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-store-scores-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-store-scores-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-store-scores-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-store-scores-public.php';
        $this->loader = new Store_Scores_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Store_Scores_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new Store_Scores_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'store_scores_options' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'store_scores_register_settings' );
    }

    private function define_public_hooks() {
        $plugin_public = new Store_Scores_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'store_scores_register_competition' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_public, 'add_competitor_boxes' );
        $this->loader->add_action('save_post',  $plugin_public, 'save_competitor' );
        $this->loader->add_action('init', $plugin_public, 'register_short_codes');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
