<?php
/** Definition of Competion custom post type and its metaboxes and post data
 *
 * @package    store_scores
 * @subpackage store_scores/admin
 *
 */

/** 
 * Class holding the competiton functions
 */
class Store_Scores_Competition {

    protected $types = [];

    public function __construct() {
        foreach (get_declared_classes() as $classname) {
            if (in_array('Store_Scores_Competition_Type', class_parents($classname))) {
                $obj = new $classname();
                $tag = $obj->get_tag();
                $this->types [$tag] = $obj;
            }       
        }
    }

    /**
     * This is hooked to 'init' to create the ss_competition post type
     */
    public function register_competition() {
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

    /**
     * This is hooked to add_meta_boxes to ss_competition posts
     */
    public function add_competition_boxes() {
        add_meta_box(
            'competition_type',
            'Competition Type',
            [$this,'competition_type_content'],
            'ss_competition', 'advanced', 'default');

         add_meta_box(
            'competition_bestof',
            'Best of',
            [$this,'competition_bestof_content'],
            'ss_competition', 'advanced', 'default');

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

    /** 
     * Invoked by add_competitition to display selector for competition type
     */
    public function competition_type_content ($post) {
        $pm = get_post_meta($post->ID);
        if (array_key_exists('type',$pm)) {
            $etype = $pm['type'][0];
        } else {
            $etype = Null;
        }
        echo '<label for="type"></label>';
        echo '<select id="type" name="type" size="1">';
        foreach ($this->types as $type => $obj) {
            $selected = ($type === $etype) ? ' selected' : '';
            $name = $type;
            echo '<option' . $selected . ' value="' .$name . '">'. $name . '</option>';
        }
        echo '</select>';
    }

    /**
     * Invoked by add_competitition to display selector for best of
     */
    public function competition_bestof_content ($post) {
        $bo = get_post_meta($post->ID,'bestof',true);
        if (! $bo) $bo = 1;
        echo '<label for="bestof"></label>';
        echo '<select id="bestof" name="bestof" size="1">';
        foreach ([1,3] as $bestof) {
            $selected = ($bestof == $bo) ? ' selected' : '';
            $name = $bestof;
            echo '<option' . $selected . ' value="' .$name . '">'. $name . '</option>';
        }
        echo '</select>';
    }


    /**
     * Invoked by add_competition_boxes to display boxes to input competitor names for a specific ss_competition.
     */
    public function competitor_content( $post, $args ) {
        $x = $args['args'][0];
        $post_name = 'competitor_'.$x;
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

    public function save_competition( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
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

        update_post_meta( $post_id, 'type', $_POST['type']);
        update_post_meta( $post_id, 'competitors', $competitors );
        update_post_meta( $post_id, 'bestof', $_POST['bestof']);
    }

}
