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
        $workbook = new SheetTestWorkbook();

        $sheets = [$workbook->addNewSheet(), $workbook->addNewSheet()];

        $this->assertEquals('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        $this->assertEquals('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $workbook = new SheetTestWorkbook();

        $customSheetName = 'CustomName';
        $sheet = $workbook->addNewSheet();
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
        $workbook = new SheetTestWorkbook();
        $workbook->addNewSheet()->setName($customSheetName);
    }

    /**
     * @return void
     */
    public function testSetSheetNameShouldNotThrowWhenSettingSameNameAsCurrentOne()
    {
        $workbook = new SheetTestWorkbook();
        $customSheetName = 'Sheet name';
        $sheet = $workbook->addNewSheet();
        $sheet->setName($customSheetName);
        $sheet->setName($customSheetName);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\InvalidSheetNameException
     * @return void
     */
    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $workbook = new SheetTestWorkbook();

        $customSheetName = 'Sheet name';

        $sheet = $workbook->addNewSheet();
        $sheet->setName($customSheetName);

        $sheet = $workbook->addNewSheet();
        $sheet->setName($customSheetName);
    }
}

class SheetTestWorkbook extends \Box\Spout\Writer\Common\Internal\AbstractWorkbook
{
	protected function getMaxRowsPerWorksheet()
	{
		return 0;
	}

	protected function getStyleHelper()
	{
		return null;
	}

	public function addNewSheet()
	{
        $newSheetIndex = count($this->worksheets);
        $sheet = new Sheet($newSheetIndex, $this);
        $this->worksheets[] = $sheet;
        return $sheet;
	}

	public function close($finalFilePointer)
	{
	}
}
