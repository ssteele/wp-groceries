<?php

class sortList {

    public $master_store_list;

    function __construct($master_store_list) {
        $this->master_store_list = $master_store_list;
    }

    function sort($a, $b) {
        return order_current_list($a, $b, $this->master_store_list);
    }
}


/**
 * Sort current grocery list by aisle (from master list)
 * @return int  0 if $a == $b, -1 if $a < $b or +1 if $a > $b
 */
function order_current_list($a, $b, $master_store_list) {

    $a_key = array_search($a['i'], $master_store_list);
    $b_key = array_search($b['i'], $master_store_list);

    // If element not found in sort array then assume it should appear at the end
    if ($a_key === false) {
        return 1;
    } elseif ($b_key === false) {
        return -1;
    }

    // Both elements found in sort array - determine order
    if ($a_key == $b_key) {
        return 0;
    }

    return ($a_key < $b_key) ? -1 : 1;

}

?>