<?php

// http://loc.harville-steele.com/content/plugins/grocery_list/ajax/select_favorite_store.php

// Cut off direct access
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['store_id'])) exit();

// Bootstrap into WP environment
require_once '../../../../wp-load.php';

$user_id = get_current_user_id();

if (!user_can($user_id, 'manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Grab the post variables
$post_vars = array('store_id');

foreach ($post_vars as $p) {

    if (isset($_POST[$p]) && !empty($_POST[$p])) {
        $$p = sanitize_input($_POST[$p], 's');
    }

}

// Isolate store ID
$store_id = preg_replace('/favorite_/', '', $store_id);

// Save user favorite
update_user_meta($user_id, '_favorite_store', $store_id);

die;

?>