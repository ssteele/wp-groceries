<?php

namespace SteveSteele\Groceries;

class TypicalListItems
{

    // Declare properties
    public $db;
    public $termId;
    public $isTypical = 0;


    /**
     * Construct method
     * @param object  $db    Probably $wpdb, but open to new things
     */
    public function __construct($db = null)
    {
        $this->setDb($db);
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
     * Set term id
     *
     * @param integer $termId    Taxonomy term ID
     *
     * @return void
     */
    private function setTermId($termId)
    {
        $this->termId = sanitize_input($termId, 'i');
    }


    /**
     * Set is typical
     *
     * @param integer $isTypical    Typical items when starting a grocery list from scratch
     *
     * @return void
     */
    private function setIsTypical($isTypical = 0)
    {
        $this->isTypical = sanitize_input($isTypical, 'i');
    }


    /**
     * Persist typical grocery list item status
     *
     * @return void
     */
    private function persistTypicalListItem()
    {
        $existingRecord = $this->getTypicalListItem($this->termId);

        if (empty($existingRecord)) {
            $this->db->insert(
                $this->db->prefix . 'term_taxonomy_extended',
                [
                    'term_id'           => $this->termId,
                    'typical_list_item' => $this->isTypical,
                ],
                [
                    '%d',
                    '%d',
                ]
            );
        } else {
            $this->db->update(
                $this->db->prefix . 'term_taxonomy_extended',
                [
                    'typical_list_item' => $this->isTypical,
                ],
                [
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
     * Save typical grocery list item status (wrapper)
     *
     * @param  integer  $termId       Taxonomy term ID
     * @param  integer  $isTypical    Typical items when starting a grocery list from scratch
     *
     * @return void
     */
    public function saveTypicalListItem($termId, $isTypical = 0)
    {
        $this->setTermId($termId);
        $this->setIsTypical($isTypical);
        $this->persistTypicalListItem();
    }


    /**
     * Fetch typical list item status from the DB
     *
     * @param  string $termId    Taxonomy term ID
     *
     * @return array              Filled with extended taxonomy objects
     */
    private function getTypicalListItem($termId)
    {
        return $this->db->get_results("SELECT * FROM wp_term_taxonomy_extended WHERE term_id = $termId");
    }


    /**
     * Get typical list item status (wrapper)
     *
     * @param  string $termId    Taxonomy term ID
     *
     * @return string             1 if item is typical, 0 otherwise
     */
    public function getTypicalListItemStatus($termId)
    {
        $isTypical = $this->getTypicalListItem($termId);

        $status = '0';
        if (! empty($isTypical)) {
            $item = reset($isTypical);
            $status = $item->typicalListItem;
        }
        return $status;
    }


    /**
     * Fetch typical list items from the DB
     *
     * @return array    Extended taxonomy objects
     */
    private function getTypicalListItems()
    {
        return $this->db->get_results("SELECT * FROM wp_term_taxonomy_extended WHERE typical_list_item = '1'");
    }


    /**
     * Fetch typical list item IDs from the DB
     *
     * @return array    Taxonomy IDs
     */
    public function getTypicalListItemIds()
    {
        $typicalItems = $this->getTypicalListItems();

        $typicalItemIds = [];
        foreach ($typicalItems as $item) {
            $typicalItemIds[] = $item->term_id;
        }

        return $typicalItemIds;
    }
}
