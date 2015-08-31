<?php

namespace Box\Spout\Writer\ODS;

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
 * @package Box\Spout\Writer\ODS
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
        $writer = WriterFactory::create(Type::ODS);
        $writer->addRowWithStyle(['ods--11', 'ods--12'], $this->defaultStyle);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function testAddRowWithStyleShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $writer = WriterFactory::create(Type::ODS);
        $writer->addRowWithStyle(['ods--11', 'ods--12'], $this->defaultStyle);
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
        $fileName = 'test_add_row_with_style_should_throw_exception.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);
        $writer->addRowWithStyle(['ods--11', 'ods--12'], $style);
    }

    /**
     * @dataProvider dataProviderForInvalidStyle
     * @expectedException \Box\Spout\Common\Exception\InvalidArgumentException
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    public function testAddRowsWithStyleShouldThrowExceptionIfInvalidStyleGiven($style)
    {
        $fileName = 'test_add_row_with_style_should_throw_exception.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterFactory::create(Type::ODS);
        $writer->openToFile($resourcePath);
        $writer->addRowsWithStyle([['ods--11', 'ods--12']], $style);
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldListAllUsedStylesInCreatedContentXmlFile()
    {
        $fileName = 'test_add_row_with_style_should_list_all_used_fonts.ods';
        $dataRows = [
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22'],
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

        $this->writeToODSFileWithMultipleStyles($dataRows, $fileName, [$style, $style2]);

        $cellStyleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        $this->assertEquals(3, count($cellStyleElements), 'There should be 3 separate cell styles, including the default one.');

        // Second font should contain data from the first created style
        $customFont1Element = $cellStyleElements[1];
        $this->assertFirstChildHasAttributeEquals('bold', $customFont1Element, 'text-properties', 'fo:font-weight');
        $this->assertFirstChildHasAttributeEquals('italic', $customFont1Element, 'text-properties', 'fo:font-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-underline-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-line-through-style');

        // Third font should contain data from the second created style
        $customFont2Element = $cellStyleElements[2];
        $this->assertFirstChildHasAttributeEquals('15pt', $customFont2Element, 'text-properties', 'fo:font-size');
        $this->assertFirstChildHasAttributeEquals('#' . Color::RED, $customFont2Element, 'text-properties', 'fo:color');
        $this->assertFirstChildHasAttributeEquals('Cambria', $customFont2Element, 'text-properties', 'style:font-name');
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldWriteDefaultStyleSettings()
    {
        $fileName = 'test_add_row_with_style_should_write_default_style_settings.ods';
        $dataRow = ['ods--11', 'ods--12'];

        $this->writeToODSFile([$dataRow], $fileName, $this->defaultStyle);

        $textPropertiesElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'style:text-properties');
        $this->assertEquals(Style::DEFAULT_FONT_SIZE . 'pt', $textPropertiesElement->getAttribute('fo:font-size'));
        $this->assertEquals('#' . Style::DEFAULT_FONT_COLOR, $textPropertiesElement->getAttribute('fo:color'));
        $this->assertEquals(Style::DEFAULT_FONT_NAME, $textPropertiesElement->getAttribute('style:font-name'));
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldApplyStyleToCells()
    {
        $fileName = 'test_add_row_with_style_should_apply_style_to_cells.ods';
        $dataRows = [
            ['ods--11'],
            ['ods--21'],
            ['ods--31'],
        ];
        $style = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontSize(15)->build();

        $this->writeToODSFileWithMultipleStyles($dataRows, $fileName, [$style, $style2, null]);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        $this->assertEquals(3, count($cellDomElements), 'There should be 3 cells with content');

        $this->assertEquals('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        $this->assertEquals('ce3', $cellDomElements[1]->getAttribute('table:style-name'));
        $this->assertEquals('ce1', $cellDomElements[2]->getAttribute('table:style-name'));
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldReuseDuplicateStyles()
    {
        $fileName = 'test_add_row_with_style_should_reuse_duplicate_styles.ods';
        $dataRows = [
            ['ods--11'],
            ['ods--21'],
        ];
        $style = (new StyleBuilder())->setFontBold()->build();

        $this->writeToODSFile($dataRows, $fileName, $style);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        $this->assertEquals(2, count($cellDomElements), 'There should be 2 cells with content');

        $this->assertEquals('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        $this->assertEquals('ce2', $cellDomElements[1]->getAttribute('table:style-name'));
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified()
    {
        $fileName = 'test_add_row_with_style_should_add_wrap_text_alignment.ods';
        $dataRows = [
            ['ods--11', 'ods--12'],
        ];
        $style = (new StyleBuilder())->setShouldWrapText()->build();

        $this->writeToODSFile($dataRows, $fileName,$style);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        $this->assertEquals(2, count($styleElements), 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    /**
     * @return void
     */
    public function testAddRowWithStyleShouldApplyWrapTextIfCellContainsNewLine()
    {
        $fileName = 'test_add_row_with_style_should_apply_wrap_text_if_new_lines.ods';
        $dataRows = [
            ["ods--11\nods--11"],
        ];

        $this->writeToODSFile($dataRows, $fileName, $this->defaultStyle);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        $this->assertEquals(2, count($styleElements), 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    /**
     * @param array $allRows
     * @param string $fileName
     * @param \Box\Spout\Writer\Style\Style $style
     * @return Writer
     */
    private function writeToODSFile($allRows, $fileName, $style)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);

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
    private function writeToODSFileWithMultipleStyles($allRows, $fileName, $styles)
    {
        // there should be as many rows as there are styles passed in
        $this->assertEquals(count($allRows), count($styles));

        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        /** @var \Box\Spout\Writer\ODS\Writer $writer */
        $writer = WriterFactory::create(Type::ODS);

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
     * @return \DOMNode[]
     */
    private function getCellElementsFromContentXmlFile($fileName)
    {
        $cellElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToStylesXmlFile = $resourcePath . '#content.xml';

        $xmlReader = new \XMLReader();
        $xmlReader->open('zip://' . $pathToStylesXmlFile);

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType === \XMLReader::ELEMENT && $xmlReader->name === 'table:table-cell' && $xmlReader->getAttribute('office:value-type') !== null) {
                $cellElements[] = $xmlReader->expand();
            }
        }

        return $cellElements;
    }

    /**
     * @param string $fileName
     * @return \DOMNode[]
     */
    private function getCellStyleElementsFromContentXmlFile($fileName)
    {
        $cellStyleElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToStylesXmlFile = $resourcePath . '#content.xml';

        $xmlReader = new \XMLReader();
        $xmlReader->open('zip://' . $pathToStylesXmlFile);

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType === \XMLReader::ELEMENT && $xmlReader->name === 'style:style' && $xmlReader->getAttribute('style:family') === 'table-cell') {
                $cellStyleElements[] = $xmlReader->expand();
            }
        }

        return $cellStyleElements;
    }

    /**
     * @param string $fileName
     * @param string $section
     * @return \DomElement
     */
    private function getXmlSectionFromStylesXmlFile($fileName, $section)
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToStylesXmlFile = $resourcePath . '#styles.xml';

        $xmlReader = new XMLReader();
        $xmlReader->open('zip://' . $pathToStylesXmlFile);
        $xmlReader->readUntilNodeFound($section);

        return $xmlReader->expand();
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
}
