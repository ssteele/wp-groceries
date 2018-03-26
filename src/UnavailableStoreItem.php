<?php

namespace SteveSteele\Groceries;

use SteveSteele\TypeSanity\UserInput;

class UnavailableStoreItem extends BaseSetter
{
    // Declare properties
    public $userId;
    public $db;
    public $termId;
    public $storeIds = [];
    public $translator;


    public function __construct($userId = null, $db = null)
    {
        parent::__construct($userId, $db);
        $this->translator = new UserInput();
    }

    /**
     * Set item ID
     * @param integer $termId    Taxonomy term ID
     * @return void
     */
    private function setItemId($termId = null)
    {
        $this->termId = $this->translator->sanitize($termId, 'i');
    }


    /**
     * Set store IDs
     * @param array $storeIds    Store IDs where item is unavailable
     * @return void
     */
    private function setStoreIds($storeIds = [])
    {
        $this->storeIds = $this->translator->sanitize($storeIds, 'i');
    }


    /**
     * Persist store IDs where item is unavailable
     * ...insert DB row if flagged unavailable
     * ...remove DB row if formerly flagged unavailable (but checkbox no longer toggled)
     * ...not interested if posted and already persisted - leave those records untouched
     * @return void
     */
    private function persist()
    {
        $existingEntities = $this->getUnavailableEntitiesByItemId($this->termId);
        $existingStoreIds = $this->pluckStoreIds($existingEntities);

        $postedNotPersisted = array_diff($this->storeIds, $existingStoreIds); // need to save these to DB
        foreach ($postedNotPersisted as $storeId) {
            $this->db->insert(
                $this->db->prefix . 'term_taxonomy_unavailable',
                [
                    'user_id'  => $this->userId,
                    'term_id'  => $this->termId,
                    'store_id' => $storeId,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                ]
            );
        }

        $persistedNotPosted = array_diff($existingStoreIds, $this->storeIds); // need to delete these from DB
        foreach ($persistedNotPosted as $storeId) {
            $this->db->delete(
                $this->db->prefix . 'term_taxonomy_unavailable',
                [
                    'user_id' => $this->userId,
                    'term_id' => $this->termId,
                    'store_id' => $storeId,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                ]
            );
        }
    }

    /**
     * Save store IDs where item is unavailable
     * @param  integer  $termId     Taxonomy term ID
     * @param  array    $storeIds   Store IDs where item is unavailable
     * @return void
     */
    public function save($termId, $storeIds = [])
    {
        $this->setItemId($termId);
        $this->setStoreIds($storeIds);
        $this->persist();
    }


    /**
     * Fetch by item ID unavailable entities from the DB
     * @param  string $termId    Taxonomy term ID
     * @return array             Filled with unavailable taxonomy DB objects
     */
    private function getUnavailableEntitiesByItemId($termId)
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM wp_term_taxonomy_unavailable WHERE user_id = %d AND term_id = %d", $this->userId, $termId)
        );
    }


    /**
     * Fetch by store ID unavailable entities from the DB
     * @param  string $storeId    Store ID
     * @return array              Filled with unavailable taxonomy DB objects
     */
    private function getUnavailableEntitiesByStoreId($storeId)
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM wp_term_taxonomy_unavailable WHERE user_id = %d AND store_id = %d", $this->userId, $storeId)
        );
    }


    /**
     * Pluck store IDs from unavailable entities
     * @param  array $unavailableEntities    Unavailable taxonomy DB objects
     * @return array                         Store IDs where item is unavailable
     */
    public function pluckStoreIds($unavailableEntities)
    {
        $storeIds = [];
        if (! empty($unavailableEntities)) {
            foreach ($unavailableEntities as $entity) {
                if (isset($entity->store_id)) {
                    $storeIds[] = $entity->store_id;
                }
            }
        }
        return $storeIds;
    }


    /**
     * Pluck term IDs from unavailable entities
     * @param  array $unavailableEntities    Unavailable taxonomy DB objects
     * @return array                         Unavailable item IDs
     */
    public function pluckItemIds($unavailableEntities)
    {
        $termIds = [];
        if (! empty($unavailableEntities)) {
            foreach ($unavailableEntities as $entity) {
                if (isset($entity->term_id)) {
                    $termIds[] = $entity->term_id;
                }
            }
        }
        return $termIds;
    }


    /**
     * Get store IDs where item is unavailable
     * @param  string $termId    Taxonomy term ID
     * @return array             Store IDs where item is unavailable
     */
    public function getStoreIds($termId)
    {
        $unavailableEntities = $this->getUnavailableEntitiesByItemId($termId);
        return $this->pluckStoreIds($unavailableEntities);
    }


    /**
     * Get term IDs unavailable for specified store
     * @param  string $termId    Taxonomy term ID
     * @return array             Store IDs where item is unavailable
     */
    public function getItemIds($storeId)
    {
        $unavailableEntities = $this->getUnavailableEntitiesByStoreId($storeId);
        return $this->pluckItemIds($unavailableEntities);
    }
}
