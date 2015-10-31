<?php
/*
Plugin Name: Grocery List
Plugin URI: http://steve-steele.com/
Description: Create an interactive list to use while grocery shopping
Version: 1.0
Author: Steve Steele
Author URI: http://steve-steele.com/

To turn this into an actual WP plugin, all of the javascript that pertains to the 'grocery_list' or 'recipes' plugins have to be extracted from the theme

*/


/**
 * Install the plugin and create a DB table
 */
function shs_grocery_list_install() {

    // Check to make sure 'Recipes' is activated
    if (!function_exists('shs_save_recipe')) {
        wp_die('This plugin depends on functionality from the \'Recipes\' plugin');
    }

    add_option('shs_grocery_list_version', '1.0');
    add_option('shs_grocery_list_id', 'SHS-GroceryList-' . time());

    global $wpdb;

    // Add grocery item order table
    $table_name = $wpdb->prefix . 'grocery_order';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

        // Code to generate table
        $sql = "CREATE TABLE $table_name (
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

    // Add store table
    $table_name = $wpdb->prefix . 'stores';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

        // Code to generate table
        $sql = "CREATE TABLE $table_name (
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
function ingredients_to_tax_ids($ingredients) {

    // Translate names to ingredient taxonomy IDs
    $output = array();
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
function tax_ids_to_ingredients($indices) {

    // Translate ingredient taxonomy IDs to names
    $output = array();
    foreach ($indices as $i) {
        $object = get_term_by('id', $i, 'ingredient');
        $output[] = $object->name;
    }

    return $output;

}


/**
 * Convert ingredient unit name to proper index before saving to DB
 * @param  string $name    Ingredient unit name
 * @return string          Ingredient unit index
 */
function unit_name_to_index($name) {

    if (empty($name)) return false;

    $unit_list = array_flip(get_option('_ingredient_unit_list'));
    return $unit_list[$name];

}


/**
 * Convert ingredient unit index to name before rendering data from DB
 * @param  string $index    Ingredient unit index
 * @return string           Ingredient unit name
 */
function unit_index_to_name($index) {

    $unit_list = get_option('_ingredient_unit_list');
    return $unit_list[$index];

}


/**
 * Create shortcode to access grocery list object methods
 * @param  array $atts    Shortcode args passed in from admin page
 */
function grocery_list_shortcode($atts) {

    if (!is_admin()) {

        // Bail if no user logged in and shortcode public attribute not explicitly set to true
        if (!is_user_logged_in() && (isset($atts['public']) && $atts['public'] != true)) {
            return false;
        }

        the_groceries();

    }

}
add_shortcode('groceries', 'grocery_list_shortcode');


/**
 * Add grocery list and stores admin sidebar submenus
 */
function register_grocery_list_admin_pages() {

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
function the_groceries() {

    $grocery_store = new groceryStores();
    $grocery_store->render_store_dropdown();

    // Set the store id
    echo '<div id="store_id" rel="' . $grocery_store->id . '" style="display:none;"></div>';

    ?>

    <h2><?php $grocery_store->get_store_name( $grocery_store->id, true ); ?></h2>

    <div class="ingredients groceries">
        <ul id="slip_list">

            <?php

            // Get existing list items
            $current_list = new currentList();
            $current_list->render_groceries($grocery_store->id);

            ?>

        </ul>
    </div>

    <?php

}


/**
 * Render the form used to generate grocery lists
 */
function render_grocery_list_admin_form() {

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate current list object
    $current_list = new currentList();

    if (isset($_POST['submit'])) {

        $is_new_ingredient = $current_list->save_groceries();

        ?>

        <div class="updated"><p><strong>Grocery List Saved</strong></p></div>

        <?php

        if ( $is_new_ingredient ) {

            $current_url = site_url() . '/wp-admin/edit.php?post_type=recipe&page=grocery-list';
            ?>

            <div class="updated">
                <p><strong>New ingredient(s) added - please <a href="<?php echo $current_url; ?>">click here</a> before proceeding</strong></p>
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
                    <input type="checkbox" id="current_items_toggle_all" />
                    <label for="current_items_toggle_all">Current items</label>
                </h3>

                <ul id="current_items">

                    <?php
                    // Get existing list items
                    $current_list->existing_admin_list();
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
function render_grocery_stores_admin_form() {

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate grocery stores object
    $grocery_stores = new groceryStores();

    if (isset($_POST['submit'])) {

        $grocery_stores->save_stores();

        ?>

        <div class="updated"><p><strong>Grocery Stores Saved</strong></p></div>

    <?php } ?>

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

            <?php $grocery_stores->show_stores(); ?>

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


/**
 * Include grocery list classes
 */
function include_grocery_list_classes() {

    // Include classes
    $classes = array(
        'groceryList',
        'masterList',
        'currentList',
        'groceryStores',
        'sortList',
    );

    foreach ($classes as $obj => $class) {

        require_once 'class.' . $class . '.inc';

    }

}
add_action('init', 'include_grocery_list_classes');

?>