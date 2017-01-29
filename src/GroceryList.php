<?php

namespace SteveSteele\Groceries;

abstract class GroceryList
{

    // Declare properties
    public $userId;
    public $idForGuestUse = 1;
    public $isGuest = false;


    /**
     * Construct method
     *
     * @param integer $userId    WP user id
     *
     * @return void
     */
    public function __construct($userId = null)
    {
        $this->setUser($userId);
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

    abstract protected function setList($groceries, $storeId = null);
    abstract protected function getList($storeId = null);
}
