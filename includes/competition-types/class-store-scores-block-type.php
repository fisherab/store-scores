<?php

/**
 * This represents a normal American block
 */
class Store_Scores_Block_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return all not played and not player_id
     */
    public function get_opponents($comp_id, $player_id) {
        $competitors = get_post_meta($comp_id, 'competitors', true);
        $avoid = [
            $player_id,
            0
        ];
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
        $opponents = array_diff($competitors, $avoid);
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
     * Return formatted results $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
        dumpToFile('Start at ' . date('Y-m-d h:i:s'));
        $results = get_post_meta($comp_id, 'result');
        if (! $results) {
            return "No matches completed yet";
        }
        $records = []; // Records are indexed by competitor. Each holds a list
                        // of results for that competitor
        foreach ($results as $result) {
            $you_id = $result['you']['person'];
            $opp_id = $result['opp']['person'];
            $records[$you_id][] = $result;
            $records[$opp_id][] = $result;
            dumpToFile($result);
        }

        $competitors = array_diff(get_post_meta($comp_id, 'competitors', true), [
            0
        ]);
        foreach ($competitors as $competitor) {
            $p = get_user_by("ID", $competitor);
            $name = $p->get('first_name') . " " . $p->get('last_name');
            $initials = substr($p->get('first_name'), 0, 1) . substr($p->get('last_name'), 0, 1);
            $table[$competitor] = [
                'name' => $name,
                'initials' => $initials,
                'wins' => 0,
                'scores' => [],
                'net_hoops' => 0,
                'total_hoops' => 0
            ];
        }

        foreach ($records as $competitor_id => $record) {
            dumpToFile([
                "competitor_id, record",
                $competitor_id,
                $record
            ]);
            $wins = 0;
            $net_hoops = 0;
            $total_hoops = 0;
            $scores = [];
            $targets = [];
            foreach ($record as $result) {
                $winner = Store_Scores_Competition_Type::getWinner($result);
                dumpToFile([
                    "Winner",
                    $winner
                ]);
                $you = $result['you'];
                $you_id = $you['person'];
                $opp = $result['opp'];
                $opp_id = $opp['person'];
                if ($competitor_id == $you_id) {
                    $first = $you;
                    $second = $opp;
                } else {
                    $first = $opp;
                    $second = $you;
                }
                $ord_games = [
                    $first['scores'],
                    $second['scores']
                ];
                $targetscores = array_key_exists('target', $first);

                $ord_targets = $targetscores ? [
                    $first['target'],
                    $second['target']
                ] : [
                    null,
                    null
                ];

                foreach ($ord_games[0] as $m => $hoops) {
                    $total_hoops += $hoops;
                    $net_hoops += ($hoops - $ord_games[1][$m]);
                }
                if ($winner[1] == $competitor_id) {
                    $wins ++;
                }
                if ($competitor_id == $you_id) {
                    $scores[$opp_id] = $ord_games;
                    $targets[$opp_id] = $ord_targets;
                } else {
                    $scores[$you_id] = $ord_games;
                    $targets[$you_id] = $ord_targets;
                }
            }
            dumpToFile([
                "competitor_id,scores, targets",
                $competitor_id,
                $scores,
                $targets
            ]);

            $table[$competitor_id]['scores'] = $scores;
            $table[$competitor_id]['targets'] = $targets;
            $table[$competitor_id]['wins'] = $wins;
            $table[$competitor_id]['net_hoops'] = $net_hoops;
            $table[$competitor_id]['total_hoops'] = $total_hoops;
        }

        foreach ($table as $competitor_id => $entry) {
            $ranking_wins[] = [
                $competitor_id,
                $table[$competitor_id]['wins']
            ];
        }

        uasort($ranking_wins, [
            $this,
            'by_wins'
        ]);

        $modpos = 0;
        $oldwins = - 1;
        $pos = 1;
        $ranking = []; // This will hold an entry for each row of the result
                        // table. The ennty holds the competitor id and the
                        // position
        foreach ($ranking_wins as $rw) {
            $wins = $rw[1];
            if ($wins != $oldwins) {
                $modpos = $pos;
                $oldwins = $wins;
            }
            $ranking[] = [
                $rw[0],
                $modpos
            ];
            $pos ++;
        }

        $html = '<div id="block_table"><table>';
        $html .= '<tr>';
        $html .= '<th>Name</th>';
        $html .= '<th>#</th>';

        foreach ($ranking as $slot) {
            $competitor_id = $slot[0];
            $competitor = $table[$competitor_id];
            dumpToFile($competitor);
            $html .= '<th>' . $competitor['initials'] . '</th>';
        }
        $html .= '<th>W</th>';
        $html .= '<th>NH</th>';
        $html .= '<th>TH</th>';
        $html .= '</tr>';

        dumpToFile([
            'ranking',
            $ranking
        ]);
        // Compute score display
        foreach ($ranking as $slot) {
            $competitor_id = $slot[0];
            $pos = $slot[1];
            $competitor = $table[$competitor_id];
            $html .= '<tr>';
            $html .= '<td>' . $competitor['name'] . '</td>';
            $html .= '<td>' . $pos . '</td>';
            $scores = $table[$competitor_id]['scores'];
            $targets = $table[$competitor_id]['targets'];
            dumpToFile([
                "comp id, scores, targets",
                $competitor_id,
                $scores,
                "  ",
                $targets
            ]);
            foreach ($ranking as $slot2) {
                $competitor_id2 = $slot2[0];
                if (isset($scores[$competitor_id2])) {
                    $match = $scores[$competitor_id2];
                    $targetpair = $targets[$competitor_id2];
                    dumpToFile([
                        $match,
                        $targetpair
                    ]);
                    $n = count($match[0]);
                    $score = "";
                    for ($j = 1; $j <= $n; $j ++) {
                        if (strlen($score) != 0) {
                            $score .= ', ';
                        }
                        if ($targetpair[0]) {
                            $score .= strval($match[0][$j]) . '/' . $targetpair[0] . '-' . strval($match[1][$j]) . '/' . $targetpair[1];
                        } else {
                            $score .= strval($match[0][$j]) . '-' . strval($match[1][$j]);
                        }
                    }
                } else if ($competitor_id == $competitor_id2) {
                    $score = "X";
                } else {
                    $score = '';
                }
                $html .= '<td>' . $score . '</td>';
            }
            $html .= '<td>' . $competitor['wins'] . '</td>';
            $html .= '<td>' . $competitor['net_hoops'] . '</td>';
            $html .= '<td>' . $competitor['total_hoops'] . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table></div>';
        return $html;
    }

    private function by_wins($a, $b) {
        if ($a[1] === $b[1]) {
            return 0;
        }
        return ($a[1] > $b[1]) ? - 1 : 1;
    }
}
