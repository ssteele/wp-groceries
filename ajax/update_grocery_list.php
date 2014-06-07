<?php

// http://loc.harville-steele.com/content/plugins/grocery_list/ajax/update_grocery_list.php?store_id=1&a=16&b=25

// Cut off direct access
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['store_id'])) exit();

// Bootstrap into WP environment
require_once '../../../../wp-load.php';

$user_id = get_current_user_id();

if (!user_can($user_id, 'manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Grab the post variables
$post_vars = array('store_id', 'moved_above', 'index_below');

foreach ($post_vars as $p) {

    if (isset($_POST[$p]) && !empty($_POST[$p])) {
        $$p = sanitize_input($_POST[$p], 'i');
    }

}

// If no index below (item moved below all else), set $index_below to 0
$index_below = (!empty($index_below)) ? $index_below : 0;

require_once '../grocery_list.php';

$master_list = new masterList();
$master_list->update_store_order($store_id, $moved_above, $index_below);

die;

?>