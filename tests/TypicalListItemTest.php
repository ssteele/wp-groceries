<?php

namespace SteveSteele\GroceriesTest;

use SteveSteele\Groceries\TypicalListItem;

class TypicalListItemTest extends BaseTestCase
{
    public function testFetchStatus()
    {
        // setup
        $userId = 1;
        $typicalListItem = new TypicalListItem($userId);

        $item = [
            'id'         => '1',
            'term_id'    => '107',
            'is_typical' => '1',
        ];
        $item = (object) $item;

        // expected first store is returned
        $expected = '1';
        $returned = $typicalListItem->pluckIsTypical([$item]);      // note: item is wrapped inside array here
        $this->assertEquals($expected, $returned);
    }

    public function testFetchIds()
    {
        // setup
        $userId = 1;
        $typicalListItem = new TypicalListItem($userId);

        $typicalItems = [
            (object) [
                'id'         => '1',
                'term_id'    => '107',
                'is_typical' => '1',
            ],
            (object) [
                'id'         => '2',
                'term_id'    => '73',
                'is_typical' => '1',
            ],
            (object) [
                'id'         => '999',
                'term_id'    => '999',
                'is_typical' => '1',
            ],
        ];

        // expected first store is returned
        $expected = ['107', '73', '999'];
        $returned = $typicalListItem->pluckIds($typicalItems);
        $this->assertEquals($expected, $returned);
    }
}
