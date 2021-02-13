<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       AuthorURI
 * @since      1.0.0
 *
 * @package    Store_Scores
 * @subpackage Store_Scores/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Store_Scores
 * @subpackage Store_Scores/public
 * @author     Steve Fisher <dr.s.m.fisher@gmail.com>
 */
class Store_Scores_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
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

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/store-scores-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
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

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/store-scores-public.js', array( 'jquery' ), $this->version, false );

    }

    public function register_short_codes() {
        add_shortcode('store-score', [$this, 'store_score_function']);
    }

    public function store_score_function($atts, $content=null) {
        write_log([$atts, $content]);
        $me = wp_get_current_user();
        if ($me->ID === 0) return 'Sorry you must be logged in to enter a result.';
        if (array_key_exists('competition', $atts)) {
            $competition = $atts['competition'];
            global $wpdb;
            $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'ss_competition'", $competition);
            $r = $wpdb->get_results($sql, OBJECT);
            if (count($r) != 1) return 'Failed to find exactly one competition with a title of '.$competition;
            $pid = $r[0]->ID;
            $competitors = get_post_meta($pid, 'competitors', true);
            write_log($competitors);
            if (! in_array($me->ID, $competitors)) return 'Sorry you are not competing in this event';
        } else {
            return 'Competion not specified in call to short code.';
        }
        $html = '';
        $html .= 'You are user'  . $me->ID;
        $html .= '<form>';
        $html .= '<label for="oppo">Identify your opponent:</label>';
        $html .= '<select id="oppo">';
        $html .= '</select>'; 
        $html .= '<input type="submit">';
        $html .= '</form>';
        return $html;
    }

    public function store_scores_register_competition() {
        $labels = array(
            'name'               => __( 'Competitions' ),
            'singular_name'      => __( 'Competition' ),
            'add_new'            => __( 'Add New' ),
            'add_new_item'       => __( 'Add New Competition' ),
            'edit_item'          => __( 'Edit Competition' ),
            'new_item'           => __( 'New Competition' ),
            'all_items'          => __( 'All Competitions' ),
            'view_item'          => __( 'View Competition' ),
            'search_items'       => __( 'Search Competitions' ),
            'not_found'          => __( 'No competitions found' ),
            'not_found_in_trash' => __( 'No competitions found in the Trash' ), 
        );
        $args = array(
            'labels'        => $labels,
            'description'   => 'Holds our products and product specific data',
            'public'        => true,
            'menu_position' => 5,
            'supports'      => array( 'title', 'editor'  ),
            'has_archive'   => true,
        );
        register_post_type( 'ss_competition', $args ); 
    }

    public function add_competitor_boxes() {
        $max_players = get_option('store_scores_options')['max_players'];
        for ($x = 0; $x < $max_players; $x++) {
            add_meta_box( 
                'competitor_box_' . $x,
                __( 'Competitor ' . $x),
                array($this, 'competitor_content'),
                'ss_competition',
                'advanced',
                'default',
                [$x]
            );
        }
    }

    public function competitor_content( $post, $args ) {
        $x = $args['args'][0];
        $post_name = 'competitor_'.$x;
        wp_nonce_field( plugin_basename( __FILE__ ), 'competitor_box_content_nonce' );
        $pm = get_post_meta($post->ID);
        if (array_key_exists('competitors',$pm)) {
            $competitors = unserialize($pm['competitors'][0]);
            $competitor = $competitors[$x];
        } else {
            $competitor = 0;
        }
        echo '<label for="' . $post_name . '"></label>';
        echo '<select id="' . $post_name . '" name="' . $post_name . '" size="1">';
        echo '<option selected value="0"></option>';
        foreach (get_users('orderby=meta_value&meta_key=last_name') as $user) {
            $selected = ($user->ID == $competitor) ? ' selected' : '';
            $name = $user->get('last_name') . ', ' . $user->get('first_name') . esc_html(' <') . $user->get('user_email') . esc_html('>'); 
            echo '<option' . $selected . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        echo '</select>';
    }

    public function save_competitor( $post_id ) {

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

        $key = 'competitor_box_content_nonce';
        if(! array_key_exists ($key, $_POST) )return;
        if ( !wp_verify_nonce( $_POST[$key], plugin_basename( __FILE__ ) ) )
            return;

        if ( 'post' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) )
                return;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) )
                return;
        }
        $max_players = get_option('store_scores_options')['max_players'];
        for ($x = 0; $x < $max_players; $x++) {
            $competitors[] = $_POST['competitor_'.$x];
        }

        update_post_meta( $post_id, 'competitors', $competitors );
    }
}
