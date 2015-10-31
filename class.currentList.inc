<?php

class currentList extends groceryList {


    // Declare properties
    public $groceries;


    /**
     * Save current user grocery list to the DB
     * @param  array $groceries    Grocery list items
     * @param  int   $store_id     Store identifier
     */
    protected function set_list($groceries, $store_id=null) {

        $srl_groceries = maybe_serialize($groceries);
        update_user_meta($this->user_id, '_grocery_list', $srl_groceries);

    }


    /**
     * Retrieve grocery list from the DB
     * Sort items by aisle if store specified
     * @param  int   $store_id     Store identifier
     * @return array               Grocery list items
     */
    protected function get_list($store_id=null) {

        $srl_groceries = get_user_meta($this->user_id, '_grocery_list', true);

        if (is_null($store_id)) {

            $this->groceries = maybe_unserialize($srl_groceries);

        } else {

            $master_list = new masterList();
            $master_store_list = $master_list->get_list($store_id);

            $this->groceries = maybe_unserialize($srl_groceries);

            if (is_array($this->groceries)) {
                usort($this->groceries, array(new sortList($master_store_list), 'sort'));
            }

        }

        return $this->groceries;

    }


    /**
     * Render the contents of the 'grocery_list/' page
     * @param  int $store_id    Store identifier
     */
    public function render_groceries($store_id) {

        $this->get_list($store_id);

        if (!empty($this->groceries)) {

            // Grab the user master list for this store
            $master_list = new masterList();
            $arr_master = $master_list->get_list( $store_id );

            $unit_map = get_option('_ingredient_units');

            $id = 'i';
            $amount = 'a';
            $unit = 'u';
            $pic = 'p';

            // Get user store dropdown selection
            $user_selected_store_url = ( isset( $_GET['sid'] ) && ! empty( $_GET['sid'] ) ) ? '?sid=' . sanitize_input( $_GET['sid'] ) : '';

            $refresh_alert = false;
            echo '<a href="' . site_url() . '/grocery-list/' . $user_selected_store_url . '"><li id="notify_new">New items added: Please click here before shopping!</li></a>';

            $arr_newbies = $master_list->get_new_ingredients( $store_id );

            foreach ($this->groceries as $item) {

                // Make sure the item is represented in the master store list
                if ( ! in_array( $item[$id], $arr_master ) ) {

                    // Not in the master list - prepend it
                    $master_list->insert_new_ingredient( $store_id, $item[$id] );
                    $refresh_alert = true;

                }

                // Prepend any item description (usually 'organic')
                $desc = term_description( $item[$id], 'ingredient' );
                $name = tax_ids_to_ingredients(array($item[$id]));
                $unit_name = unit_index_to_name($item[$unit]);

                // Get single units where necessary
                if (!stristr($item[$amount], 'to') && ($item[$amount] != 0 && $item[$amount] <= 1)) {
                    $unit_name = $unit_map[$unit_name];
                }

                // Get recipe thumbnail
                $thumb = ( isset( $item[$pic] ) && ! empty( $item[$pic] ) ) ? $item[$pic] . ' ' : '';

                // Build the list item
                $li  = '';
                $li .= $thumb;
                $li .= (isset($item[$amount]) && !empty($item[$amount])) ? $item[$amount] . ' ' : '';
                $li .= (isset($unit_name) && !empty($unit_name)) ? $unit_name . ' ' : '';
                $li .= ( isset( $desc ) && ! empty( $desc ) ) ? strip_tags( $desc ) . ' ' : '';
                $li .= $name[0] . ' ';

                // Build necessary classes
                $li_class = array();

                if ( isset( $arr_newbies ) && is_array( $arr_newbies ) && in_array( $item[$id], $arr_newbies ) ) {
                    $li_class[] = 'new-item';
                }

                if ( ! empty( $thumb ) ) {
                    $li_class[] = 'has-thumb';
                }

                $li_classes = implode( ' ' , $li_class );

                echo '<li id="' . $item[$id] . '" class="' . $li_classes . '">';
                echo    $li;
                echo '</li>';

            }

            if ( $refresh_alert ) {
                echo "
                <script>
                    jQuery('#notify_new').show();
                </script>
                ";
            }

        } else {

            echo '<div style="margin:50px 0;">';
            echo    'No groceries saved for this user.';
            echo '</div>';

        }

    }


    /**
     * Save groceries submitted via 'grocery-list' admin page
     * @return boolean    True if new ingredient added (to alert admin user to refresh the page before creating a new list)
     */
    public function save_groceries() {

        // Declare list array
        $arr_items = array();

        // Define categories and expected input types
        $cats = array(
            'i'              => 'i',                                        // Toggled ingredients from list prior
            'recipe'         => 'i',                                        // Recipes
            'ingredient'     => 'i',                                        // Ingredients chosen from 'ingredient' taxonomy terms
            'new_ingredient' => 's',                                        // Unknown ingredient (string, not ID)
        );

        // Define category-specific arrays and sanitize user input
        foreach ($cats as $cat => $type) {

            // Create an array (even if there's nothing to fill it)
            $$cat = array();

            // Bail if nothing to fill
            if (empty($_POST[$cat])) continue;

            foreach ($_POST[$cat] as $item) {

                array_push($$cat, sanitize_input($item, $type));

            }

        }

        // Merge new ingredients with toggled old ingredients
        $ingredient = array_merge( $i, $ingredient );

        $id = 'i';
        $amount = 'a';
        $unit = 'u';
        $type = 't';
        $pic = 'p';

        // Handle recipes
        if (isset($recipe) && !empty($recipe)) {

            foreach ($recipe as $r) {

                // Grab recipe thumbnail
                $r_thumbnail = get_the_post_thumbnail( $r, 'icon' );

                $arr_meta = get_post_meta($r);

                foreach ($arr_meta as $key => $val) {

                    if (preg_match('/_ingredient_(\d+)/', $key, $ingredient_id)) {

                        // Grab ingredient object
                        $term = get_term($ingredient_id[1], 'ingredient', OBJECT);

                        // Grab ingredient meta
                        $meta = get_post_meta($r, '_ingredient_' . $ingredient_id[1]);
                        $meta = maybe_unserialize($meta[0]);

                        // Translate unit into unit index
                        $meta_unit = unit_name_to_index($meta['unit']);

                        // Add ingredient to list
                        $arr_items[] = array(
                            $id         => $term->term_id,
                            $amount     => $meta['amount'],
                            $unit       => $meta_unit,
                            $type       => 'i',
                            $pic        => '<div class="recipe-thumb">' . $r_thumbnail . '</div>',
                        );

                    }

                }

            }

        }

        // Handle known ingredients (taxonomy terms)
        if (isset($ingredient) && !empty($ingredient)) {

            foreach ($ingredient as $i) {

                $term = get_term($i, 'ingredient', OBJECT);

                $arr_items[] = array(
                    $id         => $term->term_id,
                    $amount     => 0,
                    $unit       => 0,
                    $type       => 'i',
                );

            }

        }

        $is_new_ingredient = false;

        // Handle unknown ingredients submitted by user (this allows the user to save anything to the list)
        // ...make unknown ingredients known
        if (isset($new_ingredient) && !empty($new_ingredient)) {

            foreach ($new_ingredient as $n) {

                if (!empty($n)) {

                    $is_new_ingredient = true;

                    // Add the new ingredient to our list of terms
                    $term_id = wp_insert_term( $n, 'ingredient' );

                    if ( ! empty( $term_id ) && is_array( $term_id ) ) {

                        $arr_items[] = array(
                            $id         => $term_id['term_id'],
                            $amount     => 0,
                            $unit       => 0,
                            $type       => 'i',
                        );

                    }

                }

            }

        }

        $this->set_list($arr_items);

        return $is_new_ingredient;

    }


    /**
     * Render previously saved items to admin page
     */
    public function existing_admin_list() {

        $this->get_list();

        if (!empty($this->groceries)) {

            foreach ($this->groceries as $item) {

                $name = tax_ids_to_ingredients(array($item['i']));

                echo '<li>';
                    echo '<input type="checkbox" name="' .  $item['t'] . '[]" id="' . $item['i'] . '" value="' . $item['i'] . '" />';
                    echo '<label for="' . $item['i'] . '"> ' . $name[0] . '</label>';
                echo '</li>';

            }

        }

    }

}

?>