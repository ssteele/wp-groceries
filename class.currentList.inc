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

            $unit_map = get_option('_ingredient_units');

            $id = 'i';
            $amount = 'a';
            $unit = 'u';

            foreach ($this->groceries as $item) {

                $name = tax_ids_to_ingredients(array($item[$id]));
                $unit_name = unit_index_to_name($item[$unit]);

                // Get single units where necessary
                if (!stristr($item[$amount], 'to') && ($item[$amount] != 0 && $item[$amount] <= 1)) {
                    $unit_name = $unit_map[$unit_name];
                }

                // Build the list item
                $li  = '';
                $li .= (isset($item[$amount]) && !empty($item[$amount])) ? $item[$amount] . ' ' : '';
                $li .= (isset($unit_name) && !empty($unit_name)) ? $unit_name . ' ' : '';
                $li .= $name[0] . ' ';

                echo '<li id="' . $item[$id] . '">';
                echo        $li;
                echo '</li>';

            }

        } else {

            echo '<div style="margin:50px 0;">';
            echo    'No groceries saved for this user.';
            echo '</div>';

        }

    }


    /**
     * Save groceries submitted via 'grocery-list' admin page
     */
    public function save_groceries() {

        // Declare list array
        $arr_items = array();

        // Define categories and expected input types
        $cats = array(
            'recipe'         => 'i',
            'ingredient'     => 'i',
            'new_ingredient' => 's'
        );

        // Define category-specific arrays and sanitize user input
        foreach ($cats as $cat => $type) {

            if (empty($_POST[$cat])) continue;

            $$cat = array();
            foreach ($_POST[$cat] as $item) {

                array_push($$cat, sanitize_input($item, $type));

            }

        }

        $id = 'i';
        $amount = 'a';
        $unit = 'u';
        $type = 't';

        // Handle recipes
        if (isset($recipe) && !empty($recipe)) {

            foreach ($recipe as $r) {

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

        // Handle unknown ingredients submitted by user (this allows the user to save anything to the list)
        if (isset($new_ingredient) && !empty($new_ingredient)) {

            foreach ($new_ingredient as $n) {

                if (!empty($n)) {

                    $arr_items[] = array(
                        $amount     => 0,
                        $unit       => 0,
                        $type       => 'n',
                    );

                }

            }

        }

        $this->set_list($arr_items);

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