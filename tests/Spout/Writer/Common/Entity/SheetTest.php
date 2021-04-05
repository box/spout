<?php

namespace Box\Spout\Writer\Common\Entity;

use Box\Spout\Common\Helper\StringHelper;
use Box\Spout\Writer\Common\Manager\SheetManager;
use Box\Spout\Writer\Exception\InvalidSheetNameException;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest
 */
class SheetTest extends TestCase
{
    /** @var SheetManager */
    private $sheetManager;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->sheetManager = new SheetManager(new StringHelper());
    }

    /**
     * @param int $sheetIndex
     * @param int $associatedWorkbookId
     * @return Sheet
     */
    private function createSheet($sheetIndex, $associatedWorkbookId)
    {
        return new Sheet($sheetIndex, $associatedWorkbookId, $this->sheetManager);
    }

    /**
     * @return void
     */
    public function testGetSheetName()
    {
        $sheets = [$this->createSheet(0, 'workbookId1'), $this->createSheet(1, 'workbookId1')];

        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $customSheetName = 'CustomName';
        $sheet = $this->createSheet(0, 'workbookId1');
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
     *
     * @param string $customSheetName
     * @return void
     */
    public function testSetSheetNameShouldThrowOnInvalidName($customSheetName)
    {
        $this->expectException(InvalidSheetNameException::class);

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldNotThrowWhenSettingSameNameAsCurrentOne()
    {
        $customSheetName = 'Sheet name';
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);
        $sheet->setName($customSheetName);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $this->expectException(InvalidSheetNameException::class);

        $customSheetName = 'Sheet name';

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(1, 'workbookId1');
        $sheet->setName($customSheetName);
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldNotThrowWhenSameNameUsedInDifferentWorkbooks()
    {
        $customSheetName = 'Sheet name';

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(0, 'workbookId2');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(1, 'workbookId3');
        $sheet->setName($customSheetName);
        $this->expectNotToPerformAssertions();
    }
}
