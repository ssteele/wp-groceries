<?php
/*
Plugin Name: SHS Grocery List
Plugin URI: http://steve-steele.com/
Description: Create an interactive list to use while grocery shopping
Version: 1.0
Author: Steve Steele
Author URI: http://steve-steele.com/

To turn this into an actual WP plugin, all of the javascript that pertains to the 'grocery_list' or 'recipes' plugins have to be extracted from the theme

*/

use SteveSteele\Groceries\TypicalListItems;
use SteveSteele\Groceries\GroceryStores;
use SteveSteele\Groceries\CurrentList;

require_once 'vendor/autoload.php';


/**
 * Install the plugin and create a DB table
 */
function shs_grocery_list_install()
{
    // Check to make sure 'Recipes' is activated
    if (! function_exists('shs_save_recipe')) {
        wp_die('This plugin depends on functionality from the \'Recipes\' plugin');
    }

    add_option('shs_grocery_list_version', '1.0');
    add_option('shs_grocery_list_id', 'SHS-GroceryList-' . time());

    global $wpdb;

    // Add grocery item order table
    $tableName = $wpdb->prefix . 'grocery_order';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
        // Code to generate table
        $sql = "CREATE TABLE $tableName (
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        store_id INT(11) UNSIGNED NOT NULL,
        groceries LONGTEXT,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY store_id (store_id)
        );";

        // Import a file we need to call dbDelta function
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Run the SQL
        dbDelta($sql);
    }

    // Add table to reset to a grocery list to typical items (specified on taxonomy term page)
    $tableName = $wpdb->prefix . 'term_taxonomy_extended';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
        // Code to generate table
        $sql = "CREATE TABLE $tableName (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        typical_list_item TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY term_id (term_id)
        );";

        // Import a file we need to call dbDelta function
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Run the SQL
        dbDelta($sql);
    }

    // Add store table
    $tableName = $wpdb->prefix . 'stores';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
        // Code to generate table
        $sql = "CREATE TABLE $tableName (
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(64) NOT NULL,
        number VARCHAR(128) NOT NULL,
        street VARCHAR(256) NOT NULL,
        city VARCHAR(128) NOT NULL,
        state VARCHAR(64) NOT NULL,
        zip VARCHAR(10) NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id)
        );";

        // Import a file we need to call dbDelta function
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Run the SQL
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'shs_grocery_list_install');


/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    WORDPRESS FUNCTIONALITY                                         // Keep WP functionality here unless it becomes better to bootstrap/template in from classes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/


/**
 * Convert a list of readable ingredients to WP taxonomy indices
 * @param  array $ingredients    Readable ingredients list
 * @return array                 Ingredient indices
 */
function ingredients_to_tax_ids($ingredients)
{
    // Translate names to ingredient taxonomy IDs
    $output = [];
    foreach ($ingredients as $name) {
        $object = get_term_by('name', $name, 'ingredient');
        $output[] = $object->term_id;
    }

    return $output;
}


/**
 * Convert a list of WP taxonomy indices to readable ingredients
 * @param  array $indices    Ingredient indices
 * @return array             Readable ingredients list
 */
function tax_ids_to_ingredients($indices)
{
    // Translate ingredient taxonomy IDs to names
    $output = [];
    foreach ($indices as $i) {
        $object = get_term_by('id', $i, 'ingredient');

        if ($object) {
            $output[] = $object->name;
        }
    }

    return $output;
}


/**
 * Convert ingredient unit name to proper index before saving to DB
 * @param  string $name    Ingredient unit name
 * @return string          Ingredient unit index
 */
function unit_name_to_index($name)
{
    if (empty($name)) {
        return false;
    }

    $unitList = array_flip(get_option('_ingredient_unit_list'));
    return $unitList[$name];
}


/**
 * Convert ingredient unit index to name before rendering data from DB
 * @param  string $index    Ingredient unit index
 * @return string           Ingredient unit name
 */
function unit_index_to_name($index)
{
    $unitList = get_option('_ingredient_unit_list');
    return $unitList[$index];
}


/**
 * Create shortcode to access grocery list object methods
 * @param  array $atts    Shortcode args passed in from admin page
 */
function grocery_list_shortcode($atts)
{
    if (! is_admin()) {
        // Bail if no user logged in and shortcode public attribute not explicitly set to true
        if (! is_user_logged_in() && (isset($atts['public']) && $atts['public'] != true)) {
            return false;
        }

        the_groceries();
    }
}
add_shortcode('groceries', 'grocery_list_shortcode');


/**
 * Save typical grocery list item status (wrapper)
 *
 * @param  integer  $termId       Taxonomy term ID
 *
 * @return void
 */
function save_typical_list_item_status($termId = null)
{
    if ($termId) {
        $isTypical = isset($_POST['is_typical']) ? $_POST['is_typical'] : 0;
        $typicalListItems = new TypicalListItems();
        $typicalListItems->saveTypicalListItem($termId, $isTypical);
    }
}


/**
 * Get typical list item status (wrapper)
 *
 * @param  string $termId    Taxonomy term ID
 *
 * @return string            1 if item is typical, 0 otherwise
 */
function get_typical_list_item_status($termId)
{
    $typicalListItems = new TypicalListItems();
    return $typicalListItems->getTypicalListItemStatus($termId);
}


/**
 * Get typical list items (wrapper)
 *
 * @return array    Filled with extended taxonomy objects
 */
function get_typical_list_item_ids()
{
    $typicalListItems = new TypicalListItems();
    return $typicalListItems->getTypicalListItemIds();
}


/**
 * Add grocery list and stores admin sidebar submenus
 */
function register_grocery_list_admin_pages()
{
    add_submenu_page(
        'edit.php?post_type=recipe',
        'Manage Grocery Stores',
        'Grocery Stores',
        'manage_options',
        'grocery-stores',
        'render_grocery_stores_admin_form'
    );

    add_submenu_page(
        'edit.php?post_type=recipe',
        'Create a Grocery List',
        'Grocery List',
        'manage_options',
        'grocery-list',
        'render_grocery_list_admin_form'
    );
}
add_action('admin_menu', 'register_grocery_list_admin_pages');


/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    CORE PLUGIN FUNCTIONALITY
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/


/**
 * Display the grocery list
 */
function the_groceries()
{
    $groceryStore = new GroceryStores();
    $groceryStore->renderStoreDropdown();

    // Display current user
    echo '<div class="login">';
    if (! $groceryStore->isGuest) {
        $user = get_userdata($groceryStore->userId);
        echo $user->display_name;
    } else {
        echo '<a href="' . wp_login_url(get_permalink()) . '" title="Login">Login</a>';
    }
    echo '</div>';

    // Set the store id
    echo '<div id="store_id" rel="' . $groceryStore->id . '" style="display:none;"></div>';

    ?>

    <h2><?php $groceryStore->getStoreName($groceryStore->id, true); ?></h2>

    <div class="ingredients groceries">
        <ul id="slip_list">

            <?php

            // Get existing list items
            $currentList = new CurrentList();
            $currentList->renderGroceries($groceryStore->id);

            ?>

        </ul>
    </div>

    <?php
}


/**
 * Render the form used to generate grocery lists
 */
function render_grocery_list_admin_form()
{
    if (! current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate current list object
    $currentList = new CurrentList();

    if (isset($_POST['submit'])) {
        $isNewIngredient = $currentList->saveGroceries();

        ?>

        <div class="updated"><p><strong>Grocery List Saved</strong></p></div>

        <?php

        if ($isNewIngredient) {
            $currentUrl = site_url() . '/wp-admin/edit.php?post_type=recipe&page=grocery-list';
            ?>

            <div class="updated">
                <p><strong>New ingredient(s) added - please <a href="<?php echo $currentUrl; ?>">click here</a> before proceeding</strong></p>
            </div>

            <?php

        }
    }

    ?>

    <div class="wrap grocery-list" ng-controller="IngredientsCtrl" ng-app>

        <h2>Create a Grocery List</h2>

        <form name="grocery_list" method="post" action="#" enctype="multipart/form-data" autocomplete="off">

            <div class="select">

                <input class="search" id="search_ingredient" type="text" placeholder="Ingredient" tabindex="1" ng-model="search_ingredient.name" />
                <div class="dropdown-container">
                    <ul class="filtered-dropdown">
                        <li tabindex="100" ng-show="search_ingredient.name" ng-repeat="ingredient in ingredients | filter:search_ingredient" ng-click="addIngredientToList(ingredient)">
                            {{ingredient.name}}
                        </li>
                        <li tabindex="100" ng-show="renderUnknownIngredient(search_ingredient)" ng-click="addNewIngredientToList(search_ingredient)">
                            {{search_ingredient.name}}
                        </li>
                    </ul>
                </div>

                <input class="search" id="search_recipe" type="text" placeholder="Recipe" tabindex="1" ng-model="search_recipe.name" />
                <div class="dropdown-container">
                    <ul class="filtered-dropdown">
                        <li tabindex="100" ng-show="search_recipe.name" ng-repeat="recipe in recipes | filter:search_recipe" ng-click="addRecipeToList(recipe)">
                            {{recipe.name}}
                        </li>
                    </ul>
                </div>

            </div>

            <div class="list-box new">
                <h3>New items</h3>
                <ul ng-repeat="item in cart">
                    <li ng-show="item.name">
                        <input type="checkbox" name="{{item.type}}[]" id="{{item.id}}" value="{{item.id}}" checked="checked" />
                        <label for="{{item.id}}"> {{item.name}}</label>
                    </li>
                </ul>
            </div>

            <div class="list-box existing">

                <h3>
                    <input type="checkbox" id="typical_items_toggle" name="typical_items_toggle" value="1" />
                    <label for="typical_items_toggle">Typical items</label>
                </h3>

                <h3>
                    <input type="checkbox" id="current_items_toggle_all" />
                    <label for="current_items_toggle_all">Current items</label>
                </h3>

                <ul id="current_items">

                    <?php
                    // Get existing list items
                    $currentList->existingAdminList();
                    ?>

                </ul>

            </div>

            <div class="clearfix"></div>

            <div class="hr"></div>

            <div id="grocery-list-footer">
                <p class="current-list">The current list can be found <a href="<?php echo site_url(); ?>/grocery-list/">here</a></p>

                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Grocery List" />
                </p>
            </div>

        </form>
    </div>

    <?php

}


/**
 * Render the form used to generate grocery lists
 */
function render_grocery_stores_admin_form()
{
    if (! current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate grocery stores object
    $groceryStores = new GroceryStores();

    if (isset($_POST['submit'])) {
        $groceryStores->saveStores();
        echo '<div class="updated"><p><strong>Grocery Stores Saved</strong></p></div>';
    }

    ?>

    <div class="wrap grocery-stores">

        <h2>Manage Grocery Stores</h2>

        <form name="grocery_stores" method="post" action="#" enctype="multipart/form-data" autocomplete="off">

            <div class="list-box new">
                <div class="store-input">
                    <select class="state-dropdown" name="state" size="1">
                        <option value=""></option>
                        <option value="TX" selected="selected">TX</option>
                    </select>
                    <div class="clearfix"></div>
                    <input type="text" name="name" id="name" class="half" placeholder="Store Name*" />
                    <input type="text" name="number" id="number" class="half" placeholder="Store Number" />
                    <input type="text" name="street" id="street" class="full" placeholder="Street" />
                    <input type="text" name="city" id="city" class="half" placeholder="City" />
                    <input type="text" name="zip" id="zip" class="half" placeholder="Zip" />
                </div>
            </div>

            <?php $groceryStores->showStores(); ?>

            <div class="clearfix"></div>

            <div class="hr"></div>

            <div id="grocery-list-footer">
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Grocery Stores" />
                </p>
            </div>

        </form>

    <?php

}
