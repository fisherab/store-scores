<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Store_Scores
 * @subpackage Store_Scores/public
 *
 */

/** 
 * Class holding a single match result
 */
class Store_Scores_Result {
    private $submitter;
    private $opponent;
    private $games;
    private $date;

    public function __construct($submitter, $opponent, $date, $games) {
        $this->submitter = $submitter;
        $this->opponent = $opponent;
        $this->games = $games;
        $this->date = $date;
    }

    public function get_submitter() {
        return $submitter;
    }

}

/**
 * Class providing public-facing functionality of store-scores
 */
class Store_Scores_Public {

    private $plugin_name;
    private $version;
    protected $types = [];

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        foreach (get_declared_classes() as $classname) {
            if (in_array('Store_Scores_Competition_Type', class_parents($classname))) {
                $obj = new $classname();
                $tag = $obj->get_tag();
                $this->types [$tag] = $obj;
            }       
        }

        $this->register_short_codes();
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
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/store-scores-public.css', array(), $this->version, 'all' );

    }

    public function enqueue_scripts() {
        /**
         * This function is provided for demonstration purposes only.         *
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

    /**
     * Add all shortcodes for the user to add to pages or posts
     */
    private function register_short_codes() {
        add_shortcode('ss-store-score', [$this, 'store_score_function']);
        add_shortcode('ss-get-description', [$this, 'get_description']);
    }

    /**
     *  Return human description about the competition
     */
    public function get_description($atts) {
        if (array_key_exists('competition', $atts)) {
            $competition = $atts['competition'];
            global $wpdb;
            $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'ss_competition'", $competition);
            $r = $wpdb->get_results($sql, OBJECT);
            if (count($r) != 1) return 'Failed to find exactly one competition with a title of '.$competition;
            $pid = $r[0]->ID;
            $type = get_post_meta($pid, 'type', true);
            return $this->types[$type]->get_description();
        } else {
            return 'Competion not specified in call to short code.';
        }
    }      

    /**
     * A competition of type competition_ss is a post holding all the data about a competition.
     *
     * The competition has post meta data:
     *  * competitors - an array of competitor ids registered for this competition
     *  * results - an array of game result objects.
     *
     * @param array $atts arguments with the short code
     * @param string $content material between the opening and closing of the shortcode
     */
    public function store_score_function($atts, $content=null) {
        $me = wp_get_current_user();
        if ($me->ID === 0) return 'Sorry you must be logged in to enter a result.';
        if (array_key_exists('competition', $atts)) {
            $competition = $atts['competition'];
            global $wpdb;
            $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'ss_competition'", $competition);
            $r = $wpdb->get_results($sql, OBJECT);
            if (count($r) != 1) return 'Failed to find exactly one competition with a title of '.$competition;
            $pid = $r[0]->ID;
            $type = get_post_meta($pid, 'type', true);                                                             $bestof = get_post_meta($pid, 'bestof', true);   
            $competitors = get_post_meta($pid, 'competitors', true);
            if (! in_array($me->ID, $competitors)) return 'Sorry you are not competing in this event';
        } else {
            return 'Competion not specified in call to short code.';
        }
        $opponents = $this->types[$type]->get_opponents($pid,$me->ID);
        write_log(["Opps", $opponents]);

        $html = '';
        $html .= '<form>';
        $html .= '<label for="oppo">Identify your opponent:</label>';
        $html .= '<select id="oppo">';

        foreach ($opponents as $opponent) {
            write_log($opponent);

            $user = get_user_by('ID', $opponent);
            write_log($user->get('last_name'));
            $name = $user->get('last_name') . ', ' . $user->get('first_name') . esc_html(' <') . $user->get('user_email') . esc_html('>');
            $html .=  '<option' . "" . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        $html .= '</select>';
        for ($i = 1; $i <= $bestof; $i++) {
            $html .= '<div><input type="number" min="0" max="26" size="2" name="you' . $i. '" label="you' . $i. '" >-<input type="number" min="0" max="26" size="2" name="opp' . $i. '" label="opp' . $i. '" ></div>';
            
        }  
        $html .= '<input type="submit">';
        $html .= '</form>';
        return $html;
    }

}
