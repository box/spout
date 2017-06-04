<?php

namespace Box\Spout\Writer\XLSX\Helper;

/**
 * Class SizeCollectionTest
 * Integration tests.
 */
class SizeCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetShouldReturnArrayWithAsciiCharacterSizes()
    {
        $collection = new SizeCollection();
        self::assertGreaterThan(200, count($collection->get('Arial', '12')));
    }

    public function testGetWithBiggerFontSizeShouldReturnArrayWithGreaterSum()
    {
        $collection = new SizeCollection();
        self::assertGreaterThan(
            array_sum($collection->get('Arial', '12')),
            array_sum($collection->get('Arial', '13'))
        );
    }

    public function testNonExistingFontShouldStillReturnArrayWithSizes()
    {
        $collection = new SizeCollection();
        self::assertGreaterThan(200, count($collection->get('MeNoFont', '12')));
    }
}
