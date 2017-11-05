<?php

namespace Box\Spout\Writer\Common\Manager\Style;

use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Entity\Cell;

/**
 * Class StyleManagerTest
 */
class StyleManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return StyleManager
     */
    private function getStyleManager()
    {
        $style = (new StyleBuilder())->build();
        $styleRegistry = new StyleRegistry($style);

        return new StyleManager($styleRegistry);
    }

    /**
     * @return void
     */
    public function testApplyExtraStylesIfNeededShouldApplyWrapTextIfCellContainsNewLine()
    {
        $style = (new StyleBuilder())->build();
        $this->assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $updatedStyle = $styleManager->applyExtraStylesIfNeeded(new Cell("multi\nlines", $style));

        $this->assertTrue($updatedStyle->shouldWrapText());
    }

    /**
     * @return void
     */
    public function testApplyExtraStylesIfNeededShouldDoNothingIfWrapTextAlreadyApplied()
    {
        $style = (new StyleBuilder())->setShouldWrapText()->build();
        $this->assertTrue($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $updatedStyle = $styleManager->applyExtraStylesIfNeeded(new Cell("multi\nlines", $style));

        $this->assertTrue($updatedStyle->shouldWrapText());
    }
}
