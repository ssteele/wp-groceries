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

        $termIds = ['2', '3', '4', '5', '6'];
        $post = [
            'ingredient' => $termIds,
            'submit' => 'Save Grocery List',
        ];

        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->willReturn($termIds);

        $terms = [
            [
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
            ],
            [
                'term_id'          => 3,
                'name'             => 'black beans',
                'slug'             => 'black-beans',
                'term_group'       => 0,
                'term_taxonomy_id' => 3,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 6,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 4,
                'name'             => 'green onion',
                'slug'             => 'green-onion',
                'term_group'       => 0,
                'term_taxonomy_id' => 4,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 4,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 5,
                'name'             => 'spinach',
                'slug'             => 'spinach',
                'term_group'       => 0,
                'term_taxonomy_id' => 5,
                'taxonomy'         => 'ingredient',
                'description'      => 'organic',
                'parent'           => 0,
                'count'            => 4,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 6,
                'name'             => 'sweet potato',
                'slug'             => 'sweet-potato',
                'term_group'       => 0,
                'term_taxonomy_id' => 6,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 1,
                'filter'           => 'raw',
            ],
        ];

        foreach ($terms as $term) {
            WP_Mock::wpFunction('get_term', [
                'args' => [$term['term_id'], 'ingredient', OBJECT],
                'times' => 1,
                'return' => (object) $term,
            ]);
        }

        $groceries = [
            [
                'i' => 2,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
            [
                'i' => 3,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
            [
                'i' => 4,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
            [
                'i' => 5,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
            [
                'i' => 6,
                'a' => 0,
                'u' => 0,
                't' => 'i',
                'o' => '',
            ],
        ];
        $serialized = 'a:5:{i:0;a:5:{s:1:"i";i:2;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}i:1;a:5:{s:1:"i";i:3;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}i:2;a:5:{s:1:"i";i:4;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}i:3;a:5:{s:1:"i";i:5;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}i:4;a:5:{s:1:"i";i:6;s:1:"a";i:0;s:1:"u";i:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";}}';

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
