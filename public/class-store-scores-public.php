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

    public function store_scores_competitor() {
        add_meta_box( 
            'product_price_box',
            __( 'Product Price', 'myplugin_textdomain' ),
            array($this, 'product_price_box_content'),
            'ss_competition'
        );
    }

    public function product_price_box_content( $post ) {
        wp_nonce_field( plugin_basename( __FILE__ ), 'product_price_box_content_nonce' );
        $pm = get_post_meta($post->ID);
        if (array_key_exists('product_price',$pm)) {
            $value = ' value="'.get_post_meta($post->ID)['product_price'][0].'"';
        } else {
            $value = '';
        }
        echo '<label for="product_price"></label>';
        echo '<input type="text" id="product_price" name="product_price" placeholder="enter a price"' . $value . '/>';
    }

    public function product_price_box_save( $post_id ) {


        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

        $key = 'product_price_box_content_nonce';
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
        $product_price = $_POST['product_price'];
        update_post_meta( $post_id, 'product_price', $product_price );
    }
}
