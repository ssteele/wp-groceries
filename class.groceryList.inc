<?php

abstract class groceryList {


    // Declare properties
    public $user_id;


    /**
     * Construct method
     * @param integer $user_id    WP user id
     */
    function __construct($user_id=null) {

        if (!isset($user_id)) {
            $user_id = get_current_user_id();
        }

        $this->user_id = $user_id;

    }

    abstract protected function set_list($groceries, $store_id=null);
    abstract protected function get_list($store_id=null);

}

?>