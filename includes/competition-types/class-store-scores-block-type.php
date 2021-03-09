<?php

/**
 * This represents a normal American block
 */
class Store_Scores_Block_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return all not played and not player_id
     */
    public function get_opponents($comp_id, $player_id) {
        $competitors = get_post_meta($comp_id,'competitors', true);
        $results = get_post_meta($comp_id)['result'];
        $avoid = [$player_id, 0];
        foreach ($results as $n => $result) {
            $result = unserialize($result);
            $you = $result['you'];
            $you_id = $you['person'];
            $opp = $result['opp'];
            $opp_id = $opp['person'];
            write_log([$you_id, $opp_id]);
            if ($player_id == $you_id) {
                $avoid[] = $opp_id;
            } elseif ($player_id == $opp_id) {
                $avoid[] = $you_id;
            }
        }
        write_log($avoid);
        $opponents = array_diff($competitors,$avoid);
        return $opponents;
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
        $html = '<div>';
        $html .= '<p>Games should be played as 18pt (1 and 3-back variation) games with a 2.5 hour time limit. Games will be handicap using a base of 9.<p>';
        $html .= '<p>Draws are not permitted, and the rules for resolving the winner when the scores are level after the time turns should be followed.</p>';
        $html .= '<p>The best record will be determined by the following criteria, in this order:</p>';
        $html .= '<ol>';
        $html .= '<li>Number of games won</li>';
        $html .= '<li>Who-beat-whom</li>';
        $html .= '<li>Net points accrued from all games</li>';
        $html .= '<li>Total points accrued from all games</li>';
        $html .= '<li>Who reported the result of their last game first</li>';
        $html .= '</ol>';
        $html .= '</div>';
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $results = get_post_meta($comp_id)['result'];
        foreach ($results as $n => $result) {
            $result = unserialize($result);
            $you_id = $result['you']['person'];
            $opp_id = $result['opp']['person'];
            $records[$you_id][] = $result;
            $records[$opp_id][] = $result;
        }

        $competitors = array_diff(get_post_meta($comp_id,'competitors', true),[0]);
        foreach ($competitors as $competitor){
            $p = get_user_by("ID",$competitor);
            $name = $p->get('first_name') . " " . $p->get('last_name');
            $initials = substr( $p->get('first_name'),0,1) .substr( $p->get('last_name'),0,1);
            $table[$competitor] = ['name' => $name, 'initials' => $initials, 'wins' => 0, 'scores' => [], 'net_hoops' => 0, 'total_hoops' => 0 ];
        }

        foreach ($records as $competitor => $record) {
            $wins = 0;
            $games = [];
            $net_hoops = 0;
            $total_hoops = 0;
            foreach ($record as $result) {
                $you =  $result['you'];
                $you_id = $you['person'];
                $opp = $result['opp'];
                $opp_id = $opp['person'];
                if ($competitor == $you_id) {
                    $ord_games = [$you['scores'], $opp['scores']];
                } else {
                    $ord_games = [$opp['scores'], $you['scores']];
                }
                $games_in_match = 0;
                foreach ($ord_games[0] as $m => $hoops) {
                    $total_hoops += $hoops;
                    $net_hoops += ($hoops - $ord_games[1][$m]);
                    if ($hoops > $ord_games[1][$m]) {
                        $games_in_match ++;
                    } else {
                        $games_in_match --;
                    }
                }
                if ($games_in_match > 0) {
                    $wins ++;
                }
                if ($competitor == $you_id) {
                    $games[$opp_id] = $ord_games;
                }else{
                    $games[$you_id] = $ord_games;
                }

            }
            $table[$competitor]['scores'] = $games;
            $table[$competitor]['wins'] = $wins;
            $table[$competitor]['net_hoops'] = $net_hoops;
            $table[$competitor]['total_hoops'] = $total_hoops;
        }

        foreach ($table as $competitor => $entry) {
            $ranking_wins[] = [$competitor,  $table[$competitor]['wins'] ];
        }

        uasort ($ranking_wins, [$this, 'by_wins']);

        // TODO add more detailed ranking

        $modpos = 0;
        $oldwins = -1;
        $pos = 1;
        foreach ($ranking_wins as $rw) {
            $wins = $rw[1];
            if ($wins != $oldwins) {
                $modpos = $pos;
                $oldwins = $wins;
            }
            $ranking[] = [$rw[0],$modpos];
            $pos++;
        }
        return $this->generateBlockTable($table, $ranking);
    }

    private function by_wins($a, $b) {
        if ($a[1] == $b[1]) {
            return 0;
        }
        return ($a[1] <  $b[1]) ? 1: 1;
    }
}
