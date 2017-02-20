<?php

namespace SteveSteele\Groceries;

use SteveSteele\Sanitizer;
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

    public function testSaveGroceriesIngredientsOnly()
    {
        $userId = 1;
        $currentList = new CurrentList($userId);

        $termId = '2';
        $post = [
            'ingredient' => [
                0 => $termId,
            ],
            'submit' => 'Save Grocery List',
        ];

        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->willReturn($termId);

        $term = [
            'term_id'          => 2,
            'name'             => 'avocado',
            'slug'             => 'avocado',
            'term_group'       => 0,
            'term_taxonomy_id' => 2,
            'taxonomy'         => 'ingredient',
            'description'      => '',
            'parent'           => 0,
            'count'            => 4,
            'filter'           => 'raw',
        ];
        $term = (object) $term;

        WP_Mock::wpFunction('get_term', [
            'args' => [$termId, 'ingredient', OBJECT],
            'times' => 1,
            'return' => $term,
        ]);

        $groceries = [
            [
                'i' => 2,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
        ];
        $serialized = 'a:1:{i:0;a:5:{s:1:"i";i:2;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}}';
        WP_Mock::wpFunction('maybe_serialize', [
            'args' => [$groceries],
            'times' => 1,
            'return' => $serialized,
        ]);

        WP_Mock::wpFunction('update_user_meta', [
            'args' => [$userId, '_grocery_list', $serialized],
            'times' => 1,
            'return' => true,
        ]);

        $isNewIngredient = $currentList->saveGroceries($post, $sanitizerStub);
    }
}
