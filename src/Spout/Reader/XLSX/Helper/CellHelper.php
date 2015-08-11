<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Common\Exception\InvalidArgumentException;

/**
 * Class CellHelper
 * This class provides helper functions when working with cells
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class CellHelper
{
    /**
     * Fills the missing indexes of an array with a given value.
     * For instance, $dataArray = []; $a[1] = 1; $a[3] = 3;
     * Calling fillMissingArrayIndexes($dataArray, 'FILL') will return this array: ['FILL', 1, 'FILL', 3]
     *
     * @param array $dataArray The array to fill
     * @param string|void $fillValue optional
     * @return array
     */
    public static function fillMissingArrayIndexes($dataArray, $fillValue = '')
    {
        $existingIndexes = array_keys($dataArray);

        $newIndexes = array_fill_keys(range(0, max($existingIndexes)), $fillValue);
        $dataArray += $newIndexes;

        ksort($dataArray);

        return $dataArray;
    }

    /**
     * Returns the base 10 column index associated to the cell index (base 26).
     * Excel uses A to Z letters for column indexing, where A is the 1st column,
     * Z is the 26th and AA is the 27th.
     * The mapping is zero based, so that A1 maps to 0, B2 maps to 1, Z13 to 25 and AA4 to 26.
     *
     * @param string $cellIndex The Excel cell index ('A1', 'BC13', ...)
     * @return int
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException When the given cell index is invalid
     */
    public static function getColumnIndexFromCellIndex($cellIndex)
    {
        if (!self::isValidCellIndex($cellIndex)) {
            throw new InvalidArgumentException('Cannot get column index from an invalid cell index.');
        }

        $columnIndex = 0;
        $capitalAAsciiValue = ord('A');
        $capitalZAsciiValue = ord('Z');
        $step = $capitalZAsciiValue - $capitalAAsciiValue + 1;

        // Remove row information
        $column = preg_replace('/\d/', '', $cellIndex);
        $columnLength = strlen($column);

        /*
         * This is how the following loop will process the data:
         * A   => 0
         * Z   => 25
         * AA  => 26   : (26^(2-1) * (0+1)) + 0
         * AB  => 27   : (26^(2-1) * (0+1)) + 1
         * BC  => 54   : (26^(2-1) * (1+1)) + 2
         * BCZ => 1455 : (26^(3-1) * (1+1)) + (26^(2-1) * (2+1)) + 25
         */
        foreach (str_split($column) as $single_cell_index)
        {
            $currentColumnIndex = ord($single_cell_index) - $capitalAAsciiValue;

            if ($columnLength === 1) {
                $columnIndex += $currentColumnIndex;
            } else {
                $columnIndex += pow($step, ($columnLength - 1)) * ($currentColumnIndex + 1);
            }

            $columnLength--;
        }

        return $columnIndex;
    }

    /**
     * Returns whether a cell index is valid, in an Excel world.
     * To be valid, the cell index should start with capital letters and be followed by numbers.
     *
     * @param string $cellIndex The Excel cell index ('A1', 'BC13', ...)
     * @return bool
     */
    protected static function isValidCellIndex($cellIndex)
    {
        return (preg_match('/^[A-Z]+\d+$/', $cellIndex) === 1);
    }
}
