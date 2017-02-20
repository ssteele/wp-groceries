<?php

namespace SteveSteele\Groceries;

class Ingredients
{

    // Declare properties


    /**
     * Convert a list of readable ingredients to WP taxonomy indices
     * @param  array $ingredients    Readable ingredients list
     * @return array                 Ingredient indices
     */
    public function toTaxIds($ingredients)
    {
        // Translate names to ingredient taxonomy IDs
        $output = [];
        foreach ($ingredients as $name) {
            $object = get_term_by('name', $name, 'ingredient');
            $output[] = $object->term_id;
        }

        return $output;
    }


    /**
     * Convert a list of WP taxonomy indices to readable ingredients
     * @param  array $indices    Ingredient indices
     * @return array             Readable ingredients list
     */
    public function fromTaxIds($indices)
    {
        // Translate ingredient taxonomy IDs to names
        $output = [];
        foreach ($indices as $i) {
            $object = get_term_by('id', $i, 'ingredient');

            if ($object) {
                $output[] = $object->name;
            }
        }

        return $output;
    }


    /**
     * Convert ingredient unit name to proper index before saving to DB
     * @param  string $name    Ingredient unit name
     * @return string          Ingredient unit index
     */
    public function unitNameToIndex($name)
    {
        if (empty($name)) {
            return false;
        }

        $unitList = array_flip(get_option('_ingredient_unit_list'));
        return $unitList[$name];
    }


    /**
     * Convert ingredient unit index to name before rendering data from DB
     * @param  string $index    Ingredient unit index
     * @return string           Ingredient unit name
     */
    public function unitIndexToName($index)
    {
        $unitList = get_option('_ingredient_unit_list');
        return $unitList[$index];
    }
}
