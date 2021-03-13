<?php

/**
 * Abstract class for a competition type
 */
abstract class Store_Scores_Competition_Type {
    abstract public function get_opponents($comp_id, $player_id);
    abstract public function get_tag();
    abstract public function get_description();
    abstract public function get_results($comp_id);

    protected function generateBlockTable($table, $ranking) {
        $html = '<div id="block_table"><table>';
        $html .= '<tr>';
        $html .= '<th>Name</th>';
        $html .= '<th>#</th>';
        foreach ($ranking as $slot) {
            $competitor_id = $slot[0];
            $competitor = $table[$competitor_id];
            $html .= '<th>' . $competitor['initials'] . '</th>';
        }
        $html .= '<th>W</th>';
        $html .= '<th>NH</th>';
        $html .= '<th>TH</th>';
        $html .= '</tr>';

        foreach ($ranking as $slot) {
            $competitor_id = $slot[0];
            $pos = $slot[1];
            $competitor = $table[$competitor_id];
            $html .= '<tr>';
            $html .= '<td>' . $competitor['name'] . '</td>';
            $html .= '<td>' . $pos . '</td>';
            $scores = $table[$competitor_id]['scores'];
            foreach ($ranking as $slot2) {
                $competitor_id2 = $slot2[0];
                if (isset($scores[$competitor_id2])) {
                    $match = $scores[$competitor_id2];
                    $n = count($match[0]);
                    $score = "";
                    for ($j = 1; $j <= $n; $j++){
                        if (strlen($score) != 0) {
                            $score .= ', ';
                        }
                        $score .= strval($match[0][$j]) . '-' . strval($match[1][$j]);
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
}

foreach (glob(dirname(__FILE__) . "/competition-types/*.php") as $f) {
    include $f;
}

