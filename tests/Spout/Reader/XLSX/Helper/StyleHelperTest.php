<?php

namespace Box\Spout\Reader\XLSX\Helper;

/**
 * Class StyleHelperTest
 *
 * @package Box\Spout\Reader\XLSX\Helper
 */
class StyleHelperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param array $styleAttributes
     * @param array|void $customNumberFormats
     * @return StyleHelper
     */
    private function getStyleHelperMock($styleAttributes, $customNumberFormats = [])
    {
        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getMockBuilder('\Box\Spout\Reader\XLSX\Helper\StyleHelper')
                            ->setMethods(['getCustomNumberFormats', 'getStylesAttributes'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $styleHelper->method('getStylesAttributes')->willReturn($styleAttributes);
        $styleHelper->method('getCustomNumberFormats')->willReturn($customNumberFormats);

        return $styleHelper;
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithDefaultStyle()
    {
        $styleHelper = $this->getStyleHelperMock([]);
        $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(0);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenStyleIdNotListed()
    {
        $styleHelper = $this->getStyleHelperMock([['applyNumberFormat' => true]]);
        $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenShouldNotApplyNumberFormat()
    {
        $styleHelper = $this->getStyleHelperMock([[], ['applyNumberFormat' => false]]);
        $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(1);
        $this->assertFalse($shouldFormatAsDate);
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWithBuiltinDateFormats()
    {
        $builtinNumFmtIdsForDate = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];

        foreach ($builtinNumFmtIdsForDate as $builtinNumFmtIdForDate) {
            $styleHelper = $this->getStyleHelperMock([[], ['applyNumberFormat' => true, 'numFmtId' => $builtinNumFmtIdForDate]]);
            $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(1);

            $this->assertTrue($shouldFormatAsDate);
        }
    }

    /**
     * @return void
     */
    public function testShouldFormatNumericValueAsDateWhenCustomNumberFormatNotFound()
    {
        $styleHelper = $this->getStyleHelperMock([[], ['applyNumberFormat' => true, 'numFmtId' => 165]], [166 => []]);
        $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(1);

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
            ['[$-409]mmmmm\-yy;@', true],
            ['m/d/yyyy;@', true],
            ['[$-409]m/d/yy\--h:mm;@', true],
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
        $styleHelper = $this->getStyleHelperMock([[], ['applyNumberFormat' => true, 'numFmtId' => $numFmtId]], [$numFmtId => $numberFormat]);
        $shouldFormatAsDate = $styleHelper->shouldFormatNumericValueAsDate(1);

        $this->assertEquals($expectedResult, $shouldFormatAsDate);
    }
}
