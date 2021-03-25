<?php

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
        if (array_key_exists('id', $atts)) {
            $comp_id = $atts['id'];
            if (get_post_type($comp_id) !== 'ss_competition') {
                return "The id specified is not of a competition";
            } 
            $type = get_post_meta($comp_id, 'type', true);
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
        $submitter_id = $me->ID;
        if (array_key_exists('id', $atts)) {
            $comp_id = $atts['id'];
            if (get_post_type($comp_id) !== 'ss_competition') {
                return "The id specified is not of a competition";
            } 
            $type = get_post_meta($comp_id, 'type', true);
            $type = get_post_meta($comp_id, 'type', true);
            $bestof = get_post_meta($comp_id, 'bestof', true);   
            $competitors = get_post_meta($comp_id, 'competitors', true);
            $managers = get_post_meta($comp_id, 'managers', true);
            if ($managers) {
                $tman = in_array($me->ID, $managers);
            } else {
                $tman = false;
            }
            if (! in_array($me->ID, $competitors) && ! $tman) return 'Sorry you are not competing in this event';
        } else {
            return 'Competion not specified in call to short code.';
        }

        $html = '';
        $html .= '<form action="." method="POST">';
        $html .= wp_nonce_field('submit_score', 'annie the aardvark', true, false);
        $html .= '<input type="hidden" name="comp_id" value="' .$comp_id . '">';
        $html .= '<input type="hidden" name="submitter_id" value ="' . $submitter_id . '">';
        $html .= '<label for="dateofmatch">The date of the match</label>';
        $html .= '<input type="date" id="dateofmatch" name="dateofmatch" value="' . date("Y-m-d") .'">';
        if ($tman) {
            $opponents = $this->types[$type]->get_opponents($comp_id,0);
            $html .= '<label for="you"><br/>Identify the first player:</label>'; 
            $html .= '<select id="you" name="you_id">';
            foreach ($opponents as $opponent) {
                $user = get_user_by('ID', $opponent);
                $name = $user->get('first_name') . ' ' . $user->get('last_name') . esc_html(' <') . $user->get('user_email') . esc_html('>');
                $html .=  '<option' . "" . ' value="' .$user->ID. '">'. $name . '</option>';
            }
            $html .= '</select>';
            $html .= '<label for="opp"><br/>Identify the opponent:</label>';
            $your = "the first person's";
        } else {
            $opponents = $this->types[$type]->get_opponents($comp_id,$me->ID);
            $html .= '<input type="hidden" name="you_id" value="' .$me->ID . '">';
            $html .= '<label for="opp"><br/>Identify your opponent:</label>';
            $your = "your";
        }
        $html .= '<select id="opp" name="opp_id">';

        foreach ($opponents as $opponent) {
            $user = get_user_by('ID', $opponent);
            $name = $user->get('first_name') . ' ' . $user->get('last_name') . esc_html(' <') . $user->get('user_email') . esc_html('>');
            $html .=  '<option' . "" . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        $html .= '</select>';
        $html .= '<div>';
        if ($bestof == 1) {
            $html .= '<label for="you1"><br/>Please enter the results of the match with ' . $your . ' result first</label>';
        } else {
            $html .= '<label for="you1"><br/>Please enter the results in pairs for each game of the match. For each game ' . $your . ' results should appear first</label>';
        }
        for ($i = 1; $i <= $bestof; $i++) {
            if ($i != 1) $html .= ', ';
            $html .= '<input class="croquet" type="number" min="0" max="26" size="2" name="you' . $i. '" id="you' . $i. '" >-<input class="croquet" type="number" min="0" max="26" size="2" name="opp' . $i. '" id="opp' . $i. '" >';


        }  
        $html .= '</div>';
        $html .= '<input type="submit" name="send-scores" id="submit-' . $comp_id . '"  class="submit"/>';
        $html .= '</form>';

        if ( isset( $_GET['comp_id']) && ($_GET['comp_id'] == $comp_id)) {
            if ( isset( $_GET['fail'] ) ) {
                $fail = sanitize_title( $_GET['fail'] );

                switch ( $fail ) {

                case 'nodraws' :
                    $message = 'Draws are not permitted.';
                    break;

                case 'extra' :
                    $message = 'You have tried to record a superfluous game.';
                    break;

                case 'nocontest':
                    $message = 'These people were not due to play.';
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

        for ($i = 1; $i <= $bestof; $i++) {
            $you[$i] = $_POST['you' . $i];
            $opp[$i] = $_POST['opp' . $i];
            if (empty($you[$i])) {
                $you[$i] = 0;
            } if (empty($opp[$i])) {
                $opp[$i] = 0;
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

        // A manager can submit results for an invalid pair of opponenets
        if (! $fail) { 
            $type = get_post_meta($comp_id, 'type', true);
            if (! in_array($_POST['opp_id'], $this->types[$type]->get_opponents($comp_id,$_POST['you_id']))) {
                $fail = "nocontest";
            }
        }

        $url = remove_query_arg(["fail","success"]);
        $url = add_query_arg('comp_id',$comp_id,$url);
        if ($fail) {
            $url = add_query_arg('fail', $fail, $url);
        } else {
            $url = add_query_arg('success', 1, $url);
            $result['date'] = $_POST['dateofmatch'];
            $result['you'] = ['person' => $_POST['you_id'], 'scores' => $you];
            $result['opp'] = ['person' => $_POST['opp_id'], 'scores' => $opp];
            $result['timestamp'] = time();

            $you = get_user_by('ID', $_POST['you_id']);
            $opp = get_user_by('ID', $_POST['opp_id']);
            $submitter = get_user_by('ID', $_POST['submitter_id']);
            $name = $submitter->get('first_name') . ' ' . $submitter->get('last_name');
            $subject = "Result recorded by " . $name;
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            foreach ([$you,$opp] as $recipient) {
                if ($submitter->ID !== $recipient->ID) {
                    $msg = '<!DOCTYPE html PUBLIC “-//W3C//DTD XHTML 1.0 Transitional//EN” "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
                    $msg .= '<html xmlns=“https://www.w3.org/1999/xhtml”>';
                    $msg .= '<body>';
                    $msg .= '<p>' . $recipient->get('first_name') . ',</p>';
                    $msg .= '<p>' . $name . ' submitted a result for a match in ' . get_the_title($comp_id) . ' played on the ' . $result['date'] .'.</p>';
                    $msg .= '<table>';
                    $msg .= '<tr><td>' . $you->get('first_name') . ' ' . $you->get('last_name') . '</td>';
                    foreach ($result['you']['scores'] as $s) {
                        $msg .= '<td>' . $s . '</td>';
                    }
                    $msg .= '</tr>';
                    $msg .= '<tr><td>' . $opp->get('first_name') . ' ' . $opp->get('last_name') . '</td>';
                    foreach ($result['opp']['scores'] as $s) {
                        $msg .= '<td>' . $s . '</td>';
                    }
                    $msg .= '</tr>';
                    $msg .= '</table>';
                    $msg .= '</body>';
                    $msg .= '</html>';
                    wp_mail($recipient->get('user_email'), $subject, $msg, $headers);
                }
            }
            add_post_meta($comp_id, 'result', $result);
        }
        wp_safe_redirect( $url . '#submit-' . $comp_id);
        exit();
    }

    /**
     *  Return formatted display of results
     */
    public function show_results($atts) {
        if (array_key_exists('id', $atts)) {
            $comp_id = $atts['id'];
            if (get_post_type($comp_id) !== 'ss_competition') {
                return "The id specified is not of a competition";
            }
        } else {
            return 'Competion not specified in call to short code.';
        }
        $type = get_post_meta($comp_id, 'type', true);
        return $this->types[$type]->get_results($comp_id);
    } 

}
