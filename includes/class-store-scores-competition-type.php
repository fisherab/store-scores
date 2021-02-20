<?php

/**
 * Abstract class for a compeition type
 */
abstract class Store_Scores_Competition_Type {

    abstract public function get_opponents($comp_id, $player_id);
    abstract public function get_tag();

}

foreach (glob(dirname(__FILE__) . "/competition-types/*.php") as $f) {
    include $f;
}

foreach (get_declared_classes() as $classname) {
    if (in_array('Store_Scores_Competition_Type', class_parents($classname))) {
        $obj = new $classname();
        $tag = $obj->get_tag();
        write_log ([$tag, $obj]);
    }
}

