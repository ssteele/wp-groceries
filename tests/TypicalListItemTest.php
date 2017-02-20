<?php

namespace SteveSteele\Groceries;

class TypicalListItemTest extends \PHPUnit_Framework_TestCase
{

    public function testFetchStatus()
    {
        // setup
        $typicalListItem = new TypicalListItem();

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
}
