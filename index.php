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

use SteveSteele\Groceries\TypicalListItem;
use SteveSteele\Groceries\UnavailableStoreItem;
use SteveSteele\Groceries\GroceryStore;
use SteveSteele\Groceries\CurrentList;
use SteveSteele\TypeSanity\UserInput;

require_once 'vendor/autoload.php';

const SHS_GROCERY_LIST_PLUGIN_NAME = 'SHS Grocery List';

// https://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table
const GROCERY_LIST_CAPABILITY = 'read';                             // everyone can, no one cannot (previously 'manage_options')
const INGREDIENT_TAG_CREATE_CAPABILITY = 'manage_categories';       // editor's can, authors cannot


/**
 * Install the plugin and create a DB table
 */
function shs_grocery_list_install()
{
    areGroceryListDependenciesEnabled(false);

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

    // Add table to reset a grocery list with typical items (specified on taxonomy term page)
    $tableName = $wpdb->prefix . 'term_taxonomy_typical';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
        $sql = "CREATE TABLE $tableName (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        is_typical TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY term_id (term_id)
        );";

        // Import a file we need to call dbDelta function
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Run the SQL
        dbDelta($sql);
    }

    // Add table to flag items that are unavailable in a user's store
    $tableName = $wpdb->prefix . 'term_taxonomy_unavailable';

    if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
        $sql = "CREATE TABLE $tableName (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        store_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
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
 * Determine if plugin dependencies are enabled
 * @param  bool $allowPluginManagement    If true, allows requests to plugin management admin page
 * @return bool                           True if all dependencies are enabled; False otherwise
 */
function areGroceryListDependenciesEnabled($allowPluginManagement = true)
{
    // Specify plugin dependencies and function to check if existing
    $dependencies = [
        'SHS Recipes'  => 'shs_save_recipe',
    ];

    // Get current URL
    $currentUrl = site_url() . $_SERVER['REQUEST_URI'];

    // Get plugin admin page URL
    $pluginsPageUrl = admin_url('plugins.php');

    // Determine if user is requesting plugin admin page URL
    $isRequestingPluginsPage = preg_match("|$pluginsPageUrl|", $currentUrl);

    foreach ($dependencies as $plugin => $function) {
        if (! function_exists($function) && ! ($allowPluginManagement && $isRequestingPluginsPage)) {
            $message = '`' . SHS_GROCERY_LIST_PLUGIN_NAME . '` depends on functionality from the `' . $plugin . '` plugin.<br /><br />';
            if ($allowPluginManagement) {
                $message .= '<a href="' . $pluginsPageUrl . '">Click here</a> to enable `' . $plugin . '`.';
            }
            wp_die($message);
        }
    }
}


/**
 * Create shortcode to access grocery list object methods
 * @param  array $atts    Shortcode args passed in from admin page
 */
function grocery_list_shortcode($atts)
{
    if (! is_admin()) {
        $primaryUserIsPublic = $atts['public'] ?? false;
        if (is_user_logged_in() || $primaryUserIsPublic) {
            the_groceries();
        } else {
            $welcome = get_page_by_path('welcome', OBJECT, 'page');
            if ($welcome) {
                echo $welcome->post_content;
            }
        }
    }
}
add_shortcode('groceries', 'grocery_list_shortcode');


/**
 * Save typical grocery list item status
 *
 * @param  integer  $userId    WP user id
 * @param  integer  $termId    Taxonomy term ID
 *
 * @return void
 */
function save_typical_list_item_status($userId = null, $termId = null)
{
    if ($userId && $termId) {
        $isTypical = isset($_POST['is_typical']) ? 1 : 0;
        $typicalListItem = new TypicalListItem($userId);
        $typicalListItem->save($termId, $isTypical);
    }
}


/**
 * Get typical list item status
 *
 * @param  integer  $userId    WP user id
 * @param  string   $termId    Taxonomy term ID
 *
 * @return string            1 if item is typical, 0 otherwise
 */
function get_typical_list_item_status($userId = null, $termId = null)
{
    if ($userId && $termId) {
        $typicalListItem = new TypicalListItem($userId);
        return $typicalListItem->getIsTypical($termId);
    }
}


/**
 * Get typical list items
 * @param integer $userId    WP user id
 *
 * @return array             Filled with typical taxonomy DB objects
 */
function get_typical_list_item_ids($userId = null)
{
    if ($userId) {
        $typicalListItem = new TypicalListItem($userId);
        return $typicalListItem->getIds();
    }
}


/**
 * Save store IDs where item is unavailable
 *
 * @param  integer  $userId    WP user id
 * @param  integer  $termId    Taxonomy term ID
 *
 * @return void
 */
function save_store_ids_where_item_unavailable($userId = null, $termId = null)
{
    if ($userId && $termId) {
        $storeIds = (isset($_POST['is_unavailable'])) ? $_POST['is_unavailable'] : [];
        $unavailableStoreItem = new UnavailableStoreItem($userId);
        $unavailableStoreItem->save($termId, $storeIds);
    }
}


/**
 * Get store IDs where item is unavailable
 *
 * @param  integer  $userId    WP user id
 * @param  string   $termId    Taxonomy term ID
 *
 * @return array               Store IDs where item is unavailable
 */
function get_store_ids_where_item_unavailable($userId = null, $termId = null)
{
    if ($userId && $termId) {
        $unavailableStoreItem = new UnavailableStoreItem($userId);
        return $unavailableStoreItem->getStoreIds($termId);
    }
}


/**
 * Get unavailable items for a store
 * @param integer $storeId    Store ID
 *
 * @return array              Item IDs not available in store
 */
function get_unavailable_item_ids_for_store($userId = null, $storeId = null)
{
    if ($storeId) {
        $unavailableStoreItem = new UnavailableStoreItem($userId);
        return $unavailableStoreItem->getItemIds($storeId);
    }
}


/**
 * Add grocery list and stores admin sidebar submenus
 */
function register_grocery_list_admin_pages()
{
    areGroceryListDependenciesEnabled();

    add_submenu_page(
        'edit.php?post_type=recipe',
        'Manage Grocery Stores',
        'Grocery Stores',
        GROCERY_LIST_CAPABILITY,
        'grocery-stores',
        'render_grocery_stores_admin_form'
    );

    add_submenu_page(
        'edit.php?post_type=recipe',
        'Create a Grocery List',
        'Grocery List',
        GROCERY_LIST_CAPABILITY,
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
    $groceryStore = new GroceryStore();

    if ($groceryStore->exists()) {
        echo $groceryStore->renderStoreDropdown();

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
                $userInput = new UserInput();
                echo $currentList->renderGroceries($groceryStore->id, $userInput);

                ?>

            </ul>
        </div>

    <?php
    } else {
        echo '<div>';
        echo '    You have no saved stores.';
        echo '    <a href="' . get_admin_url() . 'edit.php?post_type=recipe&page=grocery-stores" title="Create your stores here">Create your stores here.</a>';
        echo '</div>';
    }
}


/**
 * Render the form used to generate grocery lists
 */
function render_grocery_list_admin_form()
{
    if (! current_user_can(GROCERY_LIST_CAPABILITY)) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate current list object
    $currentList = new CurrentList();

    if (isset($_POST['submit'])) {
        $userInput = new UserInput();
        $isNewIngredient = $currentList->saveGroceries($_POST, $userInput);

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

            <?php if (current_user_can(INGREDIENT_TAG_CREATE_CAPABILITY)) { ?>
                    <h3>
                        <input type="checkbox" id="typical_items_toggle" name="typical_items_toggle" value="1" />
                        <label for="typical_items_toggle">Typical items</label>
                    </h3>
            <?php } ?>

                <h3>
                    <input type="checkbox" id="current_items_toggle_all" />
                    <label for="current_items_toggle_all">Current items</label>
                </h3>

                <ul id="current_items">

                    <?php
                    // Get existing list items
                    echo $currentList->existingAdminList();
                    ?>

                </ul>

            </div>

            <div class="clearfix"></div>

            <div id="grocery-list-footer">
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Grocery List" />
                </p>
                <p class="current-list">The current list can be found <a href="<?php echo site_url(); ?>/grocery-list/">here</a></p>
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
    if (! current_user_can(GROCERY_LIST_CAPABILITY)) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Instantiate grocery stores object
    $groceryStore = new GroceryStore();

    if (isset($_POST['submit'])) {
        $groceryStore->saveStores();
        echo '<div class="updated"><p><strong>Grocery Stores Saved</strong></p></div>';
    }

    ?>

    <div class="wrap grocery-stores">

        <h2>Manage Grocery Stores</h2>

        <form name="grocery_stores" method="post" action="#" enctype="multipart/form-data" autocomplete="off">

            <div class="list-box new">
                <div class="store-input">
                    <input type="text" name="name" id="name" class="half" placeholder="Store Name*" />
                    <input type="text" name="number" id="number" class="half" placeholder="Store Number" />
                    <input type="text" name="street" id="street" class="full" placeholder="Street" />
                    <input type="text" name="city" id="city" class="half" placeholder="City" />
                    <input type="text" name="zip" id="zip" class="half" placeholder="Zip" />
                </div>
            </div>

            <?php echo $groceryStore->showStores(); ?>

            <div class="clearfix"></div>

            <div id="grocery-list-footer">
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Grocery Stores" />
                </p>
            </div>

        </form>

    <?php

}
