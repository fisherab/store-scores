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
        $avoid = [$player_id, 0];
        $competition = get_post_meta($comp_id);
        if (array_key_exists('result', $competition)) {
            $results = $competition['result'];
            foreach ($results as $n => $result) {
                $result = unserialize($result);
                $you = $result['you'];
                $you_id = $you['person'];
                $opp = $result['opp'];
                $opp_id = $opp['person'];
                if ($player_id == $you_id) {
                    $avoid[] = $opp_id;
                } elseif ($player_id == $opp_id) {
                    $avoid[] = $you_id;
                }
            }
        }
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
        $html .= '<p>Normally everyone plays one match against everyone else.</p>';
        $html .= '<p>Once you have played someone you will not be offered the chance to record another result against them.</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $results = get_post_meta($comp_id,'result');
        if (! $results) {
            return "No matches completed yet";
        }
        foreach ($results as $result) {
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
        if ($a[1] === $b[1]) {
            return 0;
        }
        return ($a[1] > $b[1]) ? -1: 1;
    }
}
