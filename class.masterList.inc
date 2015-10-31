<?php

class masterList extends groceryList {


    // Declare properties


    /**
     * Save master user grocery list for specified store to the DB
     * @param  array $groceries    Grocery list items
     * @param  int   $store_id     Store identifier
     */
    protected function set_list($groceries, $store_id=null) {

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'grocery_order',
            array(
                'groceries' => maybe_serialize($groceries),
            ),
            array(
                'user_id'   => $this->user_id,
                'store_id'  => $store_id,
            ),
            array(
                '%s',
            ),
            array(
                '%d',
                '%d',
            )
        );

    }


    /**
     * Retrieve the latest ordered-by-aisle list of all grocery items for this user in this store
     * @param  int   $store_id    Store identifier
     * @return array              Ordered grocery store items list
     */
    public function get_list($store_id=null) {

        global $wpdb;
        $table = $wpdb->prefix . 'grocery_order';

        $query = "SELECT groceries FROM $table WHERE user_id = '$this->user_id' AND store_id = '$store_id'";
        $groceries = $wpdb->get_var($query);

        return maybe_unserialize($groceries);

    }


    /**
     * Update grocery item order for a store while shopping
     * ...called from ajax handler
     * @param  int $store_id    Store identifier
     * @param  integer $a       Arbitrary item ID found above dragged item's new position
     * @param  integer $i       Dragged grocery item ID
     * @param  integer $b       Grocery item ID below new dragged item's position (acts as index w/in JS lib)
     *
     * ...using above:below context here, not before:after
     *
     */
    public function update_store_order( $store_id, $a, $i, $b ) {

        $arr = $this->get_list( $store_id );

        // Find 'i'
        $key_i = array_search( $i, $arr );

        // Remove 'i'
        array_splice( $arr, $key_i, 1 );

        if ( isset( $a ) ) {

            if ( isset( $b ) ) {

                // Find positions of items above and below item after user sort
                $key_a = array_search( $a, $arr );
                $key_b = array_search( $b, $arr );

                if ( $i === $a || $i === $b ) {

                    // Self case: $key_i === $key_b (if the item were still in the list array and $key_b was found)
                    // Put the item back where it was
                    array_splice( $arr, $key_i, 0, $i );

                } else if ( 0 === $key_b ) {

                    // Edge case: send 'i' to the front of the line
                    array_unshift( $arr, $i );

                } else if ( 0 === $b ) {

                    // Edge case: insert i after 'a' (back of the current line)
                    array_splice( $arr, $key_a + 1, 0, $i );

                } else if ( $key_i > $key_b ) {

                    // Insert i before 'b'
                    array_splice( $arr, $key_b, 0, $i );

                } else if ( $key_i < $key_b ) {

                    // Insert i after 'a'
                    array_splice( $arr, $key_a + 1, 0, $i );

                }

            }

        }

        // Save the master list
        $this->set_list( $arr, $store_id );

    }


    /**
     * Mark items saved to the current list that do not exist in a store's master list as new
     * @param  int $store_id     Store identifier
     * @param  arr $new_items    List of new items
     */
    private function set_new_ingredients( $store_id, $new_items ) {

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'grocery_order',
            array(
                'new_items' => maybe_serialize( $new_items ),
            ),
            array(
                'user_id'   => $this->user_id,
                'store_id'  => $store_id,
            ),
            array(
                '%s',
            ),
            array(
                '%d',
                '%d',
            )
        );

    }


    /**
     * Retrieve items that have not been incoporated into a store's master list
     * @param  int $store_id     Store identifier
     * @return arr               List of new items
     */
    public function get_new_ingredients( $store_id ) {

        global $wpdb;
        $table = $wpdb->prefix . 'grocery_order';

        $query = "SELECT new_items FROM $table WHERE user_id = '$this->user_id' AND store_id = '$store_id'";
        $new_items = $wpdb->get_var( $query );

        return maybe_unserialize( $new_items );

    }


    /**
     * Remove items from the new items table
     * This happens when the item is sorted by the user into the master list
     * @param  int $store_id    Store identifier
     * @param  int $term_id     Newly assigned ingredient ID
     */
    public function remove_sorted_new_item( $store_id, $term_id ) {

        // Get existing new items
        $new_items = $this->get_new_ingredients( $store_id );

        if ( ! empty($new_items) && is_array( $new_items ) && in_array( $term_id, $new_items ) ) {

            $key_i = array_search( $term_id, $new_items );
            array_splice( $new_items, $key_i, 1 );

            $this->set_new_ingredients( $store_id, $new_items );

        }

    }


    /**
     * Mark items saved to the current list that do not exist in a store's master list as new
     * @param  int $store_id    Store identifier
     * @param  int $term_id     Newly assigned ingredient ID
     */
    private function log_new_ingredient( $store_id, $term_id ) {

        // Get existing new items
        $new_items = $this->get_new_ingredients( $store_id );

        // Merge this item in
        if ( ! empty( $new_items ) ) {
            $new_items[] = $term_id;
        } else {
            $new_items = array( $term_id );
        }

        $this->set_new_ingredients( $store_id, $new_items );

    }


    /**
     * Prepend new ingredient to a user store's master list
     * @param  int $store_id    Store identifier
     * @param  int $term_id     Newly assigned ingredient ID
     */
    public function insert_new_ingredient( $store_id, $term_id ) {

        // Log new ingredient to DB until it's incorporated into the master list
        $this->log_new_ingredient( $store_id, $term_id );

        $groceries = $this->get_list( $store_id );
        array_unshift( $groceries, $term_id );

        $this->set_list( $groceries, $store_id );

    }


    /**
     * Initialize master user grocery list for a new store
     * @param  array $groceries    Grocery list items
     * @param  int   $store_id     Store identifier
     */
    public function initialize_list($groceries, $store_id) {

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'grocery_order',
            array(
                'user_id'   => $this->user_id,
                'store_id'  => $store_id,
                'groceries' => maybe_serialize($groceries)
            ),
            array(
                '%d',
                '%d',
                '%s'
            )
        );

    }

}


/**
 * Start a user/store combo off with this arbitrary list of grocery items by aisle
 * This can definitely be improved upon if the site warrants, including calling it from some place
 * @param  integer $user_id     WP user id
 * @param  integer $store_id    Store identifier
 */
function initialize_ordered_grocery_store_items($user_id, $store_id) {

    $readable_list = array(
        'carrot',
        'spinach',
        'tofu',
        'avocado',
        'tomato',
        'jalapeno',
        'bell pepper',
        'banana',
        'squash',
        'asparagus',
        'eggplant',
        'sweet potato',
        'green onion',
        'red onion',
        'onion',
        'garlic',
        'lemon',
        'lime',
        'peanut butter',
        'bread',
        'cashews',
        'tortillas',
        'broth',
        'oats',
        'applesauce',
        'black beans',
        'garbonzo beans',
        'corn',
        'peas',
        'barbecue sauce',
        'mustard',
        'rice',
        'soy sauce',
        'hot sauce',
        'enchilada sauce',
        'chiles',
        'salsa',
        'tomato puree',
        'spaghetti sauce',
        'macaroni',
        'bread crumbs',
        'sun-dried-tomato',
        'olive oil',
        'flour',
        'pie crust',
        'chocolate chips',
        'sugar',
        'brown sugar',
        'coconut milk',
        'curry paste',
        'pumpkin',
        'basil',
        'cayenne',
        'chili powder',
        'cilantro',
        'cinnamon',
        'cumin',
        'dill',
        'garlic powder',
        'mustard powder',
        'parsley',
        'pepper',
        'pumpkin pie spice',
        'salt',
        'vanilla extract',
        'baking soda',
        'cheese',
        'feta',
        'parmesan',
        'ricotta',
        'milk',
        'half-n-half',
        'yogurt',
        'eggs',
        'butter',
    );

    // Translate names to ingredient taxonomy IDs
    $initial_list = ingredients_to_tax_ids($readable_list);

    $master_list = new masterList();
    $master_list->initialize_list($initial_list, $store_id);

}

?>