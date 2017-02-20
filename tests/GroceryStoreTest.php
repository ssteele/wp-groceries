<?php

namespace SteveSteele\Groceries;

class GroceryStoreTest extends \PHPUnit_Framework_TestCase
{

    public function testIsExistingStore()
    {
        // setup
        $userId = 1;
        $groceryStore = new GroceryStore($userId);

        $stores = [
            (object) [
                'id' => '16',
                'user_id' => '1',
                'name' => 'Lorem Ipsum',
                'number' => '90210',
                'street' => '123 Blah Blah',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78753',
            ],
            (object) [
                'id' => '17',
                'user_id' => '1',
                'name' => 'Dolor',
                'number' => '19293',
                'street' => '456 Bip Bop',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78753',
            ],
            (object) [
                'id' => '22',
                'user_id' => '1',
                'name' => 'TEST STORE',
                'number' => '99999',
                'street' => 'Blah',
                'city' => 'Blah',
                'state' => 'TX',
                'zip' => '99999',
            ],
        ];

        // store exists
        $storeId = 16;
        $expected = true;
        $returned = $groceryStore->isExistingStore($stores, $storeId);
        $this->assertEquals($expected, $returned);

        // store does not exist
        $storeId = 10;
        $expected = false;
        $returned = $groceryStore->isExistingStore($stores, $storeId);
        $this->assertEquals($expected, $returned);
    }

    public function testGetFirstStore()
    {
        // setup
        $userId = 1;
        $groceryStore = new GroceryStore($userId);

        $stores = [
            (object) [
                'id' => '16',
                'user_id' => '1',
                'name' => 'Lorem Ipsum',
                'number' => '90210',
                'street' => '123 Blah Blah',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78753',
            ],
            (object) [
                'id' => '17',
                'user_id' => '1',
                'name' => 'Dolor',
                'number' => '19293',
                'street' => '456 Bip Bop',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78753',
            ],
            (object) [
                'id' => '22',
                'user_id' => '1',
                'name' => 'TEST STORE',
                'number' => '99999',
                'street' => 'Blah',
                'city' => 'Blah',
                'state' => 'TX',
                'zip' => '99999',
            ],
        ];

        // expected first store is returned
        $expected = 16;
        $returned = $groceryStore->getFirstStore($stores);
        $this->assertEquals($expected, $returned);
    }
}
