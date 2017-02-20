<?php

namespace SteveSteele\Groceries;

class TypicalListItemTest extends \PHPUnit_Framework_TestCase
{

    public function testFetchStatus()
    {
        // setup
        $userId = 1;
        $typicalListItem = new TypicalListItem($userId);

        $item = [
            'id'                => '1',
            'term_id'           => '107',
            'typical_list_item' => '1',
        ];
        $item = (object) $item;

        // expected first store is returned
        $expected = '1';
        $returned = $typicalListItem->fetchStatus([$item]);         // note: item is wrapped inside array here
        $this->assertEquals($expected, $returned);
    }

    public function testFetchIds()
    {
        // setup
        $userId = 1;
        $typicalListItem = new TypicalListItem($userId);

        $typicalItems = [
            (object) [
                'id'                => '1',
                'term_id'           => '107',
                'typical_list_item' => '1',
            ],
            (object) [
                'id'                => '2',
                'term_id'           => '73',
                'typical_list_item' => '1',
            ],
            (object) [
                'id'                => '999',
                'term_id'           => '999',
                'typical_list_item' => '1',
            ],
        ];

        // expected first store is returned
        $expected = ['107', '73', '999'];
        $returned = $typicalListItem->fetchIds($typicalItems);
        $this->assertEquals($expected, $returned);
    }
}
