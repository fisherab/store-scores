<?php

/**
 * Abstract class for a competition type
 */
abstract class Store_Scores_Competition_Type {
    abstract public function get_opponents($comp_id, $player_id);
    abstract public function get_tag();
    abstract public function get_description();
    abstract public function get_results($comp_id);
}

foreach (glob(dirname(__FILE__) . "/competition-types/*.php") as $f) {
    include $f;
}

