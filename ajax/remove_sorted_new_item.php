<?php

// http://loc.harville-steele.com/content/plugins/grocery_list/ajax/remove_sorted_new_item.php?id=98

// Cut off direct access
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['s'] ) || empty( $_POST['i'] ) ) exit();

// Bootstrap into WP environment
require_once '../../../../wp-load.php';

$user_id = get_current_user_id();

if (!user_can($user_id, 'manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Grab the post variables
$post_vars = array( 's', 'i' );

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
$master_list->remove_sorted_new_item( $s, $i );

die;

?>