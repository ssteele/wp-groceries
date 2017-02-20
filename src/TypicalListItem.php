<?php

namespace SteveSteele\Groceries;

class TypicalListItem
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
    private function persist()
    {
        $existingRecord = $this->get($this->termId);

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
    public function save($termId, $isTypical = 0)
    {
        $this->setTermId($termId);
        $this->setIsTypical($isTypical);
        $this->persist();
    }


    /**
     * Fetch typical list item status from the DB
     *
     * @param  string $termId    Taxonomy term ID
     *
     * @return array              Filled with extended taxonomy objects
     */
    private function get($termId)
    {
        return $this->db->get_results("SELECT * FROM wp_term_taxonomy_extended WHERE term_id = $termId");
    }


    /**
     * Get typical list item status (wrapper)
     *
     * @param  arr $isTypical    Filled with extended taxonomy objects
     *
     * @return str               1 if item is typical, 0 otherwise
     */
    public function fetchStatus($isTypical)
    {
        $status = '0';
        if (! empty($isTypical)) {
            $item = reset($isTypical);
            $status = $item->typical_list_item;
        }
        return $status;
    }


    /**
     * Get typical list item status (wrapper)
     *
     * @param  string $termId    Taxonomy term ID
     *
     * @return string             1 if item is typical, 0 otherwise
     */
    public function getStatus($termId)
    {
        $isTypical = $this->get($termId);
        return $this->fetchStatus($isTypical);
    }


    /**
     * Fetch typical list items from the DB
     *
     * @return array    Extended taxonomy objects
     */
    private function all()
    {
        return $this->db->get_results("SELECT * FROM wp_term_taxonomy_extended WHERE typical_list_item = '1'");
    }


    /**
     * Fetch typical list item IDs from the DB
     * @param  array    Extended taxonomy objects
     *
     * @return array    Taxonomy IDs
     */
    public function fetchIds($typicalItems = [])
    {
        $typicalItemIds = [];
        foreach ($typicalItems as $item) {
            $typicalItemIds[] = $item->term_id;
        }

        return $typicalItemIds;
    }


    /**
     * Get typical list item IDs from the DB
     *
     * @return array    Taxonomy IDs
     */
    public function getIds()
    {
        $typicalItems = $this->all();
        return $this->fetchIds($typicalItems);
    }
}