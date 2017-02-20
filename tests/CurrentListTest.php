<?php

namespace SteveSteele\Groceries;

use WP_Mock;

class CurrentListTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        WP_Mock::setUp();
    }

    public function tearDown()
    {
        WP_Mock::tearDown();
    }

    public function testRenderExistingAdminList()
    {
        $userId = 1;
        $termId = 107;

        // create items
        $groceries = [
            [
                'i' => $termId,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
        ];

        $term = new \stdClass();
        $term->name = 'apple';
        WP_Mock::wpFunction('get_term_by', [
            'args' => ['id', $termId, 'ingredient'],
            'times' => 1,
            'return' => $term,
        ]);

        $currentList = new CurrentList($userId);

        $expected = '<li><input type="checkbox" name="i[]" id="' . $termId . '" value="' . $termId . '" /><label for="' . $termId . '"> apple</label></li>';
        $markup = $currentList->renderExistingAdminList($groceries);
        $this->assertEquals($expected, $markup);
    }
}
