<?php

namespace SteveSteele\Groceries;

// http://shs.harvillesteele.com:8888/content/plugins/shs-grocery-list/src/ajax/select-favorite-store.php

$response = [
    'status'   => 'success',
    'message'  => '',
];

// cut off direct access
if ('POST' !== $_SERVER['REQUEST_METHOD'] || empty($_POST['store_id'])) {
    $response['status'] = 'invalid';
    echo json_encode($response);
    die();
}

// bootstrap into WP environment
require_once '../../../../../wp-load.php';

$userId = get_current_user_id();

if (! user_can($userId, 'manage_options')) {
    $response['status'] = 'error';
    $response['message'] = 'Error: You must log in to make updates';
    echo json_encode($response);
    die();
}

// grab the post variables
$postVars = ['store_id'];

foreach ($postVars as $p) {
    $$p = sanitize_input($_POST[$p], 's');
}

// isolate store ID
$storeId = preg_replace('/favorite_/', '', $store_id);

// save user favorite
$groceryStores = new GroceryStores($userId);
$groceryStores->saveFavoriteStore($storeId);

echo json_encode($response);
die;
