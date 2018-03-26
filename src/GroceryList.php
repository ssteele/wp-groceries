<?php

namespace SteveSteele\Groceries;

abstract class GroceryList extends BaseSetter
{
    // Declare properties
    public $userId;
    public $db;
    public $idForGuestUse = 1;
    public $isGuest = false;


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
     * @return array              Grocery list items
     */
    abstract protected function getList($storeId = null);
}
