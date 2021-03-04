<?php

/**
 * This represents a normal Ladder competition
 */
class Store_Scores_Ladder_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return all players other than one one specified
     *
     * @param integer $comp_id id of the competition custom post
     * @param integer $player_id id of the player 
     */
    public function get_opponents($comp_id, $player_id) {
        $competitors = get_post_meta($comp_id,'competitors', true);
        $opponents = array_diff($competitors,[$player_id, 0]);
        return $opponents;
    }

    /**
     * Get unique short name to display to admin
     */
    public function get_tag() {
        return "Ladder";
    }

    /** 
     * Get human desciption of the format
     */
    public function get_description() {
        $html = "<div>";
        $html .= "<p>You may challenge any club member to a game, regardless of whether they are on the ladder already or are yet to play their first game. Games should ideally be arranged and played within a week of a challenge being issued.</p>";
        $html .= "<p>Your initial ladder ranking points are set to 100, and you gain a point for winning a game and drop a point for losing. This means the ladder should remain balanced if some players play more games with other playing less. Your ladder position is determined by ladder ranking points. If a tie break is required then games won and percentage of games won are considered.</p>";
        $html .= "</div>";
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $needed = (intval(get_post_meta($comp_id, 'bestof', true))+1)/2; 
        $rankings = [];
        $results = get_post_meta($comp_id)['result'];
        foreach ($results as $n => $result) { 
            $result = unserialize($result);
            $you = $result['you'];
            $you_id = $you['person'];
            $opp = $result['opp'];
            $opp_id = $opp['person'];
            if (! isset($rankings[$you_id])) {
                $rankings[$you_id] = 100;
            }
            if (! isset($rankings[$opp_id])) {
                $rankings[$opp_id] = 100;
            }
            if (intval($result['wins']) >= $needed) {
                $rankings[$you_id]++;
                $rankings[$opp_id]--;
            } else {
                $rankings[$you_id]--;
                $rankings[$opp_id]++;
            } 
        }
        arsort($rankings, SORT_NUMERIC);
        $html = "<div><table>";
        foreach ($rankings as $person_id => $ranking) {
            $person = get_user_by("ID", $person_id);
            $person_name = $person->get('first_name') . ' ' . $person->get('last_name');
            $html .= '<tr><td>' . $person_name . '<td><td>' . $ranking . '</td></tr>';
        }
        $html .= '</table></div>';
        return $html;
    }

}
