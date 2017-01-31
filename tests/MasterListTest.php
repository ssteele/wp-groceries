<?php

namespace SteveSteele\Groceries;

class MasterListTest extends \PHPUnit_Framework_TestCase
{

    public function testHandleStoreItemReordering()
    {
        $userId = 1;
        $masterList = new MasterList($userId);
        $store = 1;
        $list = [1, 2, 3, 4, 5];

        // move middle to same position
        $expectedReorderedList = [1, 2, 3, 4, 5];
        $list = $masterList->handleStoreItemReordering($store, 2, 3, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move first to same position
        $expectedReorderedList = [1, 2, 3, 4, 5];
        $list = $masterList->handleStoreItemReordering($store, 0, 1, 2, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move last to same position
        $expectedReorderedList = [1, 2, 3, 4, 5];
        $list = $masterList->handleStoreItemReordering($store, 4, 5, 0, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item down one
        $expectedReorderedList = [1, 3, 2, 4, 5];
        $list = $masterList->handleStoreItemReordering($store, 3, 2, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item up one
        $expectedReorderedList = [1, 3, 4, 2, 5];
        $list = $masterList->handleStoreItemReordering($store, 3, 4, 2, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move first item down one
        $expectedReorderedList = [3, 1, 4, 2, 5];
        $list = $masterList->handleStoreItemReordering($store, 3, 1, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move last item up one
        $expectedReorderedList = [3, 1, 4, 5, 2];
        $list = $masterList->handleStoreItemReordering($store, 4, 5, 2, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item down two
        $expectedReorderedList = [3, 4, 5, 1, 2];
        $list = $masterList->handleStoreItemReordering($store, 5, 1, 2, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item up two
        $expectedReorderedList = [3, 1, 4, 5, 2];
        $list = $masterList->handleStoreItemReordering($store, 3, 1, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item to top
        $expectedReorderedList = [4, 3, 1, 5, 2];
        $list = $masterList->handleStoreItemReordering($store, 0, 4, 3, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item to bottom
        $expectedReorderedList = [4, 3, 5, 2, 1];
        $list = $masterList->handleStoreItemReordering($store, 2, 1, 0, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move middle item to top
        $expectedReorderedList = [5, 4, 3, 2, 1];
        $list = $masterList->handleStoreItemReordering($store, 0, 5, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move first item to bottom
        $expectedReorderedList = [4, 3, 2, 1, 5];
        $list = $masterList->handleStoreItemReordering($store, 1, 5, 0, $list);
        $this->assertEquals($expectedReorderedList, $list);

        // move last item to top
        $expectedReorderedList = [5, 4, 3, 2, 1];
        $list = $masterList->handleStoreItemReordering($store, 0, 5, 4, $list);
        $this->assertEquals($expectedReorderedList, $list);
    }
}
