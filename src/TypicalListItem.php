<?php

namespace SteveSteele\Groceries;

use SteveSteele\TypeSanity\UserInput;

class TypicalListItem extends BaseSetter
{
    // Declare properties
    public $userId;
    public $db;
    public $termId;
    public $isTypical = 0;
    public $translator;


    public function __construct($userId = null, $db = null)
    {
        parent::__construct($userId, $db);
        $this->translator = new UserInput();
    }

    /**
     * Set term ID
     * @param integer $termId    Taxonomy term ID
     * @return void
     */
    private function setItemId($termId = null)
    {
        $this->termId = $this->translator->sanitize($termId, 'i');
    }


    /**
     * Set is typical
     * @param integer $isTypical    Typical items when starting a grocery list from scratch
     * @return void
     */
    private function setIsTypical($isTypical = 0)
    {
        $this->isTypical = $this->translator->sanitize($isTypical, 'i');
    }


    /**
     * Persist typical grocery list item status
     * @return void
     */
    private function persist()
    {
        $existingEntity = $this->getTypicalEntity($this->termId);

        if (empty($existingEntity)) {
            $this->db->insert(
                $this->db->prefix . 'term_taxonomy_typical',
                [
                    'user_id'    => $this->userId,
                    'term_id'    => $this->termId,
                    'is_typical' => $this->isTypical,
                ],
                [
                    '%d',
                    '%d',
                    '%d',
                ]
            );
        } else {
            $this->db->update(
                $this->db->prefix . 'term_taxonomy_typical',
                [
                    'is_typical' => $this->isTypical,
                ],
                [
                    'user_id' => $this->userId,
                    'term_id' => $this->termId,
                ],
                [
                    '%d',
                ],
                [
                    '%d',
                ]
            );
        }
    }

    /**
     * Save typical grocery list item status
     * @param  integer  $termId       Taxonomy term ID
     * @param  integer  $isTypical    Typical items when starting a grocery list from scratch
     * @return void
     */
    public function save($termId, $isTypical = 0)
    {
        $this->setItemId($termId);
        $this->setIsTypical($isTypical);
        $this->persist();
    }


    /**
     * Fetch typical list item status from the DB
     * @param  string $termId    Taxonomy term ID
     * @return array             Typical taxonomy DB object
     */
    private function getTypicalEntity($termId)
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM wp_term_taxonomy_typical WHERE user_id = %d AND term_id = %d", $this->userId, $termId)
        );
    }


    /**
     * Fetch typical list items from the DB
     * @return array    Typical taxonomy DB objects
     */
    private function getTypicalEntities()
    {
        return $this->db->get_results(
            $this->db->prepare("SELECT * FROM wp_term_taxonomy_typical WHERE user_id = %d AND is_typical = '1'", $this->userId)
        );
    }


    /**
     * Pluck typical list item status from DB entity
     * @param  array $typicalTaxonomyEntities    Typical taxonomy DB objects
     * @return string                            1 if item is typical, 0 otherwise
     */
    public function pluckIsTypical($typicalEntity)
    {
        $isTypical = '0';
        if (! empty($typicalEntity)) {
            $item = reset($typicalEntity);
            $isTypical = $item->is_typical;
        }
        return $isTypical;
    }


    /**
     * Pluck typical list item IDs from an array DB entities
     * @param  array    Typical taxonomy DB objects
     * @return array    Taxonomy IDs
     */
    public function pluckIds($typicalEntities = [])
    {
        $typicalItemIds = [];
        foreach ($typicalEntities as $item) {
            $typicalItemIds[] = $item->term_id;
        }

        return $typicalItemIds;
    }


    /**
     * Get typical list item status
     * @param  string $termId    Taxonomy term ID
     * @return string            1 if item is typical, 0 otherwise
     */
    public function getIsTypical($termId)
    {
        $typicalEntity = $this->getTypicalEntity($termId);
        return $this->pluckIsTypical($typicalEntity);
    }


    /**
     * Get typical list item IDs from the DB
     * @return array    Taxonomy IDs
     */
    public function getIds()
    {
        $typicalEntities = $this->getTypicalEntities();
        return $this->pluckIds($typicalEntities);
    }
}
