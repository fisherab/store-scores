<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    store_scores
 * @subpackage store_scores/admin
 *
 */
class Store_Scores_Admin {

    private $plugin_name;
    private $version;
    private $competition;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->competition = new Store_Scores_Competition();
    }

    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Store_Scores_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Store_Scores_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/store-scores-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Store_Scores_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Store_Scores_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/store-scores-admin.js', array( 'jquery' ), $this->version, false );

    }

    public function register_competition () {
        $this->competition->register_competition();
    }

    public function add_competition_boxes() {
        $this->competition->add_competition_boxes();
    }

    public function save_competition($post_id) {
          $this->competition->save_competition($post_id);
    }

    public function store_scores_options() {
        add_options_page( 'Store Scores Options', 'Store Scores', 'manage_options', 'store-scores-options-identifier', [$this, 'render_options'] );
    }

    public function store_scores_register_settings() {
        register_setting( 'store_scores_options', 'store_scores_options', array('type'=> 'array', 'sanitize_callback' => [$this, 'validate_all_options'])) ;
        add_settings_section( 'misc_settings', 'Misc Settings', [$this, 'render_section'], 'store-scores-options-identifier' );
        add_settings_field( 'stores_scores_options_min_players', 'Minimum players in a competition', [$this, 'render_min_players'], 'store-scores-options-identifier', 'misc_settings' );
        add_settings_field( 'stores_scores_options_max_players', 'Maximum players in a competition', [$this, 'render_max_players'], 'store-scores-options-identifier', 'misc_settings' );
    }

    public function render_section() {
        // Nothing useful to say here
    }

    public function render_max_players() {
        $options = get_option( 'store_scores_options' );
        $value = '';
        if (array_key_exists('max_players', $options)) $value=' value="' . $options['max_players'] . '"';
        echo '<input id="store_scores_options_max_players" name="store_scores_options[max_players]" type="text"' . $value . '/>';
    } 

    public function render_min_players() {
        $options = get_option( 'store_scores_options' );
        $value = '';
        if (array_key_exists('min_players', $options)) $value=' value="' . $options['min_players'] . '"';
        echo '<input id="store_scores_options_max_players" name="store_scores_options[min_players]" type="text"' . $value . '/>';
    }

    public function validate_all_options ($input) {
        write_log(['Validate ', $input]);
        $newinput['min_players'] = intval($input['min_players']);
        if ($newinput['min_players'] <= 0) {
            $newinput['min_players'] = 2;
        }
        $newinput['max_players'] = intval($input['max_players']);
        if ($newinput['max_players'] <= 0 ) {
            $newinput['max_players'] = 100;
        }
        if ($newinput['max_players'] < $newinput['min_players']) {
            $newinput['max_players'] = $newinput['min_players'];
        }
        return $newinput;
    }

    public function render_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
?>
        <h2>Store Score Plugin Settings</h2>
            <form action="options.php" method="post">
<?php 
        settings_fields( 'store_scores_options' );
        do_settings_sections( 'store-scores-options-identifier' );
?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
<?php
    }
}
