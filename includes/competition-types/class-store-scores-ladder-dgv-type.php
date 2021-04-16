<?php

/**
 * This represents a normal Ladder competition
 */
class Store_Scores_Ladder_DGV_Type extends Store_Scores_Competition_Type {

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
        return "Ladder (DGV)";
    }

    /** 
     * Get human desciption of the format
     */
    public function get_description() {
        $html = "<div>";
        $html .= "<p>A player on the ladder may challenge any player above them. A player not yet on the ladder may challenge anyone who is on the ladder or anyone who is not.</p>";
        $html .= "<p>Either player can enter the result. The ladder will then be updated automatically. If the challenger wins he takes the ladder position of the loser’s position and all below move down one place. If a game is entered between two players not yet on the ladder they are added at the bottom with the winner first.</p>";
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $ladder = $this->build_ladder($comp_id);

        $html = "<div><table>";
        $html .= '<tr><th>Name</th><th>Wins</th><th>Games</th></tr>';
        for ($ladder->rewind(); $ladder->valid(); $ladder->next()) {
            $rung = $ladder->current();
            $person = get_user_by("ID", $rung[0]);
            $person_name = $person->get('first_name') . ' ' . $person->get('last_name');
            $wins = $rung[1];
            $games = $rung[2];
            $html .= '<tr><td>' . $person_name . '</td><td>' . $wins . '</td><td>' . $games . '</td></tr>';
        }
        $html .= '</table></div>';
        return $html;

    }

    private function sort_by_timestamp($ra, $rb) {
        $a = $ra['timestamp'];
        $b = $rb['timestamp'];
        if ($a === $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }

    private function build_ladder($comp_id) {
        $bestof = get_post_meta($comp_id, 'bestof', true);
        $ladder = new SplDoublyLinkedList;

        $results = get_post_meta($comp_id, 'result');
        usort($results, [$this, 'sort_by_timestamp']);


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

            //            write_log("Result " . $you_id . ' ' . $you_wins . ' ' . $opp_id . ' ' . $opp_wins);

            $you_pos = null;
            $opp_pos = null;
            for ($ladder->rewind(); $ladder->valid(); $ladder->next()) {
                if ($you_id == $ladder->current()[0]) {
                    $you_pos = $ladder->key();
                    $you_entry = $ladder->current();
                } else if ($opp_id == $ladder->current()[0]) {
                    $opp_pos = $ladder->key();
                    $opp_entry = $ladder->current();
                } 
            }
            if ($you_pos === null) {
                $you_entry = [$you_id,0,0];
                $ladder->push($you_entry);
                $you_pos = $ladder->count() - 1;
            }
            if ($opp_pos === null) {
                $opp_entry = [$opp_id,0,0];
                $ladder->push($opp_entry);
                $opp_pos = $ladder->count() - 1;
            }
            $you_entry[2]++;
            $opp_entry[2]++;
            if ($you_wins > $opp_wins) {
                $you_entry[1]++;
                if ($you_pos > $opp_pos) {
                    //                   write_log("You " . $you_id . " won and are moving up");
                    $ladder->offsetUnset($you_pos);
                    $ladder->add($opp_pos, $you_entry);
                    $ladder->offsetSet($opp_pos+1, $opp_entry);
                } else {
                    //                   write_log("You " . $you_id . " kept the place");
                    $ladder->offsetSet($you_pos, $you_entry);
                    $ladder->offsetSet($opp_pos, $opp_entry);
                }
            } else {
                $opp_entry[1]++;
                if ($you_pos < $opp_pos) {
                    //                   write_log("Opp " . $opp_id . " won and are moving up");
                    $ladder->offsetUnset($opp_pos);
                    $ladder->add($you_pos, $opp_entry);
                    $ladder->offsetSet($you_pos+1, $you_entry);
                } else {
                    //                   write_log("Opp " . $opp_id . " kept the place");
                    $ladder->offsetSet($opp_pos, $opp_entry);
                    $ladder->offsetSet($you_pos, $you_entry);
                }

            }

        }
        return $ladder;
    }

}
