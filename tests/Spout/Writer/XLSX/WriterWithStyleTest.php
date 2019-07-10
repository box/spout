<?php

namespace Box\Spout\Writer\XLSX;

use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Reader\Wrapper\XMLReader;
use Box\Spout\TestUsingResource;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Box\Spout\Writer\RowCreationHelper;
use Box\Spout\Writer\XLSX\Manager\OptionsManager;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterWithStyleTest
 */
class WriterWithStyleTest extends TestCase
{
    use TestUsingResource;
    use RowCreationHelper;

    /** @var \Box\Spout\Common\Entity\Style\Style */
    private $defaultStyle;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->defaultStyle = (new StyleBuilder())->build();
    }

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->addRow($this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    /**
     * @return void
     */
    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->addRow($this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    /**
     * @return void
     */
    public function testAddRowShouldListAllUsedFontsInCreatedStylesXmlFile()
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.xlsx';

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

        $dataRows = [
            $this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $style),
            $this->createStyledRowFromValues(['xlsx--21', 'xlsx--22'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        $this->assertEquals(3, $fontsDomElement->getAttribute('count'), 'There should be 3 fonts, including the default one.');

        $fontElements = $fontsDomElement->getElementsByTagName('font');
        $this->assertEquals(3, $fontElements->length, 'There should be 3 associated "font" elements, including the default one.');

        // First font should be the default one
        $defaultFontElement = $fontElements->item(0);
        $this->assertChildrenNumEquals(3, $defaultFontElement, 'The default font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals((string) OptionsManager::DEFAULT_FONT_SIZE, $defaultFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $defaultFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(OptionsManager::DEFAULT_FONT_NAME, $defaultFontElement, 'name', 'val');

        // Second font should contain data from the first created style
        $secondFontElement = $fontElements->item(1);
        $this->assertChildrenNumEquals(7, $secondFontElement, 'The font should only have 7 properties (4 custom styles + 3 default styles).');
        $this->assertChildExists($secondFontElement, 'b');
        $this->assertChildExists($secondFontElement, 'i');
        $this->assertChildExists($secondFontElement, 'u');
        $this->assertChildExists($secondFontElement, 'strike');
        $this->assertFirstChildHasAttributeEquals((string) OptionsManager::DEFAULT_FONT_SIZE, $secondFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $secondFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(OptionsManager::DEFAULT_FONT_NAME, $secondFontElement, 'name', 'val');

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
    public function testAddRowShouldApplyStyleToCells()
    {
        $fileName = 'test_add_row_should_apply_style_to_cells.xlsx';

        $style = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontSize(15)->build();

        $dataRows = [
            $this->createStyledRowFromValues(['xlsx--11'], $style),
            $this->createStyledRowFromValues(['xlsx--21'], $style2),
            $this->createRowFromValues(['xlsx--31']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        $this->assertCount(3, $cellDomElements, 'There should be 3 cells.');

        $this->assertEquals('1', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[1]->getAttribute('s'));
        $this->assertEquals('0', $cellDomElements[2]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddRowShouldApplyStyleToEmptyCellsIfNeeded()
    {
        $fileName = 'test_add_row_should_apply_style_to_empty_cells_if_needed.xlsx';

        $styleWithFont = (new StyleBuilder())->setFontBold()->build();
        $styleWithBackground = (new StyleBuilder())->setBackgroundColor(Color::BLUE)->build();

        $border = (new BorderBuilder())->setBorderBottom(Color::GREEN)->build();
        $styleWithBorder = (new StyleBuilder())->setBorder($border)->build();

        $dataRows = [
            $this->createRowFromValues(['xlsx--11', '', 'xlsx--13']),
            $this->createStyledRowFromValues(['xlsx--21', '', 'xlsx--23'], $styleWithFont),
            $this->createStyledRowFromValues(['xlsx--31', '', 'xlsx--33'], $styleWithBackground),
            $this->createStyledRowFromValues(['xlsx--41', '', 'xlsx--43'], $styleWithBorder),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // The first and second rows should not have a reference to the empty cell
        // The other rows should have the reference because style should be applied to them
        // So that's: 2 + 2 + 3 + 3 = 10 cells
        $this->assertCount(10, $cellDomElements);

        // First row has 2 styled cells
        $this->assertEquals('0', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('0', $cellDomElements[1]->getAttribute('s'));

        // Second row has 2 styled cells
        $this->assertEquals('1', $cellDomElements[2]->getAttribute('s'));
        $this->assertEquals('1', $cellDomElements[3]->getAttribute('s'));

        // Third row has 3 styled cells
        $this->assertEquals('2', $cellDomElements[4]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[5]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[6]->getAttribute('s'));

        // Third row has 3 styled cells
        $this->assertEquals('3', $cellDomElements[7]->getAttribute('s'));
        $this->assertEquals('3', $cellDomElements[8]->getAttribute('s'));
        $this->assertEquals('3', $cellDomElements[9]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddRowShouldReuseDuplicateStyles()
    {
        $fileName = 'test_add_row_should_reuse_duplicate_styles.xlsx';

        $style = (new StyleBuilder())->setFontBold()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11'],
            ['xlsx--21'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        $this->assertEquals('1', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('1', $cellDomElements[1]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddRowWithNumFmtStyles()
    {
        $fileName = 'test_add_row_with_numfmt.xlsx';
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.00')//Builtin format
            ->build();
        $style2 = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.000')
            ->build();


        $dataRows = [
            $this->createStyledRowFromValues([1.123456789], $style),
            $this->createStyledRowFromValues([12.1], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);


        $formatsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'numFmts');
        $this->assertEquals(
            1,
            $formatsDomElement->getAttribute('count'),
            'There should be 2 formats, including the 1 default ones'
        );


        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        foreach ([2, 164] as $index => $expected) {
            $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item($index + 1);
            $this->assertEquals($expected, $xfElement->getAttribute('numFmtId'));

        }
    }

    /**
     * @return void
     */
    public function testAddRowShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified()
    {
        $fileName = 'test_add_row_should_add_wrap_text_alignment.xlsx';

        $style = (new StyleBuilder())->setShouldWrapText()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11', 'xlsx--12'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        $this->assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    /**
     * @return void
     */
    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine()
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.xlsx';

        $dataRows = $this->createStyledRowsFromValues([
            ["xlsx--11\nxlsx--11"],
            ['xlsx--21'],
        ], $this->defaultStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        $this->assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    /**
     * @return void
     */
    public function testAddRowShouldSupportCellStyling()
    {
        $fileName = 'test_add_row_should_support_cell_styling.xlsx';

        $boldStyle = (new StyleBuilder())->setFontBold()->build();
        $underlineStyle = (new StyleBuilder())->setFontUnderline()->build();

        $dataRow = WriterEntityFactory::createRow([
            WriterEntityFactory::createCell('xlsx--11', $boldStyle),
            WriterEntityFactory::createCell('xlsx--12', $underlineStyle),
            WriterEntityFactory::createCell('xlsx--13', $underlineStyle),
        ]);

        $this->writeToXLSXFile([$dataRow], $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // First row should have 3 styled cells, with cell 2 and 3 sharing the same style
        $this->assertEquals('1', $cellDomElements[0]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[1]->getAttribute('s'));
        $this->assertEquals('2', $cellDomElements[2]->getAttribute('s'));
    }

    /**
     * @return void
     */
    public function testAddBackgroundColor()
    {
        $fileName = 'test_add_background_color.xlsx';

        $style = (new StyleBuilder())->setBackgroundColor(Color::WHITE)->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['BgColor'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        $this->assertEquals(3, $fillsDomElement->getAttribute('count'), 'There should be 3 fills, including the 2 default ones');

        $fillsElements = $fillsDomElement->getElementsByTagName('fill');

        $thirdFillElement = $fillsElements->item(2); // Zero based
        $fgColor = $thirdFillElement->getElementsByTagName('fgColor')->item(0)->getAttribute('rgb');

        $this->assertEquals(Color::WHITE, $fgColor, 'The foreground color should equal white');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $this->assertEquals(2, $styleXfsElements->getAttribute('count'), '2 cell xfs present - a default one and a custom one');

        $customFillId = $styleXfsElements->lastChild->getAttribute('fillId');
        $this->assertEquals(2, (int) $customFillId, 'The custom fill id should have the index 2');
    }

    /**
     * @return void
     */
    public function testReuseBackgroundColorSharedDefinition()
    {
        $fileName = 'test_add_background_color_shared_definition.xlsx';

        $style = (new StyleBuilder())->setBackgroundColor(Color::RED)->setFontBold()->build();
        $style2 = (new StyleBuilder())->setBackgroundColor(Color::RED)->build();

        $dataRows = [
            $this->createStyledRowFromValues(['row-bold-background-red'], $style),
            $this->createStyledRowFromValues(['row-background-red'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        $this->assertEquals(
            3,
            $fillsDomElement->getAttribute('count'),
            'There should be 3 fills, including the 2 default ones'
        );

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $this->assertEquals(
            3,
            $styleXfsElements->getAttribute('count'),
            '3 cell xfs present - a default one and two custom ones'
        );

        $firstCustomId = $styleXfsElements->childNodes->item(1)->getAttribute('fillId');
        $this->assertEquals(2, (int) $firstCustomId, 'The first custom fill id should have the index 2');

        $secondCustomId = $styleXfsElements->childNodes->item(2)->getAttribute('fillId');
        $this->assertEquals(2, (int) $secondCustomId, 'The second custom fill id should have the index 2');
    }

    /**
     * @return void
     */
    public function testBorders()
    {
        $fileName = 'test_borders.xlsx';

        $borderBottomGreenThickSolid = (new BorderBuilder())
            ->setBorderBottom(Color::GREEN, Border::WIDTH_THICK, Border::STYLE_SOLID)->build();

        $borderTopRedThinDashed = (new BorderBuilder())
            ->setBorderTop(Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED)->build();

        $styles =  [
            (new StyleBuilder())->setBorder($borderBottomGreenThickSolid)->build(),
            (new StyleBuilder())->build(),
            (new StyleBuilder())->setBorder($borderTopRedThinDashed)->build(),
        ];

        $dataRows = [
            $this->createStyledRowFromValues(['row-with-border-bottom-green-thick-solid'], $styles[0]),
            $this->createStyledRowFromValues(['row-without-border'], $styles[1]),
            $this->createStyledRowFromValues(['row-with-border-top-red-thin-dashed'], $styles[2]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');
        $this->assertEquals(3, $borderElements->getAttribute('count'), '3 borders present');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $this->assertEquals(3, $styleXfsElements->getAttribute('count'), '3 cell xfs present');
    }

    /**
     * @return void
     */
    public function testBordersCorrectOrder()
    {
        // Border should be Left, Right, Top, Bottom
        $fileName = 'test_borders_correct_order.xlsx';

        $borders = (new BorderBuilder())
            ->setBorderRight()
            ->setBorderTop()
            ->setBorderLeft()
            ->setBorderBottom()
            ->build();

        $style = (new StyleBuilder())->setBorder($borders)->build();

        $dataRows = $this->createStyledRowsFromValues([
            ['I am a teapot'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);
        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        $correctOrdering = [
            'left', 'right', 'top', 'bottom',
        ];

        /** @var \DOMElement $borderNode */
        foreach ($borderElements->childNodes as $borderNode) {
            $borderParts = $borderNode->childNodes;
            $ordering = [];

            /** @var \DOMText $part */
            foreach ($borderParts as $part) {
                if ($part instanceof \DOMElement) {
                    $ordering[] = $part->nodeName;
                }
            }

            $this->assertEquals($correctOrdering, $ordering, 'The border parts are in correct ordering');
        }
    }

    /**
     * @return void
     */
    public function testSetDefaultRowStyle()
    {
        $fileName = 'test_set_default_row_style.xlsx';
        $dataRows = $this->createRowsFromValues([['xlsx--11']]);

        $defaultFontSize = 50;
        $defaultStyle = (new StyleBuilder())->setFontSize($defaultFontSize)->build();

        $this->writeToXLSXFileWithDefaultStyle($dataRows, $fileName, $defaultStyle);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        $fontElements = $fontsDomElement->getElementsByTagName('font');
        $this->assertEquals(1, $fontElements->length, 'There should only be the default font.');

        $defaultFontElement = $fontElements->item(0);
        $this->assertFirstChildHasAttributeEquals((string) $defaultFontSize, $defaultFontElement, 'sz', 'val');
    }

    /**
     * @return void
     */
    public function testReuseBorders()
    {
        $fileName = 'test_reuse_borders.xlsx';

        $borderLeft = (new BorderBuilder())->setBorderLeft()->build();
        $borderLeftStyle = (new StyleBuilder())->setBorder($borderLeft)->build();

        $borderRight = (new BorderBuilder())->setBorderRight(Color::RED, Border::WIDTH_THICK)->build();
        $borderRightStyle = (new StyleBuilder())->setBorder($borderRight)->build();

        $fontStyle = (new StyleBuilder())->setFontBold()->build();
        $emptyStyle = (new StyleBuilder())->build();

        $borderRightFontBoldStyle = (new StyleMerger())->merge($borderRightStyle, $fontStyle);

        $dataRows = [
            $this->createStyledRowFromValues(['Border-Left'], $borderLeftStyle),
            $this->createStyledRowFromValues(['Empty'], $emptyStyle),
            $this->createStyledRowFromValues(['Font-Bold'], $fontStyle),
            $this->createStyledRowFromValues(['Border-Right'], $borderRightStyle),
            $this->createStyledRowFromValues(['Border-Right-Font-Bold'], $borderRightFontBoldStyle),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        $this->assertEquals(3, $borderElements->getAttribute('count'), '3 borders in count attribute');
        $this->assertEquals(3, $borderElements->childNodes->length, '3 border childnodes present');

        /** @var \DOMElement $firstBorder */
        $firstBorder = $borderElements->childNodes->item(1); // 0  = default border
        $leftStyle = $firstBorder->getElementsByTagName('left')->item(0)->getAttribute('style');
        $this->assertEquals('medium', $leftStyle, 'Style is medium');

        /** @var \DOMElement $secondBorder */
        $secondBorder = $borderElements->childNodes->item(2);
        $rightStyle = $secondBorder->getElementsByTagName('right')->item(0)->getAttribute('style');
        $this->assertEquals('thick', $rightStyle, 'Style is thick');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        // A rather relaxed test
        // Where a border is applied - the borderId attribute has to be greater than 0
        $bordersApplied = 0;
        /** @var \DOMElement $node */
        foreach ($styleXfsElements->childNodes as $node) {
            $shouldApplyBorder = ((int) $node->getAttribute('applyBorder') === 1);
            if ($shouldApplyBorder) {
                $bordersApplied++;
                $this->assertGreaterThan(0, (int) $node->getAttribute('borderId'), 'BorderId is greater than 0');
            } else {
                $this->assertSame(0, (int) $node->getAttribute('borderId'), 'BorderId is 0');
            }
        }

        $this->assertEquals(3, $bordersApplied, 'Three borders have been applied');
    }

    /**
     * @param Row[] $allRows
     * @param string $fileName
     * @return Writer
     */
    private function writeToXLSXFile($allRows, $fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setShouldUseInlineStrings(true);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     * @param string $fileName
     * @param \Box\Spout\Common\Entity\Style\Style|null $defaultStyle
     * @return Writer
     */
    private function writeToXLSXFileWithDefaultStyle($allRows, $fileName, $defaultStyle)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setShouldUseInlineStrings(true);
        $writer->setDefaultRowStyle($defaultStyle);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
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

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/styles.xml');
        $xmlReader->readUntilNodeFound($section);

        $xmlSection = $xmlReader->expand();

        $xmlReader->close();

        return $xmlSection;
    }

    /**
     * @param string $fileName
     * @return \DOMNode[]
     */
    private function getCellElementsFromSheetXmlFile($fileName)
    {
        $cellElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('c')) {
                $cellElements[] = $xmlReader->expand();
            }
        }

        $xmlReader->close();

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
