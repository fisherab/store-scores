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
        global $post;

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

        $managers = get_post_meta($post->ID,'managers',true);
        if ($managers) {
            $count = count($managers);
        } else {
            $count = 0;
        }

        $max_managers = get_option('store_scores_options')['managers_increment'] + $count;
        for ($x = 0; $x < $max_managers; $x++) {
            add_meta_box( 
                'manager_box_' . $x,
                __( 'Manager ' . $x),
                array($this, 'manager_content'),
                'ss_competition',
                'advanced',
                'default',
                [$x]
            );
        }

        $competitors = get_post_meta($post->ID,'competitors',true);
        if ($competitors) {
            $count = count($competitors);
        } else {
            $count = 0;
        }

        $max_competitors = get_option('store_scores_options')['competitors_increment'] + $count;
        for ($x = 0; $x < $max_competitors; $x++) {
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
        $competitors = get_post_meta($post->ID,'competitors',true);
        if ($competitors && array_key_exists($x, $competitors)) {
            $competitor = $competitors[$x];
        } else {
            $competitor = 0;
        }
        echo '<label for="' . $post_name . '"></label>';
        echo '<select id="' . $post_name . '" name="' . $post_name . '" size="1">';
        echo '<option selected value="0"></option>';
        foreach (get_users('orderby=meta_value&meta_key=last_name') as $user) {
            $selected = ($user->ID == $competitor) ? ' selected' : '';
            $name = $user->get('first_name') . ' ' . $user->get('last_name') . esc_html(' <') . $user->get('user_email') . esc_html('>'); 
            echo '<option' . $selected . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        echo '</select>';
    }

    /**
     * Invoked by add_competition_boxes to display boxes to input manager names for a specific ss_competition.
     */
    public function manager_content( $post, $args ) {
        $x = $args['args'][0];
        $post_name = 'manager_'.$x;
        $managers = get_post_meta($post->ID,'managers',true);
        if ($managers && array_key_exists($x, $managers)) {
            $manager = $managers[$x];
        } else {
            $manager = 0;
        }
        echo '<label for="' . $post_name . '"></label>';
        echo '<select id="' . $post_name . '" name="' . $post_name . '" size="1">';
        echo '<option selected value="0"></option>';
        foreach (get_users('orderby=meta_value&meta_key=last_name') as $user) {
            $selected = ($user->ID == $manager) ? ' selected' : '';
            $name = $user->get('first_name') . ' ' . $user->get('last_name') . esc_html(' <') . $user->get('user_email') . esc_html('>'); 
            echo '<option' . $selected . ' value="' .$user->ID. '">'. $name . '</option>';
        }
        echo '</select>';
    }

 public function save_competition( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

        if ( ! isset ($_POST['post_type']) || 'ss_competition' != $_POST['post_type'] ) {
            return;
        }

        if ( 'post' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) )
                return;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) )
                return;
        }
        for ($x = 0; ; $x++) {
            $key = 'competitor_'.$x;
            if (! array_key_exists($key, $_POST)) break;
            $competitors[] = $_POST[$key];
        }
        for ($x = 0; ; $x++) {
            $key = 'manager_'.$x;
            if (! array_key_exists($key, $_POST)) break;
            $managers[] = $_POST[$key];
        }

        update_post_meta( $post_id, 'type', $_POST['type']);
        update_post_meta( $post_id, 'bestof', $_POST['bestof']);
        update_post_meta( $post_id, 'competitors', array_values(array_diff(array_unique($competitors),[0])));
        update_post_meta( $post_id, 'managers', array_values(array_diff(array_unique($managers),[0])));
    }

}
