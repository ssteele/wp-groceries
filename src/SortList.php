<?php

namespace SteveSteele\Groceries;

class SortList
{

    // Declare properties
    public $masterStoreList;


    public function __construct($masterStoreList)
    {
        $this->masterStoreList = $masterStoreList;
    }


    /**
     * Sort current grocery list wrapper
     * @return int  0 if $a == $b, -1 if $a < $b or +1 if $a > $b
     */
    public function sort($a, $b)
    {
        return $this->orderCurrentList($a, $b, $this->masterStoreList);
    }


    /**
     * Sort current grocery list by aisle (from master list)
     * @return int  0 if $a == $b, -1 if $a < $b or +1 if $a > $b
     */
    private function orderCurrentList($a, $b, $masterStoreList)
    {
        $aKey = array_search($a['i'], $masterStoreList);
        $bKey = array_search($b['i'], $masterStoreList);

        // If element not found in sort array then assume it should appear at the end
        if ($aKey === false) {
            return 1;
        } elseif ($bKey === false) {
            return -1;
        }

        // Both elements found in sort array - determine order
        if ($aKey == $bKey) {
            return 0;
        }

        return ($aKey < $bKey) ? -1 : 1;
    }
}
