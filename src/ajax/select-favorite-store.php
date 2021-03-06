<?php

namespace SteveSteele\Groceries;

use SteveSteele\TypeSanity\UserInput;

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

if (! user_can($userId, GROCERY_LIST_CAPABILITY)) {
    $response['status'] = 'error';
    $response['message'] = 'Error: You must log in to make updates';
    echo json_encode($response);
    die();
}

$translator = new UserInput();

// grab the post variables
$postVars = ['store_id'];

foreach ($postVars as $p) {
    $$p = $translator->sanitize($_POST[$p], 's');
}

// isolate store ID
$storeId = preg_replace('/favorite_/', '', $store_id);

// save user favorite
$groceryStore = new GroceryStore($userId);
$groceryStore->saveFavoriteStore($storeId);

echo json_encode($response);
die;
