<?php

namespace SteveSteele\Groceries;

class MasterList extends GroceryList
{
    // Declare properties
    public $userId;
    public $db;


    /**
     * Save master user grocery list for specified store to the DB
     * @param  array $groceries    Grocery list items
     * @param  int   $storeId      Store identifier
     * @return int                 Number of rows updated or false if error
     */
    protected function setList($groceries, $storeId = null)
    {
        return $this->db->update(
            $this->db->prefix . 'grocery_order',
            [
                'groceries' => maybe_serialize($groceries),
            ],
            [
                'user_id'   => $this->userId,
                'store_id'  => $storeId,
            ],
            [
                '%s',
            ],
            [
                '%d',
                '%d',
            ]
        );
    }


    /**
     * Retrieve the latest ordered-by-aisle list of all grocery items for this user in this store
     * @param  int   $storeId    Store identifier
     * @return array             Ordered grocery store items list
     */
    public function getList($storeId = null)
    {
        $table = $this->db->prefix . 'grocery_order';

        $query = "SELECT groceries FROM $table WHERE user_id = '$this->userId' AND store_id = '$storeId'";
        $groceries = $this->db->get_var($query);

        return maybe_unserialize($groceries);
    }


    /**
     * Update grocery item order for a store while shopping
     * ...called from ajax handler
     * @param  int $storeId    Store identifier
     * @param  integer $a      Arbitrary item ID found above dragged item's new position
     * @param  integer $i      Dragged grocery item ID
     * @param  integer $b      Grocery item ID below new dragged item's position (acts as index w/in JS lib)
     * @return int             Number of rows updated or false if error
     * ...using above:below context here, not before:after
     */
    public function updateStoreOrder($storeId, $a, $i, $b)
    {
        $reorderedList = $this->handleStoreItemReordering($storeId, $a, $i, $b, $this->getList($storeId));
        return $this->setList($reorderedList, $storeId);
    }

    /**
     * Update grocery item order for a store while shopping
     * ...called from ajax handler
     * @param  int $storeId   Store identifier
     * @param  int $a         Arbitrary item ID found above dragged item's new position
     * @param  int $i         Dragged grocery item ID
     * @param  int $b         Grocery item ID below new dragged item's position (acts as index w/in JS lib)
     * @param  arr $list      Master list
     * @return arr            Reordered list
     * ...using above:below context here, not before:after
     */
    public function handleStoreItemReordering($storeId, $a, $i, $b, $list = [])
    {
        // Find 'i'
        $keyI = array_search($i, $list);

        // Remove 'i'
        array_splice($list, $keyI, 1);

        if (isset($a)) {
            if (isset($b)) {
                // Find positions of items above and below item after user sort
                $keyA = array_search($a, $list);
                $keyB = array_search($b, $list);

                if ($i === $a || $i === $b) {
                    // Self case: $keyI === $keyB (if the item were still in the list array and $keyB was found)
                    // Put the item back where it was
                    array_splice($list, $keyI, 0, $i);
                } else if (0 === $keyB) {
                    // Edge case: send 'i' to the front of the line
                    array_unshift($list, $i);
                } else if (0 === $b) {
                    // Edge case: insert i after 'a' (back of the current line)
                    array_splice($list, $keyA + 1, 0, $i);
                } else if ($keyI > $keyB) {
                    // Insert i before 'b'
                    array_splice($list, $keyB, 0, $i);
                } else if ($keyI <= $keyB) {
                    // Insert i after 'a'
                    array_splice($list, $keyA + 1, 0, $i);
                } else {
                    // If nothing else, put the item back where it was
                    // ...this shouldn't ever be called, but is a good fallback
                    array_splice($list, $keyI, 0, $i);
                }
            }
        }

        return $list;
    }


    /**
     * Mark items saved to the current list that do not exist in a store's master list as new
     * @param  int $storeId     Store identifier
     * @param  arr $newItems    List of new items
     */
    private function setNewIngredients($storeId, $newItems)
    {
        $this->db->update(
            $this->db->prefix . 'grocery_order',
            [
                'new_items' => maybe_serialize($newItems),
            ],
            [
                'user_id'   => $this->userId,
                'store_id'  => $storeId,
            ],
            [
                '%s',
            ],
            [
                '%d',
                '%d',
            ]
        );
    }


    /**
     * Retrieve items that have not been incoporated into a store's master list
     * @param  int $storeId     Store identifier
     * @return arr              List of new items
     */
    public function getNewIngredients($storeId)
    {
        $table = $this->db->prefix . 'grocery_order';

        $query = "SELECT new_items FROM $table WHERE user_id = '$this->userId' AND store_id = '$storeId'";
        $newItems = $this->db->get_var($query);

        return maybe_unserialize($newItems);
    }


    /**
     * Remove items from the new items table
     * This happens when the item is sorted by the user into the master list
     * @param  int $storeId    Store identifier
     * @param  int $termId     Newly assigned ingredient ID
     */
    public function removeSortedNewItem($storeId, $termId)
    {
        // Get existing new items
        $newItems = $this->getNewIngredients($storeId);

        if (! empty($newItems) && is_array($newItems) && in_array($termId, $newItems)) {
            $keyI = array_search($termId, $newItems);
            array_splice($newItems, $keyI, 1);

            $this->setNewIngredients($storeId, $newItems);
        }
    }


    /**
     * Mark items saved to the current list that do not exist in a store's master list as new
     * @param  int $storeId    Store identifier
     * @param  int $termId     Newly assigned ingredient ID
     */
    private function logNewIngredient($storeId, $termId)
    {
        // Get existing new items
        $newItems = $this->getNewIngredients($storeId);

        // Merge this item in
        if (! empty($newItems)) {
            $newItems[] = $termId;
        } else {
            $newItems = [$termId];
        }

        $this->setNewIngredients($storeId, $newItems);
    }


    /**
     * Prepend new ingredient to a user store's master list
     * @param  int $storeId    Store identifier
     * @param  int $termId     Newly assigned ingredient ID
     * @return int             Number of rows updated or false if error
     */
    public function insertNewIngredient($storeId, $termId)
    {
        // Log new ingredient to DB until it's incorporated into the master list
        $this->logNewIngredient($storeId, $termId);

        $groceries = $this->getList($storeId);
        array_unshift($groceries, $termId);

        return $this->setList($groceries, $storeId);
    }


    /**
     * Initialize master user grocery list for a new store
     * @param  array $groceries    Grocery list items
     * @param  int   $storeId      Store identifier
     */
    public function initializeList($groceries, $storeId)
    {
        $this->db->insert(
            $this->db->prefix . 'grocery_order',
            [
                'user_id'   => $this->userId,
                'store_id'  => $storeId,
                'groceries' => maybe_serialize($groceries),
            ],
            [
                '%d',
                '%d',
                '%s',
            ]
        );
    }
}
