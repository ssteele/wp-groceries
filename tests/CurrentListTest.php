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

    public function testSaveGroceriesCurrentItemsOnly()
    {
        $userId = 1;
        $termIds = ['20', '21', '22', '23', '24'];
        $post = [
            'i' => $termIds,
            'submit' => 'Save Grocery List',
        ];

        // mock sanitizer
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->willReturn($termIds);

        // mock get_term
        $terms = [
            [
                'term_id'          => 20,
                'name'             => 'onion',
                'slug'             => 'onion',
                'term_group'       => 0,
                'term_taxonomy_id' => 20,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 10,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 21,
                'name'             => 'peas',
                'slug'             => 'peas',
                'term_group'       => 0,
                'term_taxonomy_id' => 21,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 1,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 22,
                'name'             => 'rice',
                'slug'             => 'rice',
                'term_group'       => 0,
                'term_taxonomy_id' => 22,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 1,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 23,
                'name'             => 'soy sauce',
                'slug'             => 'soy-sauce',
                'term_group'       => 0,
                'term_taxonomy_id' => 23,
                'taxonomy'         => 'ingredient',
                'description'      => '',
                'parent'           => 0,
                'count'            => 2,
                'filter'           => 'raw',
            ],
            [
                'term_id'          => 24,
                'name'             => 'squash',
                'slug'             => 'squash',
                'term_group'       => 0,
                'term_taxonomy_id' => 24,
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

        // mock maybe_serialize
        $groceries = [
            [
                'i' => 20,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 21,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 22,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 23,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 24,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
        ];

        $serialized = 'a:5:{i:0;a:6:{s:1:"i";i:20;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:1;a:6:{s:1:"i";i:21;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:2;a:6:{s:1:"i";i:22;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:3;a:6:{s:1:"i";i:23;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:4;a:6:{s:1:"i";i:24;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}}';


        WP_Mock::wpFunction('maybe_serialize', [
            'args' => [$groceries],
            'times' => 1,
            'return' => $serialized,
        ]);

        // mock update_user_meta
        WP_Mock::wpFunction('update_user_meta', [
            'args' => [$userId, '_grocery_list', $serialized],
            'times' => 1,
            'return' => true,
        ]);

        $currentList = new CurrentList($userId);
        $isNewIngredient = $currentList->saveGroceries($post, $sanitizerStub);
        $this->assertFalse($isNewIngredient);
    }

    public function testSaveGroceriesIngredientsOnly()
    {
        $userId = 1;
        $termIds = ['2', '3', '4', '5', '6'];
        $post = [
            'ingredient' => $termIds,
            'submit' => 'Save Grocery List',
        ];

        // mock sanitizer
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->willReturn($termIds);

        // mock get_term
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

        // mock maybe_serialize
        $groceries = [
            [
                'i' => 2,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 3,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 4,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 5,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
            [
                'i' => 6,
                'a' => '',
                'u' => false,
                't' => 'i',
                'o' => '',
                'p' => '',
            ],
        ];

        $serialized = 'a:5:{i:0;a:6:{s:1:"i";i:2;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:1;a:6:{s:1:"i";i:3;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:2;a:6:{s:1:"i";i:4;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:3;a:6:{s:1:"i";i:5;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}i:4;a:6:{s:1:"i";i:6;s:1:"a";s:0:"";s:1:"u";b:0;s:1:"t";s:1:"i";s:1:"o";s:0:"";s:1:"p";s:0:"";}}';

        WP_Mock::wpFunction('maybe_serialize', [
            'args' => [$groceries],
            'times' => 1,
            'return' => $serialized,
        ]);

        // mock update_user_meta
        WP_Mock::wpFunction('update_user_meta', [
            'args' => [$userId, '_grocery_list', $serialized],
            'times' => 1,
            'return' => true,
        ]);

        $currentList = new CurrentList($userId);
        $isNewIngredient = $currentList->saveGroceries($post, $sanitizerStub);
        $this->assertFalse($isNewIngredient);
    }
}
