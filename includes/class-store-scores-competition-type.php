<?php

/**
 * Abstract class for a competition type
 */
abstract class Store_Scores_Competition_Type {

    abstract public function get_opponents($comp_id, $player_id);

    abstract public function get_tag();

    abstract public function get_description();

    abstract public function get_results($comp_id);

    /**
     * Return the pair of 'you' or 'opp' and the id of the winner 
     */
    public static function getWinner($result) {
        if (array_key_exists('target', $result['you'])) {
            // The result is determined by the last game 
            foreach ([
                'you',
                'opp'
            ] as $p) {
                $pinfo = $result[$p];
                $pid = $pinfo["person"];
                $scores = $pinfo['scores'];
                if ($scores[array_key_last($scores)] == $pinfo['target']) {
                     return [
                        $p,
                        $pid
                    ];
                }
            }
            return [
                null,
                null
            ];
        } else {
            $youresult = $result['you']['scores'];
            $oppresult = $result['opp']['scores'];
            $winner = $youresult[array_key_last($youresult)] > $oppresult[array_key_last($youresult)] ? 'you' : 'opp';
            return [
                $winner,
                $result[$winner]['person']
            ];
        }
    }
}

foreach (glob(dirname(__FILE__) . "/competition-types/*.php") as $f) {
    include $f;
}

