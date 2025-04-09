<?php

/**
 * This represents a Knock Out event which could be part of a draw and process.
 */
class Store_Scores_KO_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return the one person (at most) you can play
     */
    public function get_opponents($comp_id, $player_id) {
        if ($player_id == 0) {
            return get_post_meta($comp_id,'competitors', true);
        }
        $sheet = $this->get_sheet($comp_id);
        $rounds = count($sheet);
        for($i = 0; $i < count($sheet[0]); $i++) {
            $rowspan = 1;
            for ($j = 0; $j < $rounds; $j++) {
                if($i % $rowspan == 0) {
                    if (! isset($sheet[$j][$i/$rowspan])) {
                        if (isset($sheet[$j-1][$i*2/$rowspan]) && isset($sheet[$j-1][$i*2/$rowspan+1])) {
                            $c1 = $sheet[$j-1][$i*2/$rowspan];
                            $c2 = $sheet[$j-1][$i*2/$rowspan+1];
                            if ($c1 == $player_id) return [$c2];
                            if ($c2 == $player_id) return [$c1];
                        }
                    }
                    $rowspan = $rowspan * 2;
                }
            }
        }
        return [];
    }

    private function get_sheet($comp_id) {
        $competitors = get_post_meta($comp_id,'competitors', true);
        $title = explode("byes:", get_the_title($comp_id));
        if ($title[1]) $byeslist = explode(" ",$title[1]);
        else $byeslist = [];
        foreach ($byeslist as $value) $round[intval($value)] = "-";
        $insert = 0;
        foreach ($competitors as $value) {
            while (array_key_exists($insert,$round)) $insert++;
            $round[$insert] = $value;
            $insert++;
        }
        ksort($round);
        $sheet[0] = $round;
        $lastround = $round;
        $round_size = (count($competitors) + count($byeslist))/2;
        $competition = get_post_meta($comp_id);
        while ($round_size > 0) {
            $round = [];
            for ($i = 0; $i < $round_size; $i++) {
                if (isset($lastround[$i*2]) && $lastround[$i*2] == "-") $round[$i] = $lastround[$i*2 + 1];
                elseif (isset($lastround[$i*2+1]) && $lastround[$i*2+1] == "-") $round[$i] = $lastround[$i*2];
                else {
                    if (array_key_exists($i*2, $lastround) && array_key_exists($i*2+1, $lastround)) {
                        if (array_key_exists('result', $competition)) {
                            $results = $competition['result'];
                            foreach ($results as $n => $result) {
                                $result = unserialize($result);
                                $you = $result['you'];
                                $you_id = $you['person'];
                                $you_scores = $you['scores'];
                                $you_score = $you_scores[count($you_scores)];
                                $opp = $result['opp'];
                                $opp_id = $opp['person'];
                                $opp_scores = $opp['scores'];
                                $opp_score = $opp_scores[count($opp_scores)];
                                $youwin = $you_score > $opp_score;
                                if ($lastround[$i*2] == $you_id && $lastround[$i*2+1] == $opp_id) {
                                    if ($youwin) $round[$i] = $you_id;
                                    else $round[$i] = $opp_id;
                                }
                                elseif($lastround[$i*2] == $opp_id && $lastround[$i*2+1] == $you_id) {
                                    if ($youwin) $round[$i] = $you_id;
                                    else $round[$i] = $opp_id;
                                }
                            }
                        }
                    }
                }
            }
            $sheet[] = $round;
            $lastround = $round;
            $round_size = intdiv($round_size, 2);
        }
        return $sheet;
    }

    /**
     * Get unique short name to display to admin
     */
    public function get_tag() {
        return "Knock Out";
    }

    /** 
     * Get human description of the format
     */
    public function get_description() {
        $html = '<div>';
        $html .= '<p>You get no choice of whom to play.</p>';
        $html .= '<p>If you win you will get the chance to play soemone else.</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        $sheet = $this->get_sheet($comp_id);
        $rounds = count($sheet);
        $html = "<div><table border = 1>";
        for($i = 0; $i < count($sheet[0]); $i++) {
            $html .= "<tr>";
            $rowspan = 1;
            for ($j = 0; $j < $rounds; $j++) {
                if($i % $rowspan == 0) {
                    if (isset($sheet[$j][$i/$rowspan])) {
                        $userid = $sheet[$j][$i/$rowspan];
                        $first = get_user_meta($userid,'first_name',true);
                        $last = get_user_meta($userid,'last_name', true);
                        $userid = $first . ' ' . $last;
                        if ($j != 0) $userid = $first . ' ' . $last[0];
                    } else $userid = "&nbsp;";
                    $html .= '<td rowspan="'.$rowspan.'">'.$userid.'</td>';
                    $rowspan = $rowspan * 2;
                }
            }
            $html .= "</tr>";
        }
        $html .= '</table></div>';
        return $html;
    }
}
