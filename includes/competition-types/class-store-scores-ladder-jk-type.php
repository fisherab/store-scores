<?php

/**
 * This represents a normal Ladder competition
 */
class Store_Scores_Ladder_JK_Type extends Store_Scores_Competition_Type {

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
        return "Ladder (JK)";
    }

    /** 
     * Get human desciption of the format
     */
    public function get_description() {
        $html = "<div>";
        $html .= "<p>You may challenge any club member to a game, regardless of whether they are on the ladder already or are yet to play their first game. Games should ideally be arranged and played within a week of a challenge being issued.</p>";
        $html .= "<p>Your initial ladder ranking points are set to 100, and you gain a point for winning a game and drop a point for losing. This means the ladder should remain balanced if some players play more games with other playing less. Your ladder position is determined by ladder ranking points.</p>";
        $html .= "</div>";
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $bestof = get_post_meta($comp_id, 'bestof', true); 
        $rankings = [];
        $results = get_post_meta($comp_id,'result');
        foreach ($results as $result) { 
            $you = $result['you'];
            $you_id = $you['person'];
            $opp = $result['opp'];
            $opp_id = $opp['person'];
            $you_wins = 0;
            $opp_wins = 0;
            for ($i = 1; $i <= $bestof; $i++) {
                if ($you['scores'][$i] > $opp['scores'][$i]) {
                    $you_wins++;
                } else {
                    $opp_wins++;
                }
            }

            if (! isset($rankings[$you_id])) {
                $rankings[$you_id] = [$you_id,100,0,0];
            }
            if (! isset($rankings[$opp_id])) {
                $rankings[$opp_id] = [$opp_id,100,0,0];
            }
            $rankings[$you_id][3]++;
            $rankings[$opp_id][3]++;
            if ($you_wins > $opp_wins) {
                $rankings[$you_id][1]++;
                $rankings[$opp_id][1]--;
                $rankings[$you_id][2]++;
            } else {
                $rankings[$you_id][1]--;
                $rankings[$opp_id][1]++;
                $rankings[$opp_id][2]++;
            } 
        }
        usort($rankings, [$this, 'sort_by_points']);
        $html = "<div><table>";
        $html .= '<tr><td>Name</td><td>Points</td><td>Wins</td><td>Games</td></tr>';
        foreach ($rankings as $ranking) {
            $person_id = $ranking[0];
            $person = get_user_by("ID", $person_id);
            $person_name = $person->get('first_name') . ' ' . $person->get('last_name');
            $html .= '<tr><td>' . $person_name . '</td><td>' . $ranking[1] . '</td><td>' . $ranking[2] . '</td><td>' . $ranking[3] . '</td></tr>';
        }
        $html .= '</table></div>';
        return $html;
    }

    private function sort_by_points($ar, $br) {
        $a = $ar[1];
        $b = $br[1];
        if ($a === $b) {
            return 0;
        }
        return ($a < $b) ? 1:-1;
    }

}
