<?php

/**
 * This represents a normal Ladder competition
 */
class Store_Scores_Egyptian_Type extends Store_Scores_Competition_Type {

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
        return "Egyptian";
    }

    /** 
     * Get human desciption of the format
     */
    public function get_description() {
        $html = "<div>";
        $html .= "<p>Each player is initially assigned 100 ranking points. If a player beats a player of the same ranking then four points are transferred from the loser to the winner. However beating a higher ranked player results in more points being transferred and vice versa. Winning or losing a game may change your handicap but this change has no impact on the ranking points</p>";
        $html .= "<p>You may challenge any club member to a game, regardless of whether they are on the ladder already or are yet to play their first game. Failure to accept the challenge without good reason and to play the game within two weeks results in a maximum score being recorded for the challenger and 0 for the person who failed to play. Such results do not go on handicap cards as regular results would.</p>";

        $html .= "<p>";
        $html .= "You must play a minimum number of games to win.";
        $html .= "</p>";
                
        $html .= "<p>";
        $html .= "A player's position is determined by ranking points, then wins, then net wins (#wins – #losses). ";
        $html .= "</p>";

        $html .= "<p>";
        $html .= "The number of points transferred is given in the table below.";
        $html .= "<table> ";
        $html .= "<tr><th>Difference in ranking points before the game</th><th>If player with higher ranking points wins</th><th>If player with lower ranking points wins</th></tr> ";
        $html .= "<tr><td>0-7</td><td>4</td><td>4</td></tr> ";
        $html .= "<tr><td>8-11</td><td>3</td><td>5</td></tr> ";
        $html .= "<tr><td>12-15</td><td>2</td><td>6</td></tr> ";
        $html .= "<tr><td>16+</td><td>1</td><td>7</td></tr> ";
        $html .= "</table> ";
        $html .= "</p>";

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
        store_scores_log("Count of results: " . count($results));
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
            $diff = $rankings[$you_id][1] - $rankings[$opp_id][1];
            $highwins = ($you_wins > $opp_wins && $diff > 0) || ($you_wins < $opp_wins && $diff < 0);
            $adiff = abs($diff);    
            $transfer = 4;
            if ($adiff >= 8 and $adiff <= 11) $transfer = $highwins? 3:5;
            if ($adiff >= 12 and $adiff <= 15) $transfer = $highwins? 2:6;
            if ($adiff >= 16) $transfer = $highwins? 1:7;
            #store_scores_log($you['person'] . " " . $you[scores][1] . " " . $rankings[$you_id][1]);
            #store_scores_log($opp['person'] . " " . $opp[scores][1] . " " . $rankings[$opp_id][1]);
            #store_scores_log("Diff: " . $diff . " Highwins: " . $highwins);


            $rankings[$you_id][3]++;
            $rankings[$opp_id][3]++;
            if ($you_wins > $opp_wins) {
                $rankings[$you_id][1]+=$transfer;
                $rankings[$opp_id][1]-=$transfer;
                $rankings[$you_id][2]++;
            } else {
                $rankings[$you_id][1]-=$transfer;
                $rankings[$opp_id][1]+=$transfer;
                $rankings[$opp_id][2]++;
            } 
            #store_scores_log("$you_id " . $rankings[$you_id][1] . " " . $rankings[$you_id][2] . " " . $rankings[$you_id][3]);
            #store_scores_log("$opp_id " . $rankings[$opp_id][1] . " " . $rankings[$opp_id][2] . " " . $rankings[$opp_id][3]);
        }
        #$rankings[1][1] = 42;
        #$rankings[2][1] = 42;
        #$rankings[3][1] = 42;
        #$rankings[4][1] = 42;
        #$rankings[1][2] = 2;
        #$rankings[2][2] = 2;
        #$rankings[3][2] = 2;
        #$rankings[4][2] = 2; 


        usort($rankings, [$this, 'sort_by_points_wins_and_netwins']);
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

    private function sort_by_points_wins_and_netwins($ar, $br) {
        $a = $ar[1];
        $b = $br[1];
        if ($a === $b) {
            $aw = $ar[2];
            $bw = $br[2];
            if ($aw === $bw) {
                return 2*($bw-$aw)-($br[3]-$ar[3]);
            }
            return ($aw < $bw) ? 1:-1;
        }
        return ($a < $b) ? 1:-1;
    }

}
