<?php

namespace Box\Spout\Writer\Common\Creator\Style;

use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

/**
 * Class StyleManagerTest
 */
class StyleBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function testStyleBuilderShouldApplyBorders()
    {
        $border = (new BorderBuilder())
            ->setBorderBottom()
            ->build();
        $style = (new StyleBuilder())->setBorder($border)->build();
        $this->assertTrue($style->shouldApplyBorder());
    }

    /**
     * @return void
     */
    public function testStyleBuilderShouldMergeBorders()
    {
        $border = (new BorderBuilder())->setBorderBottom(Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED)->build();

        $baseStyle = (new StyleBuilder())->setBorder($border)->build();
        $currentStyle = (new StyleBuilder())->build();

        $styleMerger = new StyleMerger();
        $mergedStyle = $styleMerger->merge($currentStyle, $baseStyle);

        $this->assertNull($currentStyle->getBorder(), 'Current style has no border');
        $this->assertInstanceOf(Border::class, $baseStyle->getBorder(), 'Base style has a border');
        $this->assertInstanceOf(Border::class, $mergedStyle->getBorder(), 'Merged style has a border');
    }

    /**
     * @return void
     */
    public function testStyleBuilderShouldApplyCellAlignment()
    {
        $style = (new StyleBuilder())->setCellAlignment(CellAlignment::CENTER)->build();
        $this->assertTrue($style->shouldApplyCellAlignment());
    }

    /**
     * @return void
     */
    public function testStyleBuilderShouldThrowOnInvalidCellAlignment()
    {
        $this->expectException(InvalidArgumentException::class);
        (new StyleBuilder())->setCellAlignment('invalid_cell_alignment')->build();
    }
}
