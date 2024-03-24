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
        add_settings_field( 'stores_scores_options_competitors_increment', 'How many empty slots for new competitors', [$this, 'render_competitors_increment'], 'store-scores-options-identifier', 'misc_settings' );
        add_settings_field( 'stores_scores_options_managers_increment', 'How many empty slots for managers', [$this, 'render_managers_increment'], 'store-scores-options-identifier', 'misc_settings' );
    }

    public function render_section() {
        // Nothing useful to say here
    }

    public function render_competitors_increment() {
        $options = get_option( 'store_scores_options', []);
        $value = '';
        if (array_key_exists('competitors_increment', $options)) $value=' value="' . $options['competitors_increment'] . '"';
        echo '<input id="store_scores_options_competitors_increment" name="store_scores_options[competitors_increment]" type="text"' . $value . '/>';
    }

    public function render_managers_increment() {
        $options = get_option( 'store_scores_options', []);
        $value = '';
        if (array_key_exists('managers_increment', $options)) $value=' value="' . $options['managers_increment'] . '"';
        echo '<input id="store_scores_options_managers_increment" name="store_scores_options[managers_increment]" type="text"' . $value . '/>';
    } 

    public function validate_all_options ($input) {
        $newinput['competitors_increment'] = intval($input['competitors_increment']);
        if ($newinput['competitors_increment'] <= 0) {
            $newinput['competitors_increment'] = 1;
        }
        $newinput['managers_increment'] = intval($input['managers_increment']);
        if ($newinput['managers_increment'] <= 0 ) {
            $newinput['managers_increment'] = 1;
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
