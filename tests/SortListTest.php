<?php

namespace SteveSteele\Groceries;

class SortListTest extends \PHPUnit_Framework_TestCase
{

    public function testSort()
    {
        // create items
        $item1 = ['i' => 1];
        $item2 = ['i' => 2];
        $item3 = ['i' => 3];
        $item4 = ['i' => 4];
        $item5 = ['i' => 5];

        // create easy list
        $list = [1, 2, 3, 4, 5];
        $sortList = new SortList($list);

        // compare item1 with item1
        $expected = 0;
        $returned = $sortList->sort($item1, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item2
        $expected = -1;
        $returned = $sortList->sort($item1, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item3
        $expected = -1;
        $returned = $sortList->sort($item1, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item4
        $expected = -1;
        $returned = $sortList->sort($item1, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item5
        $expected = -1;
        $returned = $sortList->sort($item1, $item5, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item1
        $expected = 1;
        $returned = $sortList->sort($item2, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item2
        $expected = 0;
        $returned = $sortList->sort($item2, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item3
        $expected = -1;
        $returned = $sortList->sort($item2, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item4
        $expected = -1;
        $returned = $sortList->sort($item2, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item5
        $expected = -1;
        $returned = $sortList->sort($item2, $item5, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item1
        $expected = 1;
        $returned = $sortList->sort($item5, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item2
        $expected = 1;
        $returned = $sortList->sort($item5, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item3
        $expected = 1;
        $returned = $sortList->sort($item5, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item4
        $expected = 1;
        $returned = $sortList->sort($item5, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item5
        $expected = 0;
        $returned = $sortList->sort($item5, $item5, $list);
        $this->assertEquals($expected, $returned);

        // mix it up
        $list = [3, 4, 1, 5, 2];
        $sortList = new SortList($list);

        // compare item1 with item1
        $expected = 0;
        $returned = $sortList->sort($item1, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item2
        $expected = -1;
        $returned = $sortList->sort($item1, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item3
        $expected = 1;
        $returned = $sortList->sort($item1, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item4
        $expected = 1;
        $returned = $sortList->sort($item1, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item1 with item5
        $expected = -1;
        $returned = $sortList->sort($item1, $item5, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item1
        $expected = 1;
        $returned = $sortList->sort($item2, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item2
        $expected = 0;
        $returned = $sortList->sort($item2, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item3
        $expected = 1;
        $returned = $sortList->sort($item2, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item4
        $expected = 1;
        $returned = $sortList->sort($item2, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item2 with item5
        $expected = 1;
        $returned = $sortList->sort($item2, $item5, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item1
        $expected = 1;
        $returned = $sortList->sort($item5, $item1, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item2
        $expected = -1;
        $returned = $sortList->sort($item5, $item2, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item3
        $expected = 1;
        $returned = $sortList->sort($item5, $item3, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item4
        $expected = 1;
        $returned = $sortList->sort($item5, $item4, $list);
        $this->assertEquals($expected, $returned);

        // compare item5 with item5
        $expected = 0;
        $returned = $sortList->sort($item5, $item5, $list);
        $this->assertEquals($expected, $returned);
    }
}
