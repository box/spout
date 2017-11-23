<?php

namespace Box\Spout\Common\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class CellTypeHelperTest
 */
class CellTypeHelperTest extends TestCase
{
    /**
     * @return array
     */
    public function testIsEmpty()
    {
        $this->assertTrue(CellTypeHelper::isEmpty(null));
        $this->assertTrue(CellTypeHelper::isEmpty(''));

        $this->assertFalse(CellTypeHelper::isEmpty('string'));
        $this->assertFalse(CellTypeHelper::isEmpty(0));
        $this->assertFalse(CellTypeHelper::isEmpty(1));
        $this->assertFalse(CellTypeHelper::isEmpty(true));
        $this->assertFalse(CellTypeHelper::isEmpty(false));
        $this->assertFalse(CellTypeHelper::isEmpty(['string']));
        $this->assertFalse(CellTypeHelper::isEmpty(new \stdClass()));
    }

    /**
     * @return array
     */
    public function testIsNonEmptyString()
    {
        $this->assertTrue(CellTypeHelper::isNonEmptyString('string'));

        $this->assertFalse(CellTypeHelper::isNonEmptyString(''));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(0));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(1));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(true));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(false));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(['string']));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(new \stdClass()));
        $this->assertFalse(CellTypeHelper::isNonEmptyString(null));
    }

    /**
     * @return array
     */
    public function testIsNumeric()
    {
        $this->assertTrue(CellTypeHelper::isNumeric(0));
        $this->assertTrue(CellTypeHelper::isNumeric(10));
        $this->assertTrue(CellTypeHelper::isNumeric(10.1));
        $this->assertTrue(CellTypeHelper::isNumeric(10.10000000000000000000001));
        $this->assertTrue(CellTypeHelper::isNumeric(0x539));
        $this->assertTrue(CellTypeHelper::isNumeric(02471));
        $this->assertTrue(CellTypeHelper::isNumeric(0b10100111001));
        $this->assertTrue(CellTypeHelper::isNumeric(1337e0));

        $this->assertFalse(CellTypeHelper::isNumeric('0'));
        $this->assertFalse(CellTypeHelper::isNumeric('42'));
        $this->assertFalse(CellTypeHelper::isNumeric(true));
        $this->assertFalse(CellTypeHelper::isNumeric([2]));
        $this->assertFalse(CellTypeHelper::isNumeric(new \stdClass()));
        $this->assertFalse(CellTypeHelper::isNumeric(null));
    }

    /**
     * @return array
     */
    public function testIsBoolean()
    {
        $this->assertTrue(CellTypeHelper::isBoolean(true));
        $this->assertTrue(CellTypeHelper::isBoolean(false));

        $this->assertFalse(CellTypeHelper::isBoolean(0));
        $this->assertFalse(CellTypeHelper::isBoolean(1));
        $this->assertFalse(CellTypeHelper::isBoolean('0'));
        $this->assertFalse(CellTypeHelper::isBoolean('1'));
        $this->assertFalse(CellTypeHelper::isBoolean('true'));
        $this->assertFalse(CellTypeHelper::isBoolean('false'));
        $this->assertFalse(CellTypeHelper::isBoolean([true]));
        $this->assertFalse(CellTypeHelper::isBoolean(new \stdClass()));
        $this->assertFalse(CellTypeHelper::isBoolean(null));
    }
}
