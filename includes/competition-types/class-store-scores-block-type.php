<?php

/**
 * This represents a normal American block
 */
class Store_Scores_Block_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return all not played and not player_id
     */
    public function get_opponents($comp_id, $player_id) {
        return [1];
    }

    /**
     * Get unique short name to display to admin
     */
    public function get_tag() {
        return "American Block";
    }
}
