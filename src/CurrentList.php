<?php

namespace SteveSteele\Groceries;

class CurrentList extends GroceryList
{

    // Declare properties
    public $groceries = [];
    public $isNewIngredient = false;
    public $userId;


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
            $groceries = maybe_unserialize($srlGroceries);
        } else {
            $masterList = new MasterList();
            $masterStoreList = $masterList->getList($storeId);

            $groceries = maybe_unserialize($srlGroceries);

            if (is_array($groceries)) {
                usort($groceries, [new SortList($masterStoreList), 'sort']);
            }
        }

        return $groceries;
    }


    /**
     * Render the contents of the 'grocery_list' page
     * @param  int                    $storeId       Store identifier
     * @param  \SteveSteele\Sanitizer $sanitizer     Dedicated input sanitization object
     *
     * @return string                                Markup
     */
    public function renderGroceries($storeId, \SteveSteele\Sanitizer $sanitizer)
    {
        $output = '';

        $groceries = $this->getList($storeId);

        if (! empty($groceries)) {
            // Grab the user master list for this store
            $masterList = new MasterList();
            $arrMaster = $masterList->getList($storeId);

            $unitMap = get_option('_ingredient_units_to_singular_names_map');

            $id = 'i';
            $amount = 'a';
            $unit = 'u';
            $optional = 'o';
            $pic = 'p';

            // Get user store dropdown selection
            $userSelectedStoreUrl = (isset($_GET['sid']) && ! empty($_GET['sid'])) ? '?sid=' . $sanitizer->sanitize($_GET['sid']) : '';

            $refreshAlert = false;
            $output .= '<a href="' . site_url() . '/grocery-list/' . $userSelectedStoreUrl . '"><li id="notify_new">New items added: Please click here before shopping!</li></a>';

            $arrNewbies = $masterList->getNewIngredients($storeId);
            $ingredientTranslator = new IngredientTranslator();

            // Flag optional ingredients so the legend will show only when necessary
            $optionalFlag = false;

            foreach ($groceries as $item) {
                // Make sure the item is represented in the master store list
                if (! in_array($item[$id], $arrMaster)) {
                    // Not in the master list - prepend it
                    $masterList->insertNewIngredient($storeId, $item[$id]);
                    $refreshAlert = true;
                }

                // Prepend any item description (usually 'organic')
                $desc = term_description($item[$id], 'ingredient');
                $name = $ingredientTranslator->fromTaxIds([$item[$id]]);
                $unitName = $ingredientTranslator->indexToUnitName($item[$unit]);

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
     * Extract and sanitize groceries from admin page user input
     * @param  array                  $post         Raw form post
     * @param  \SteveSteele\Sanitizer $sanitizer    Dedicated input sanitization object
     *
     * @return array                                Filled with sanitized input category arrays
     *                                              ...current items, recipes, ingredients, new ingredients, typical items
     */
    public function extractSaveGroceries($post, \SteveSteele\Sanitizer $sanitizer)
    {
        // Define categories and expected input types
        $cats = [
            'i'              => 'i',                                // Toggled ingredients from list prior
            'recipe'         => 'i',                                // Recipes
            'ingredient'     => 'i',                                // Ingredients chosen from 'ingredient' taxonomy terms
            'new_ingredient' => 's',                                // Unknown ingredient (string, not ID)
        ];

        // Define category-specific arrays and sanitize user input
        foreach ($cats as $cat => $type) {
            // Create an array (even if there's nothing to fill it)
            $$cat = [];

            // Bail if nothing to fill
            if (empty($post[$cat])) {
                continue;
            }

            $post[$cat] = $sanitizer->sanitize($post[$cat], $type);
            foreach ($post[$cat] as $item) {
                array_push($$cat, $item);
            }
        }

        $typicalItems = [];
        // Handle typical items
        if (isset($post['typical_items_toggle'])) {
            $typicalItems = get_typical_list_item_ids($this->userId);
        }

        return [$i, $recipe, $ingredient, $new_ingredient, $typicalItems];
    }


    /**
     * Add items in recipes to grocery list
     * @param  array                                       $recipes                 Filled with recipe IDs
     * @param  \SteveSteele\Groceries\IngredientTranslator $ingredientTranslator    Ingredient translation helper
     *
     * @return array                                       Appended user grocery list
     */
    private function addGroceriesFromRecipes($recipes, IngredientTranslator $ingredientTranslator)
    {
        if (isset($recipes) && ! empty($recipes)) {
            foreach ($recipes as $recipe) {
                // Grab recipe thumbnail and meta (including ingredients)
                $rThumbnail = get_the_post_thumbnail($recipe, 'icon');
                $arrMeta = get_post_meta($recipe);

                foreach ($arrMeta as $key => $val) {
                    if (preg_match('/_ingredient_(\d+)/', $key, $match)) {
                        // Initialize variables
                        list($metaKey, $ingredientId) = $match;

                        // Grab ingredient meta
                        $doubleSerializedMeta = array_shift($val);
                        $serializedMeta = maybe_unserialize($doubleSerializedMeta);
                        $meta = maybe_unserialize($serializedMeta);

                        // Translate unit into unit index
                        $metaUnit = $ingredientTranslator->unitNameToIndex($meta['unit']);

                        // Flag for optional ingredients
                        $isOptional = preg_match('/\(optional\)/', $meta['prep']);

                        // Add ingredient to list
                        $this->groceries[] = [
                            $ingredientTranslator->id       => (int) $ingredientId,
                            $ingredientTranslator->amount   => $meta['amount'],
                            $ingredientTranslator->unit     => $metaUnit,
                            $ingredientTranslator->type     => 'i',
                            $ingredientTranslator->optional => ($isOptional) ? '*' : '',
                            $ingredientTranslator->pic      => '<div class="recipe-thumb">' . $rThumbnail . '</div>',
                        ];
                    }
                }
            }
        }

        return $this->groceries;
    }


    /**
     * Add new items to grocery list
     * @param  array                                       $ingredients             Filled with ingredient IDs
     * @param  \SteveSteele\Groceries\IngredientTranslator $ingredientTranslator    Ingredient translation helper
     *
     * @return array                                       Appended user grocery list
     */
    private function addGroceriesFromIngredients($ingredients, IngredientTranslator $ingredientTranslator)
    {
        // Handle known ingredients (taxonomy terms)
        if (isset($ingredients) && ! empty($ingredients)) {
            foreach ($ingredients as $ingredient) {
                $term = get_term($ingredient, 'ingredient', OBJECT);

                $this->groceries[] = [
                    $ingredientTranslator->id       => $term->term_id,
                    $ingredientTranslator->amount   => '',
                    $ingredientTranslator->unit     => false,
                    $ingredientTranslator->type     => 'i',
                    $ingredientTranslator->optional => '',
                    $ingredientTranslator->pic      => '',
                ];
            }
        }

        return $this->groceries;
    }


    /**
     * Add new items to grocery list
     * @param  array                                       $newIngredients          Filled with new ingredient strings
     * @param  \SteveSteele\Groceries\IngredientTranslator $ingredientTranslator    Ingredient translation helper
     *
     * @return array                                       Appended user grocery list
     */
    private function addGroceriesFromNewIngredients($newIngredients, IngredientTranslator $ingredientTranslator)
    {
        // Handle unknown ingredients submitted by user (this allows the user to save anything to the list)
        if (isset($newIngredients) && ! empty($newIngredients)) {
            foreach ($newIngredients as $newIngredient) {
                if (! empty($newIngredient)) {
                    // Add the new ingredient to our list of terms
                    // @TODO: need to check permissions here
                    $termId = wp_insert_term($newIngredient, 'ingredient');
                    $this->isNewIngredient = true;

                    if (! empty($termId) && is_array($termId)) {
                        $this->groceries[] = [
                            $ingredientTranslator->id       => $termId['term_id'],
                            $ingredientTranslator->amount   => '',
                            $ingredientTranslator->unit     => false,
                            $ingredientTranslator->type     => 'i',
                            $ingredientTranslator->optional => '',
                            $ingredientTranslator->pic      => '',
                        ];
                    }
                }
            }
        }

        return $this->groceries;
    }


    /**
     * Save groceries submitted via 'grocery-list' admin page
     * @param  array                  $post         Raw form post
     * @param  \SteveSteele\Sanitizer $sanitizer    Dedicated input sanitization object
     *
     * @return boolean                              True if new ingredient added (to alert admin user to refresh the page before creating a new list)
     */
    public function saveGroceries($post, \SteveSteele\Sanitizer $sanitizer)
    {
        // Extract post data
        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $this->extractSaveGroceries($post, $sanitizer);

        // Merge ingredients
        $ingredients = array_unique(array_merge($currentItems, $ingredients));
        $ingredients = array_unique(array_merge($typicalItems, $ingredients));

        // Compile groceries
        $ingredientTranslator = new IngredientTranslator();
        $this->addGroceriesFromRecipes($recipes, $ingredientTranslator);
        $this->addGroceriesFromIngredients($ingredients, $ingredientTranslator);
        $this->addGroceriesFromNewIngredients($newIngredients, $ingredientTranslator);

        $this->setList($this->groceries);

        return $this->isNewIngredient;
    }


    /**
     * Render previously saved grocery items to admin page
     * @param  array  $groceries    Grocery items
     *
     * @return string               Markup
     */
    public function renderExistingAdminList($groceries = [])
    {
        $output = '';

        $ingredientTranslator = new IngredientTranslator();
        if (! empty($groceries)) {
            foreach ($groceries as $item) {
                $name = $ingredientTranslator->fromTaxIds([$item['i']]);
                if ($name = array_shift($name)) {
                    $output .= '<li>';
                    $output .=     '<input type="checkbox" name="' .  $item['t'] . '[]" id="' . $item['i'] . '" value="' . $item['i'] . '" />';
                    $output .=     '<label for="' . $item['i'] . '"> ' . $name . '</label>';
                    $output .= '</li>';
                }
            }
        }

        return $output;
    }


    /**
     * Render previously saved grocery items to admin page (wrapper)
     *
     * @return string    Markup
     */
    public function existingAdminList()
    {
        $groceries = $this->getList();
        return $this->renderExistingAdminList($groceries);
    }
}
