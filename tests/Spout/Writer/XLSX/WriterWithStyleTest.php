<?php

namespace Box\Spout\Writer\XLSX;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\Style;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;

/**
 * Class WriterWithStyleTest
 *
 * @package Box\Spout\Writer\XLSX
 */
class WriterWithStyleTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /** @var \Box\Spout\Writer\Style\Style */
    protected $defaultStyle;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->defaultStyle = (new StyleBuilder())->build();
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testAddRowWithStyleShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer->addRowWithStyle(['xlsx--11', 'xlsx--12'], $this->defaultStyle);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testAddRowWithStyleShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer->addRowWithStyle(['xlsx--11', 'xlsx--12'], $this->defaultStyle);
    }

    /**
     * @return array
     */
    public function dataProviderForInvalidStyle()
    {
        return [
            ['style'],
            [new \stdClass()],
            [null],
        ];
    }

    /**
     * @dataProvider dataProviderForInvalidStyle
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    public function testAddRowWithStyleShouldThrowExceptionIfInvalidStyleGiven($style)
    {
        $fileName = 'test_add_row_with_style_should_throw_exception.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($resourcePath);
        $writer->addRowWithStyle(['xlsx--11', 'xlsx--12'], $style);
    }

    /**
     * @dataProvider dataProviderForInvalidStyle
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    public function testAddRowsWithStyleShouldThrowExceptionIfInvalidStyleGiven($style)
    {
        $fileName = 'test_add_row_with_style_should_throw_exception.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($resourcePath);
        $writer->addRowsWithStyle([['xlsx--11', 'xlsx--12']], $style);
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldListAllUsedFontsInCreatedStylesXmlFile()
    {
        $fileName = 'test_add_row_with_style_should_list_all_used_fonts.xlsx';
        $dataRows = [
            ['xlsx--11', 'xlsx--12'],
            ['xlsx--21', 'xlsx--22'],
        ];

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->build();
        $style2 = (new StyleBuilder())
            ->setFontSize(15)
            ->setFontColor(Color::RED)
            ->setFontName('Cambria')
            ->build();

        $this->writeToXLSXFileWithMultipleStyles($dataRows, $fileName, [$style, $style2]);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        $this->assertEquals(3, $fontsDomElement->getAttribute('count'), 'There should be 3 fonts, including the default one.');

        $fontElements = $fontsDomElement->getElementsByTagName('font');
        $this->assertEquals(3, $fontElements->length, 'There should be 3 associated "font" elements, including the default one.');

        // First font should be the default one
        $defaultFontElement = $fontElements->item(0);
        $this->assertChildrenNumEquals(3, $defaultFontElement, 'The default font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals((string) Writer::DEFAULT_FONT_SIZE, $defaultFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $defaultFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(Writer::DEFAULT_FONT_NAME, $defaultFontElement, 'name', 'val');

        // Second font should contain data from the first created style
        $secondFontElement = $fontElements->item(1);
        $this->assertChildrenNumEquals(7, $secondFontElement, 'The font should only have 7 properties (4 custom styles + 3 default styles).');
        $this->assertChildExists($secondFontElement, 'b');
        $this->assertChildExists($secondFontElement, 'i');
        $this->assertChildExists($secondFontElement, 'u');
        $this->assertChildExists($secondFontElement, 'strike');
        $this->assertFirstChildHasAttributeEquals((string) Writer::DEFAULT_FONT_SIZE, $secondFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $secondFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(Writer::DEFAULT_FONT_NAME, $secondFontElement, 'name', 'val');

        // Third font should contain data from the second created style
        $thirdFontElement = $fontElements->item(2);
        $this->assertChildrenNumEquals(3, $thirdFontElement, 'The font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals('15', $thirdFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Color::RED), $thirdFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals('Cambria', $thirdFontElement, 'name', 'val');
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldApplyStyleToCells()
    {
        $fileName = 'test_add_row_with_style_should_apply_style_to_cells.xlsx';
        $dataRows = [
            ['xlsx--11'],
            ['xlsx--21'],
            ['xlsx--31'],
        ];
        $style = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontSize(15)->build();

        $this->writeToXLSXFileWithMultipleStyles($dataRows, $fileName, [$style, $style2, null]);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        $this->assertEquals(3, count($cellDomElements), 'There should be 3 cells.');

        $this->assertEquals('1', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[1]->getAttribute('s'));
        $this->assertEquals('0', $cellDomElements[2]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldReuseDuplicateStyles()
    {
        $fileName = 'test_add_row_with_style_should_reuse_duplicate_styles.xlsx';
        $dataRows = [
            ['xlsx--11'],
            ['xlsx--21'],
        ];
        $style = (new StyleBuilder())->setFontBold()->build();

        $this->writeToXLSXFile($dataRows, $fileName, $style);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        $this->assertEquals('1', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('1', $cellDomElements[1]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified()
    {
        $fileName = 'test_add_row_with_style_should_add_wrap_text_alignment.xlsx';
        $dataRows = [
            ['xlsx--11', 'xlsx--12'],
        ];
        $style = (new StyleBuilder())->setShouldWrapText()->build();

        $this->writeToXLSXFile($dataRows, $fileName,$style);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        $this->assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldApplyWrapTextIfCellContainsNewLine()
    {
        $fileName = 'test_add_row_with_style_should_apply_wrap_text_if_new_lines.xlsx';
        $dataRows = [
            ["xlsx--11\nxlsx--11"],
            ['xlsx--21'],
        ];

        $this->writeToXLSXFile($dataRows, $fileName, $this->defaultStyle);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        $this->assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    /**
     * @param array $allRows
     * @param string $fileName
     * @param \Box\Spout\Writer\Style\Style $style
     * @return Writer
     */
    private function writeToXLSXFile($allRows, $fileName, $style)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\XLSX\Writer $writer */
        $writer = WriterFactory::create(Type::XLSX);
        $writer->setShouldUseInlineStrings(true);

        $writer->openToFile($resourcePath);
        $writer->addRowsWithStyle($allRows, $style);
        $writer->close();

        return $writer;
    }

    /**
     * @param array $allRows
     * @param string $fileName
     * @param \Box\Spout\Writer\Style\Style|null[] $styles
     * @return Writer
     */
    private function writeToXLSXFileWithMultipleStyles($allRows, $fileName, $styles)
    {
        // there should be as many rows as there are styles passed in
        $this->assertEquals(count($allRows), count($styles));

        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\XLSX\Writer $writer */
        $writer = WriterFactory::create(Type::XLSX);
        $writer->setShouldUseInlineStrings(true);

        $writer->openToFile($resourcePath);
        for ($i = 0; $i < count($allRows); $i++) {
            if ($styles[$i] === null) {
                $writer->addRow($allRows[$i]);
            } else {
                $writer->addRowWithStyle($allRows[$i], $styles[$i]);
            }
        }
        $writer->close();

        return $writer;
    }

    /**
     * @param string $fileName
     * @param string $section
     * @return \DomElement
     */
    private function getXmlSectionFromStylesXmlFile($fileName, $section)
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToStylesXmlFile = $resourcePath . '#xl/styles.xml';

        $xmlReader = new XMLReader();
        $xmlReader->open('zip://' . $pathToStylesXmlFile);
        $xmlReader->readUntilNodeFound($section);

        return $xmlReader->expand();
    }

    /**
     * @param string $fileName
     * @return \DOMNode[]
     */
    private function getCellElementsFromSheetXmlFile($fileName)
    {
        $cellElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToStylesXmlFile = $resourcePath . '#xl/worksheets/sheet1.xml';

        $xmlReader = new \XMLReader();
        $xmlReader->open('zip://' . $pathToStylesXmlFile);

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType === \XMLReader::ELEMENT && $xmlReader->name === 'c') {
                $cellElements[] = $xmlReader->expand();
            }
        }

        return $cellElements;
    }

    /**
     * @param string $expectedValue
     * @param \DOMNode $parentElement
     * @param string $childTagName
     * @param string $attributeName
     * @return void
     */
    private function assertFirstChildHasAttributeEquals($expectedValue, $parentElement, $childTagName, $attributeName)
    {
        $this->assertEquals($expectedValue, $parentElement->getElementsByTagName($childTagName)->item(0)->getAttribute($attributeName));
    }

    /**
     * @param int $expectedNumber
     * @param \DOMNode $parentElement
     * @param string $message
     * @return void
     */
    private function assertChildrenNumEquals($expectedNumber, $parentElement, $message)
    {
        $this->assertEquals($expectedNumber, $parentElement->getElementsByTagName('*')->length, $message);
    }

    /**
     * @param \DOMNode $parentElement
     * @param string $childTagName
     * @return void
     */
    private function assertChildExists($parentElement, $childTagName)
    {
        $this->assertEquals(1, $parentElement->getElementsByTagName($childTagName)->length);
    }
}
