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

    public function testExtractSaveGroceries()
    {
        $userId = 1;
        $currentList = new CurrentList($userId);

        // item from previous list
        $currentItemInput = ['2'];
        $post = [
            'i' => $currentItemInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // items from previous list
        $currentItemInput = ['2', '3', '4'];
        $post = [
            'i' => $currentItemInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // ingredient
        $ingredientInput = ['2'];
        $post = [
            'ingredient' => $ingredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // ingredients
        $ingredientInput = ['2', '3', '4'];
        $post = [
            'ingredient' => $ingredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // recipe
        $recipeInput = ['2'];
        $post = [
            'recipe' => $recipeInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEquals($recipes, $recipeInput);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // recipes
        $recipeInput = ['2', '3', '4'];
        $post = [
            'recipe' => $recipeInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEquals($recipes, $recipeInput);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // new ingredient
        $newIngredientInput = ['new ingredient'];
        $post = [
            'new_ingredient' => $newIngredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEquals($newIngredients, $newIngredientInput);
        $this->assertEmpty($typicalItems);

        // new ingredients
        $newIngredientInput = ['new ingredient', 'other new ingredient', 'other other new ingredient'];
        $post = [
            'new_ingredient' => $newIngredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEquals($newIngredients, $newIngredientInput);
        $this->assertEmpty($typicalItems);

        // typical ingredient
        $typicalItemSaved = ['2'];
        $post = [
            'typical_items_toggle' => '1',
            'submit' => 'Save Grocery List',
        ];
        WP_Mock::wpFunction('get_typical_list_item_ids', [
            'args' => [$userId],
            'times' => 1,
            'return' => $typicalItemSaved,
        ]);

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEquals($typicalItems, $typicalItemSaved);

        // typical ingredients
        $typicalItemSaved = ['2, 3, 4'];
        $post = [
            'typical_items_toggle' => '1',
            'submit' => 'Save Grocery List',
        ];
        WP_Mock::wpFunction('get_typical_list_item_ids', [
            'args' => [$userId],
            'times' => 1,
            'return' => $typicalItemSaved,
        ]);

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEmpty($currentItems);
        $this->assertEmpty($recipes);
        $this->assertEmpty($ingredients);
        $this->assertEmpty($newIngredients);
        $this->assertEquals($typicalItems, $typicalItemSaved);

        // items from previous list
        //  & ingredients
        $currentItemInput = ['2', '3', '4'];
        $ingredientInput = ['5', '6', '7'];
        $post = [
            'i' => $currentItemInput,
            'ingredient' => $ingredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEmpty($recipes);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // items from previous list
        //  & ingredients
        //  & recipes
        $currentItemInput = ['2', '3', '4'];
        $ingredientInput = ['5', '6', '7'];
        $recipesInput = ['8', '9', '10'];
        $post = [
            'i' => $currentItemInput,
            'ingredient' => $ingredientInput,
            'recipe' => $recipeInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEquals($recipes, $recipeInput);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEmpty($newIngredients);
        $this->assertEmpty($typicalItems);

        // items from previous list
        //  & ingredients
        //  & recipes
        //  & new ingredients
        $currentItemInput = ['2', '3', '4'];
        $ingredientInput = ['5', '6', '7'];
        $recipesInput = ['8', '9', '10'];
        $newIngredientInput = ['new ingredient', 'other new ingredient', 'other other new ingredient'];
        $post = [
            'i' => $currentItemInput,
            'ingredient' => $ingredientInput,
            'recipe' => $recipeInput,
            'new_ingredient' => $newIngredientInput,
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEquals($recipes, $recipeInput);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEquals($newIngredients, $newIngredientInput);
        $this->assertEmpty($typicalItems);

        // items from previous list
        //  & ingredients
        //  & recipes
        //  & new ingredients
        //  & typical ingredients
        $currentItemInput = ['2', '3', '4'];
        $ingredientInput = ['5', '6', '7'];
        $recipesInput = ['8', '9', '10'];
        $newIngredientInput = ['new ingredient', 'other new ingredient', 'other other new ingredient'];
        $typicalItemSaved = ['11, 12, 13'];
        $post = [
            'i' => $currentItemInput,
            'ingredient' => $ingredientInput,
            'recipe' => $recipeInput,
            'new_ingredient' => $newIngredientInput,
            'typical_items_toggle' => '1',
            'submit' => 'Save Grocery List',
        ];
        $sanitizerStub = $this->createMock(Sanitizer::class);
        $sanitizerStub->method('sanitize')
            ->will($this->returnArgument(0));
        WP_Mock::wpFunction('get_typical_list_item_ids', [
            'args' => [$userId],
            'times' => 1,
            'return' => $typicalItemSaved,
        ]);

        list($currentItems, $recipes, $ingredients, $newIngredients, $typicalItems) = $currentList->extractSaveGroceries($post, $sanitizerStub);
        $this->assertEquals($currentItems, $currentItemInput);
        $this->assertEquals($recipes, $recipeInput);
        $this->assertEquals($ingredients, $ingredientInput);
        $this->assertEquals($newIngredients, $newIngredientInput);
        $this->assertEquals($typicalItems, $typicalItemSaved);
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
