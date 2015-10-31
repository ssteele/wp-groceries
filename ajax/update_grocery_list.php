<?php

// http://loc.harville-steele.com/content/plugins/grocery_list/ajax/update_grocery_list.php?s=3&a=26&i=23&b=29
// ...using above:below context here, not before:after

// Cut off direct access
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['s'] ) ) exit();

// Bootstrap into WP environment
require_once '../../../../wp-load.php';

$user_id = get_current_user_id();

if (!user_can($user_id, 'manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Grab the post variables
$post_vars = array( 's', 'a', 'i', 'b' );

foreach ($post_vars as $p) {

    if ( isset( $_POST[$p] ) ) {

        $$p = sanitize_input($_POST[$p], 's');
        $$p = ( is_string( $$p) ) ? intval( $$p ) : $$p;

    } else {
        die();
    }

}

require_once '../grocery_list.php';

$master_list = new masterList();
$master_list->update_store_order($s, $a, $i, $b);

die;

?>