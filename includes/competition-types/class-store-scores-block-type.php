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

    /** 
     * Get human desciption of the format
     */
    public function get_description() {
        return "
Games should be played as 18pt (1 and 3-back variation) games with a 2.5 hour time limit. Games will be handicap using a base of 9.

Draws are not permitted, and the rules for resolving the winner when the scores are level after the time turns should be followed.

The best record will be determined by the following criteria, in this order:

            Number of games won
            Who-beat-whom
            Net points accrued from all games
            Total points accrued from all games
            Who reported the result of their last game first
";
    }
    
    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        return "<p>Results will appear here</p>";
    }

}
