<?php

namespace Box\Spout\Writer\ODS\Helper;

use Box\Spout\Writer\Style\StyleBuilder;

/**
 * Class StyleHelperTest
 *
 * @package Box\Spout\Writer\ODS\Helper
 */
class StyleHelperTest extends \PHPUnit_Framework_TestCase
{
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
     * @return void
     */
    public function testRegisterStyleShouldUpdateId()
    {
        $style1 = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontUnderline()->build();

        $this->assertEquals(0, $this->defaultStyle->getId(), 'Default style ID should be 0');
        $this->assertNull($style1->getId());
        $this->assertNull($style2->getId());

        $styleHelper = new StyleHelper($this->defaultStyle);
        $registeredStyle1 = $styleHelper->registerStyle($style1);
        $registeredStyle2 = $styleHelper->registerStyle($style2);

        $this->assertEquals(1, $registeredStyle1->getId());
        $this->assertEquals(2, $registeredStyle2->getId());
    }

    /**
     * @return void
     */
    public function testRegisterStyleShouldReuseAlreadyRegisteredStyles()
    {
        $style = (new StyleBuilder())->setFontBold()->build();

        $styleHelper = new StyleHelper($this->defaultStyle);
        $registeredStyle1 = $styleHelper->registerStyle($style);
        $registeredStyle2 = $styleHelper->registerStyle($style);

        $this->assertEquals(1, $registeredStyle1->getId());
        $this->assertEquals(1, $registeredStyle2->getId());
    }

    /**
     * @return void
     */
    public function testApplyExtraStylesIfNeededShouldApplyWrapTextIfCellContainsNewLine()
    {
        $style = clone $this->defaultStyle;
        $styleHelper = new StyleHelper($this->defaultStyle);

        $this->assertFalse($style->shouldWrapText());

        $updatedStyle = $styleHelper->applyExtraStylesIfNeeded($style, [12, 'single line', "multi\nlines", null]);

        $this->assertTrue($updatedStyle->shouldWrapText());
    }

    /**
     * @return void
     */
    public function testApplyExtraStylesIfNeededShouldDoNothingIfWrapTextAlreadyApplied()
    {
        $style = (new StyleBuilder())->setShouldWrapText()->build();
        $styleHelper = new StyleHelper($this->defaultStyle);

        $this->assertTrue($style->shouldWrapText());

        $updatedStyle = $styleHelper->applyExtraStylesIfNeeded($style, ["multi\nlines"]);

        $this->assertTrue($updatedStyle->shouldWrapText());
    }
}
