<?php

namespace Box\Spout\Reader\XLSX\Manager;

use Box\Spout\Reader\XLSX\Creator\InternalEntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class StyleManagerTest
 */
class StyleManagerTest extends TestCase
{
    /**
     * @param array $styleAttributes
     * @param array $customNumberFormats
     * @return StyleManager
     */
    private function getStyleManagerMock($styleAttributes = [], $customNumberFormats = [])
    {
        $entityFactory = $this->createMock(InternalEntityFactory::class);
        $workbookRelationshipsManager = $this->createMock(WorkbookRelationshipsManager::class);
        $workbookRelationshipsManager->method('hasStylesXMLFile')->willReturn(true);

        /** @var StyleManager $styleManager */
        $styleManager = $this->getMockBuilder('\Box\Spout\Reader\XLSX\Manager\StyleManager')
                             ->setConstructorArgs(['/path/to/file.xlsx', $workbookRelationshipsManager, $entityFactory])
                             ->setMethods(['getCustomNumberFormats', 'getStylesAttributes'])
                             ->getMock();

        $styleManager->method('getStylesAttributes')->willReturn($styleAttributes);
        $styleManager->method('getCustomNumberFormats')->willReturn($customNumberFormats);

        return $styleManager;
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithDefaultStyle()
    {
        $styleManager = $this->getStyleManagerMock();
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(0);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenShouldNotApplyNumberFormat()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => false, 'numFmtId' => 14]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithGeneralFormat()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 0]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithNonDateBuiltinFormat()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 9]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithNoNumFmtId()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => null]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithBuiltinDateFormats()
    {
        $builtinNumFmtIdsForDate = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];

        foreach ($builtinNumFmtIdsForDate as $builtinNumFmtIdForDate) {
            $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => $builtinNumFmtIdForDate]]);
            $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

            $this->assertTrue($shouldFormatAsDate);
        }
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenApplyNumberFormatNotSetAndUsingBuiltinDateFormat()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => null, 'numFmtId' => 14]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        $this->assertTrue($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenApplyNumberFormatNotSetAndUsingBuiltinNonDateFormat()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => null, 'numFmtId' => 9]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenCustomNumberFormatNotFound()
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 165]], [166 => []]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return array
     */
    public function dataProviderForCustomDateFormats()
    {
        return [
            // number format, expectedResult
            ['[$-409]dddd\,\ mmmm\ d\,\ yy', true],
            ['[$-409]d\-mmm\-yy;@', true],
            ['[$-409]d\-mmm\-yyyy;@', true],
            ['mm/dd/yy;@', true],
            ['MM/DD/YY;@', true],
            ['[$-F800]dddd\,\ mmmm\ dd\,\ yyyy', true],
            ['m/d;@', true],
            ['m/d/yy;@', true],
            ['[$-409]d\-mmm;@', true],
            ['[$-409]dd\-mmm\-yy;@', true],
            ['[$-409]mmm\-yy;@', true],
            ['[$-409]mmmm\-yy;@', true],
            ['[$-409]mmmm\ d\,\ yyyy;@', true],
            ['[$-409]m/d/yy\ h:mm\ AM/PM;@', true],
            ['m/d/yy\ h:mm;@', true],
            ['[$-409]mmmmm;@', true],
            ['[$-409]MMmmM;@', true],
            ['[$-409]mmmmm\-yy;@', true],
            ['m/d/yyyy;@', true],
            ['[$-409]m/d/yy\--h:mm;@', true],
            ['General', false],
            ['GENERAL', false],
            ['\ma\yb\e', false],
            ['[Red]foo;', false],
        ];
    }

    /**
     * @dataProvider dataProviderForCustomDateFormats
     *
     * @param string $numberFormat
     * @param bool $expectedResult
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithCustomDateFormats($numberFormat, $expectedResult)
    {
        $numFmtId = 165;
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => $numFmtId]], [$numFmtId => $numberFormat]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        $this->assertEquals($expectedResult, $shouldFormatAsDate);
    }
}
