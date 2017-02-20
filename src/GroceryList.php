<?php

namespace SteveSteele\Groceries;

abstract class GroceryList
{

    // Declare properties
    public $userId;
    public $db;
    public $idForGuestUse = 1;
    public $isGuest = false;


    /**
     * Construct method
     *
     * @param integer $userId    WP user id
     * @param object  $db        Probably $wpdb, but open to new things
     *
     * @return void
     */
    public function __construct($userId = null, $db = null)
    {
        $this->setUser($userId);
        $this->setDb($db);
    }


    /**
     * Set user
     *
     * @param integer $userId    WP user id
     *
     * @return void
     */
    protected function setUser($userId = null)
    {
        if (! isset($userId)) {
            $userId = get_current_user_id();
            if (! $userId) {
                $userId = $this->idForGuestUse;
                $this->isGuest = true;
            }
        }

        $this->userId = $userId;
    }


    /**
     * Set DB
     *
     * @param object $db    Probably $wpdb, but open to new things
     *
     * @return void
     */
    protected function setDb($db = null)
    {
        if (! isset($db)) {
            // assume wpdb
            global $wpdb;
            $db = $wpdb;
        }

        $this->db = $db;
    }


    /**
     * Save current user grocery list to the DB
     * @param  array $groceries    Grocery list items
     * @param  int   $storeId      Store identifier
     */
    abstract protected function setList($groceries, $storeId = null);


    /**
     * Retrieve grocery list from the DB
     * Sort items by aisle if store specified
     * @param  int   $storeId     Store identifier
     *
     * @return array              Grocery list items
     */
    abstract protected function getList($storeId = null);
}
