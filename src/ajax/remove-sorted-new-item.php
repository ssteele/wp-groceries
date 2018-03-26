<?php

namespace SteveSteele\Groceries;

use SteveSteele\TypeSanity\UserInput;

// http://shs.harvillesteele.com:8888/content/plugins/shs-grocery-list/src/ajax/remove-sorted-new-item.php?id=98

$response = [
    'status'   => 'success',
    'message'  => '',
];

// Cut off direct access
if ('POST' !== $_SERVER['REQUEST_METHOD'] || empty($_POST['s']) || empty($_POST['i'])) {
    $response['status'] = 'invalid';
    echo json_encode($response);
    die();
}

// Bootstrap into WP environment
require_once '../../../../../wp-load.php';

$userId = get_current_user_id();

if (! user_can($userId, GROCERY_LIST_CAPABILITY)) {
    $response['status'] = 'error';
    $response['message'] = 'Error: You must log in to make updates';
    echo json_encode($response);
    die();
}

$translator = new UserInput();

// Grab the post variables
$postVars = ['s', 'i'];

foreach ($postVars as $p) {
    $$p = $translator->sanitize($_POST[$p], 's');
    $$p = (is_string($$p)) ? intval($$p) : $$p;
}

require_once '../../index.php';

$masterList = new MasterList();
$masterList->removeSortedNewItem($s, $i);

echo json_encode($response);
die;
