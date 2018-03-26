<?php

namespace SteveSteele\GroceriesTest;

use SteveSteele\Groceries\IngredientTranslator;
use WP_Mock;

class IngredientTranslatorTest extends BaseTestCase
{
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
        WP_Mock::userFunction('get_option', [
            'args' => '_ingredient_unit_list',
            'times' => 3,
            'return' => $ingredientUnitList,
        ]);

        $ingredientTranslator = new IngredientTranslator();

        $unitName = 'spears';
        $expectedIndex = 13;
        $unitIndex = $ingredientTranslator->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);

        $unitName = 'cloves';
        $expectedIndex = 3;
        $unitIndex = $ingredientTranslator->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);

        $unitName = 'small';
        $expectedIndex = 12;
        $unitIndex = $ingredientTranslator->unitNameToIndex($unitName);
        $this->assertEquals($expectedIndex, $unitIndex);
    }

    public function testIndexToUnitName()
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
        WP_Mock::userFunction('get_option', [
            'args' => '_ingredient_unit_list',
            'times' => 3,
            'return' => $ingredientUnitList,
        ]);

        $ingredientTranslator = new IngredientTranslator();

        $unitIndex = 13;
        $expectedName = 'spears';
        $unitName = $ingredientTranslator->indexToUnitName($unitIndex);
        $this->assertEquals($expectedName, $unitName);

        $unitIndex = 3;
        $expectedName = 'cloves';
        $unitName = $ingredientTranslator->indexToUnitName($unitIndex);
        $this->assertEquals($expectedName, $unitName);

        $unitIndex = 12;
        $expectedName = 'small';
        $unitName = $ingredientTranslator->indexToUnitName($unitIndex);
        $this->assertEquals($expectedName, $unitName);
    }
}
