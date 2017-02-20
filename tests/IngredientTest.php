<?php

namespace SteveSteele\Groceries;

use WP_Mock;

class IngredientTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        WP_Mock::setUp();
    }

    public function tearDown()
    {
        WP_Mock::tearDown();
    }

    public function testUnitNameToIndex()
    {
        // mock ingredient unit list
        $ingredientUnitList = [
            '',
            'a',
            'an',
            'cloves',
            'cups',
            'heads',
            'large',
            'medium',
            'ounces',
            'packages',
            'pinches',
            'pounds',
            'small',
            'spears',
            'squeezes',
            'tablespoons',
            'teaspoons',
        ];
        WP_Mock::wpFunction('get_option', [
            'args' => '_ingredient_unit_list',
            'times' => 3,
            'return' => $ingredientUnitList,
        ]);

        $ingredients = new Ingredients();

        $unitName = 'spears';
        $expectedIndex = 13;
        $unitIndex = $ingredients->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);

        $unitName = 'cloves';
        $expectedIndex = 3;
        $unitIndex = $ingredients->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);

        $unitName = 'small';
        $expectedIndex = 12;
        $unitIndex = $ingredients->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);
    }
}
