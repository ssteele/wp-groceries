<?php

namespace SteveSteele\Groceries;

class BaseSetter
{
    // Declare properties
    public $userId;
    public $db;


    /**
     * Construct method
     * @param integer $userId    WP user id
     * @param object  $db        Probably $wpdb, but open to new things
     * @return void
     */
    public function __construct($userId = null, $db = null)
    {
        $this->setUser($userId);
        $this->setDb($db);
    }


    /**
     * Set user
     * @param integer $userId    WP user id
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
     * @param object $db    Probably $wpdb, but open to new things
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
}
