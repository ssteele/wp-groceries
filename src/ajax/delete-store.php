<?php

namespace SteveSteele\Groceries;

// http://shs.harvillesteele.com:8888/content/plugins/shs-grocery-list/src/ajax/delete-store.php

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

if (! user_can($userId, GROCERY_LIST_CAPABILITY)) {
    $response['status'] = 'error';
    $response['message'] = 'Error: You must log in to make updates';
    echo json_encode($response);
    die();
}

// grab the post variables
$postVars = ['store_id'];

foreach ($postVars as $p) {
    $$p = shsSanitize($_POST[$p], 's');
}

// isolate store ID
$storeId = preg_replace('/delete_/', '', $store_id);

// delete store
$groceryStore = new GroceryStore($userId);
$groceryStore->deleteStore($storeId);

echo json_encode($response);
die;
