<?php

namespace Box\Spout\Writer\Common;

/**
 * Class SheetTest
 *
 * @package Box\Spout\Writer\Common
 */
class SheetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = [new Sheet(0), new Sheet(1)];

        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $customSheetName = 'CustomName';
        $sheet = new Sheet(0);
        $sheet->setName($customSheetName);

        $this->assertEquals($customSheetName, $sheet->getName(), "The sheet name should have been changed to '$customSheetName'");
    }

    /**
     * @return array
     */
    public function dataProviderForInvalidSheetNames()
    {
        return [
            [null],
            [21],
            [''],
            ['this title exceeds the 31 characters limit'],
            ['Illegal \\'],
            ['Illegal /'],
            ['Illegal ?'],
            ['Illegal *'],
            ['Illegal :'],
            ['Illegal ['],
            ['Illegal ]'],
            ['\'Illegal start'],
            ['Illegal end\''],
        ];
    }

    /**
     * @dataProvider dataProviderForInvalidSheetNames
     * @expectedException \Box\Spout\Writer\Exception\InvalidSheetNameException
     *
     * @param string $customSheetName
     * @return void
     */
    public function testSetSheetNameShouldThrowOnInvalidName($customSheetName)
    {
        (new Sheet(0))->setName($customSheetName);
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldNotThrowWhenSettingSameNameAsCurrentOne()
    {
        $customSheetName = 'Sheet name';
        $sheet = new Sheet(0);
        $sheet->setName($customSheetName);
        $sheet->setName($customSheetName);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\InvalidSheetNameException
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $customSheetName = 'Sheet name';

        $sheet = new Sheet(0);
        $sheet->setName($customSheetName);

        $sheet = new Sheet(1);
        $sheet->setName($customSheetName);
    }
}
