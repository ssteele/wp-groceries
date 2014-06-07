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
    protected function get_list($store_id=null) {

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
     * @param  integer $a       Dragged grocery item ID
     * @param  integer $b       Grocery item ID following above (acts as index)
     */
    public function update_store_order($store_id, $a, $b) {

        $arr = $this->get_list($store_id);

        // Find 'a'
        $key_a = array_search($a, $arr);

        // Remove 'a'
        array_splice($arr, $key_a, 1);

        if (isset($b) && $b != 0) {

            // Find 'b'
            $key_b = array_search($b, $arr);

            if ($key_b == 0) {

                // Edge case: send 'a' to the front of the line
                array_unshift($arr, $a);

            } else {

                // Insert a before 'b'
                array_splice($arr, $key_b, 0, $a);

            }

        } else {

            // Edge case: send 'a' to the back of the line
            array_push($arr, $a);

        }

        // Save the master list
        $this->set_list($arr, $store_id);

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