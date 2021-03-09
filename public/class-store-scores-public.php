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
        add_action('template_redirect', [$this,'process_submit_score']);
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
        add_shortcode('ss-get-description', [$this, 'get_description']);
        add_shortcode('ss-enter-score', [$this, 'enter_score']);
        add_shortcode('ss-show-results', [$this, 'show_results']);
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
    public function enter_score($atts, $content=null) {
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

        $html = '';
        $html .= '<form action="." method="POST">';
        $html .= wp_nonce_field('submit_score', 'annie the aardvark', true, false);
        $html .= '<input type="hidden" name="comp_id" value="' .$pid . '">';
        $html .= '<input type="hidden" name="you_id" value="' .$me->ID . '">';

        $html .= '<label for="dateofmatch">The date of the match</label>';
        $html .= '<input type="date" id="dateofmatch" name="dateofmatch" value="' . date("Y-m-d") .'">';

        $html .= '<label for="opp"><br/>Identify your opponent:</label>';
        $html .= '<select id="opp" name="opp_id">';

        foreach ($opponents as $opponent) {
            $user = get_user_by('ID', $opponent);
            $name = $user->get('last_name') . ', ' . $user->get('first_name') . esc_html(' <') . $user->get('user_email') . esc_html('>');
            $html .=  '<option' . "" . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        $html .= '</select>';
        $html .= '<div>';
        if ($bestof == 1) {
            $html .= '<label for="you1"><br/>Please enter results of the match with your result first</label>';
        } else {
            $html .= '<label for="you1"><br/>Please enter the results in pairs for each game of the match. For each game your results should appear first</label>';
        }
        for ($i = 1; $i <= $bestof; $i++) {
            if ($i != 1) $html .= ', ';
            $html .= '<input class="croquet" type="number" min="0" max="26" size="2" name="you' . $i. '" id="you' . $i. '" >-<input class="croquet" type="number" min="0" max="26" size="2" name="opp' . $i. '" id="opp' . $i. '" >';


        }  
        $html .= '</div>';
        $html .= '<input type="submit" name="send-scores" id="submit" class="submit"/>';
        $html .= '</form>';

        if ( isset( $_GET['fail'] ) ) {
            $fail = sanitize_title( $_GET['fail'] );

            switch ( $fail ) {
            case 'nodraws' :
                $message = 'Draws are not permitted.';
                break;

            case 'extra' :
                $message = 'You have tried to record a superfluous game.';
                break;

            default :
                $message = 'Something went wrong.';
                break;
            }

            $html .= '<div class="fail"><p>' . esc_html( $message ) . '</p></div>';
        }

        if ( isset( $_GET['success'] ) ) {
            $html .= '<div class="success"><p>' . "Results succesfully uploaded" . '</p></div>';
        }

        return $html;
    }

    public function process_submit_score() {
        if ( ! isset( $_POST['send-scores'] ) || ! isset( $_POST['annie_the_aardvark'] ) )  {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['annie_the_aardvark'], 'submit_score' ) ) {
            return;
        }
        $fail = '';
        $comp_id = $_POST['comp_id'];
        $bestof = get_post_meta($comp_id, 'bestof', true); 

        $wins = 0;
        for ($i = 1; $i <= $bestof; $i++) {
            $you[$i] = $_POST['you' . $i];
            $opp[$i] = $_POST['opp' . $i];
            if (empty($you[$i])) {
                $you[$i] = 0;
            } if (empty($opp[$i])) {
                $opp[$i] = 0;
            }

            if ($you[$i] > $opp[$i]) {
                $wins++;
            }
        }
        if ($bestof == 1) {
            if ($you[1] == $opp[1]) {
                $fail = 'nodraws';
            }
        } elseif ($bestof == 3) {
            if ($you[1] == $opp[1]) {
                $fail = 'nodraws';
            }
            if (! $fail) {
                if ($you[2] == $opp[2]) {
                    $fail ='nodraws';
                }
                if (! $fail) {
                    $need3 = ($you[1] > $opp[1]) && ($you[2] < $opp[2]);
                    if (!$need3) {
                        if (($you[3] != 0) || ($opp[3] != 0)) {
                            $fail = 'extra';
                        } 
                    } else {
                        if ($you[3] == $opp[3]) {
                            $fail ='nodraws';
                        }
                    }
                }
            }
        }
        $url = remove_query_arg(["fail","success"]);
        if ($fail) {
            $url = add_query_arg('fail', $fail, $url);
        } else {
            $url = add_query_arg('success', 1, $url);
            $result['date'] = $_POST['dateofmatch'];
            $result['wins'] = $wins;
            $result['you'] = ['person' => $_POST['you_id'], 'scores' => $you];
            $result['opp'] = ['person' => $_POST['opp_id'], 'scores' =>$opp];
            $email = get_user_by('ID', $_POST['opp_id'])->get('user_email');
            $you_user = get_user_by('ID', $_POST['you_id']);

            $name = $you_user->get('first_name') . ' ' . $you_user->get('last_name');
            $subject = "Result recorded by " . $name;
            $msg = var_export($result, true);
            wp_mail($email, $subject, $msg);
            add_post_meta($comp_id, 'result', $result);
        }
        wp_safe_redirect( $url );
        exit();
    }

    /**
     *  Return formatted display of results
     */
    public function show_results($atts) {
        if (! array_key_exists('competition', $atts)) {
            return 'Competion not specified in call to short code.';
        }
        $competition = $atts['competition'];
        global $wpdb;
        $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'ss_competition'", $competition);
        $r = $wpdb->get_results($sql, OBJECT);
        if (count($r) != 1) {
            return 'Failed to find exactly one competition with a title of '.$competition;
        }
        $pid = $r[0]->ID;
        $type = get_post_meta($pid, 'type', true);
        return $this->types[$type]->get_results($pid);
    } 

}


