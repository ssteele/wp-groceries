<?php

namespace SteveSteele\Groceries;

class CurrentList extends GroceryList
{

    // Declare properties
    public $groceries;
    public $userId;
    public $db;


    /**
     * Save current user grocery list to the DB
     * @param  array $groceries    Grocery list items
     * @param  int   $storeId      Store identifier
     */
    protected function setList($groceries, $storeId = null)
    {
        $srlGroceries = maybe_serialize($groceries);
        update_user_meta($this->userId, '_grocery_list', $srlGroceries);
    }


    /**
     * Retrieve grocery list from the DB
     * Sort items by aisle if store specified
     * @param  int   $storeId     Store identifier
     *
     * @return array              Grocery list items
     */
    protected function getList($storeId = null)
    {
        $srlGroceries = get_user_meta($this->userId, '_grocery_list', true);

        if (is_null($storeId)) {
            $this->groceries = maybe_unserialize($srlGroceries);
        } else {
            $masterList = new MasterList();
            $masterStoreList = $masterList->getList($storeId);

            $this->groceries = maybe_unserialize($srlGroceries);

            if (is_array($this->groceries)) {
                usort($this->groceries, [new SortList($masterStoreList), 'sort']);
            }
        }

        return $this->groceries;
    }


    /**
     * Render the contents of the 'grocery_list' page
     * @param  int $storeId    Store identifier
     *
     * @return string          Markup
     */
    public function renderGroceries($storeId)
    {
        $output = '';

        $this->getList($storeId);

        if (! empty($this->groceries)) {
            // Grab the user master list for this store
            $masterList = new MasterList();
            $arrMaster = $masterList->getList($storeId);

            $unitMap = get_option('_ingredient_units');

            $id = 'i';
            $amount = 'a';
            $unit = 'u';
            $optional = 'o';
            $pic = 'p';

            // Get user store dropdown selection
            $userSelectedStoreUrl = (isset($_GET['sid']) && ! empty($_GET['sid'])) ? '?sid=' . sanitize_input($_GET['sid']) : '';

            $refreshAlert = false;
            $output .= '<a href="' . site_url() . '/grocery-list/' . $userSelectedStoreUrl . '"><li id="notify_new">New items added: Please click here before shopping!</li></a>';

            $arrNewbies = $masterList->getNewIngredients($storeId);
            $ingredients = new Ingredients();

            // Flag optional ingredients so the legend will show only when necessary
            $optionalFlag = false;

            foreach ($this->groceries as $item) {
                // Make sure the item is represented in the master store list
                if (! in_array($item[$id], $arrMaster)) {
                    // Not in the master list - prepend it
                    $masterList->insertNewIngredient($storeId, $item[$id]);
                    $refreshAlert = true;
                }

                // Prepend any item description (usually 'organic')
                $desc = term_description($item[$id], 'ingredient');
                $name = $ingredients->fromTaxIds([$item[$id]]);
                $unitName = $ingredients->unitIndexToName($item[$unit]);

                // Get single units where necessary
                if (! stristr($item[$amount], 'to') && ($item[$amount] != 0 && $item[$amount] <= 1)) {
                    $unitName = $unitMap[$unitName];
                }

                // Get recipe thumbnail
                $thumb = (isset($item[$pic]) && ! empty($item[$pic])) ? $item[$pic] . ' ' : '';

                $optionalFlag = ('' !== $item[$optional]) ? true : $optionalFlag;

                // Build the list item
                $li  = '';
                $li .= $thumb;
                $li .= (isset($item[$amount]) && ! empty($item[$amount])) ? $item[$amount] . ' ' : '';
                $li .= (isset($unitName) && ! empty($unitName)) ? $unitName . ' ' : '';
                $li .= (isset($desc) && ! empty($desc)) ? strip_tags($desc) . ' ' : '';
                $li .= $name[0] . $item[$optional] . ' ';

                // Build necessary classes
                $liClass = [];

                if (isset($arrNewbies) && is_array($arrNewbies) && in_array($item[$id], $arrNewbies)) {
                    $liClass[] = 'new-item';
                }

                if (! empty($thumb)) {
                    $liClass[] = 'has-thumb';
                }

                $liClasses = implode(' ', $liClass);

                $output .= '<li id="' . $item[$id] . '" class="' . $liClasses . '">';
                $output .=    $li;
                $output .= '</li>';

            }

            if ($refreshAlert) {
                $output .= "
                <script>
                    jQuery('#notify_new').show();
                </script>
                ";
            }

            $output .= '<div>';
            $output .=    ($optionalFlag) ? '* denotes optional ingredient' : '';
            $output .= '</div>';
        } else {
            $output .= '<div style="margin:50px 0;">';
            $output .=    'No groceries saved for this user.';
            $output .= '</div>';
        }

        return $output;
    }


    /**
     * Save groceries submitted via 'grocery-list' admin page
     * @return boolean    True if new ingredient added (to alert admin user to refresh the page before creating a new list)
     */
    public function saveGroceries()
    {
        // Declare list array
        $arrItems = [];

        // Define categories and expected input types
        $cats = [
            'i'             => 'i',                                 // Toggled ingredients from list prior
            'recipe'        => 'i',                                 // Recipes
            'ingredient'    => 'i',                                 // Ingredients chosen from 'ingredient' taxonomy terms
            'newIngredient' => 's',                                 // Unknown ingredient (string, not ID)
        ];

        // Define category-specific arrays and sanitize user input
        foreach ($cats as $cat => $type) {
            // Create an array (even if there's nothing to fill it)
            $$cat = [];

            // Bail if nothing to fill
            if (empty($_POST[$cat])) {
                continue;
            }

            foreach ($_POST[$cat] as $item) {
                array_push($$cat, sanitize_input($item, $type));
            }

        }

        // Merge new ingredients with toggled old ingredients
        $ingredient = array_merge($i, $ingredient);

        // Merge in typical items with ingredients if toggled
        if (isset($_POST['typical_items_toggle'])) {
            $typicalItems = get_typical_list_item_ids();
            $ingredient = array_unique(array_merge($typicalItems, $ingredient));
        }

        $ingredients = new Ingredients();

        $id = 'i';
        $amount = 'a';
        $unit = 'u';
        $type = 't';
        $optional = 'o';
        $pic = 'p';

        // Handle recipes
        if (isset($recipe) && ! empty($recipe)) {
            foreach ($recipe as $r) {
                // Grab recipe thumbnail and meta (including ingredients)
                $rThumbnail = get_the_post_thumbnail($r, 'icon');
                $arrMeta = get_post_meta($r);

                foreach ($arrMeta as $key => $val) {
                    if (preg_match('/_ingredient_(\d+)/', $key, $match)) {
                        // Initialize variables
                        list($metaKey, $ingredientId) = $match;

                        // Grab ingredient meta
                        $doubleSerializedMeta = array_shift($val);
                        $serializedMeta = maybe_unserialize($doubleSerializedMeta);
                        $meta = maybe_unserialize($serializedMeta);

                        // Translate unit into unit index
                        $metaUnit = $ingredients->unitNameToIndex($meta['unit']);

                        // Flag for optional ingredients
                        $isOptional = preg_match('/\(optional\)/', $meta['prep']);

                        // Add ingredient to list
                        $arrItems[] = [
                            $id       => $ingredientId,
                            $amount   => $meta['amount'],
                            $unit     => $metaUnit,
                            $type     => 'i',
                            $optional => ($isOptional) ? '*' : '',
                            $pic      => '<div class="recipe-thumb">' . $rThumbnail . '</div>',
                        ];
                    }
                }
            }
        }

        // Handle known ingredients (taxonomy terms)
        if (isset($ingredient) && ! empty($ingredient)) {
            foreach ($ingredient as $i) {
                $term = get_term($i, 'ingredient', OBJECT);

                $arrItems[] = [
                    $id       => $term->term_id,
                    $amount   => 0,
                    $unit     => 0,
                    $type     => 'i',
                    $optional => '',
                ];
            }
        }

        $isNewIngredient = false;

        // Handle unknown ingredients submitted by user (this allows the user to save anything to the list)
        // ...make unknown ingredients known
        if (isset($newIngredient) && ! empty($newIngredient)) {
            foreach ($newIngredient as $n) {
                if (! empty($n)) {
                    $isNewIngredient = true;

                    // Add the new ingredient to our list of terms
                    $termId = wp_insert_term($n, 'ingredient');

                    if (! empty($termId) && is_array($termId)) {
                        $arrItems[] = [
                            $id         => $termId['term_id'],
                            $amount     => 0,
                            $unit       => 0,
                            $type       => 'i',
                        ];
                    }
                }
            }
        }

        $this->setList($arrItems);

        return $isNewIngredient;
    }


    /**
     * Render previously saved items to admin page
     *
     * @return string    Markup
     */
    public function existingAdminList()
    {
        $output = '';
        $this->getList();

        $ingredients = new Ingredients();
        if (! empty($this->groceries)) {
            foreach ($this->groceries as $item) {
                $name = $ingredients->fromTaxIds([$item['i']]);

                $output .= '<li>';
                $output .=     '<input type="checkbox" name="' .  $item['t'] . '[]" id="' . $item['i'] . '" value="' . $item['i'] . '" />';
                $output .=     '<label for="' . $item['i'] . '"> ' . $name[0] . '</label>';
                $output .= '</li>';
            }
        }

        return $output;
    }
}
