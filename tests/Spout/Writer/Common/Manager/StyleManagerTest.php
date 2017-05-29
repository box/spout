<?php

namespace Box\Spout\Writer\Common\Manager;

use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Entity\Style\Border;
use Box\Spout\Writer\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Entity\Style\Style;

/**
 * Class StyleManagerTest
 *
 * @package Box\Spout\Writer\Common\Manager
 */
class StyleManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var StyleManager */
    private $styleManager;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->styleManager = new StyleManager();
    }

    /**
     * @return void
     */
    public function testSerializeShouldNotTakeIntoAccountId()
    {
        $style1 = (new StyleBuilder())->setFontBold()->build();
        $style1->setId(1);

        $style2 = (new StyleBuilder())->setFontBold()->build();
        $style2->setId(2);

        $this->assertEquals($this->styleManager->serialize($style1), $this->styleManager->serialize($style2));
    }

    /**
     * @return void
     */
    public function testMergeWithShouldReturnACopy()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertNotSame($mergedStyle, $currentStyle);
    }

    /**
     * @return void
     */
    public function testMergeWithShouldMergeSetProperties()
    {
        $baseStyle = (new StyleBuilder())->setFontSize(99)->setFontBold()->build();
        $currentStyle = (new StyleBuilder())->setFontName('Font')->setFontUnderline()->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertNotEquals(99, $currentStyle->getFontSize());
        $this->assertFalse($currentStyle->isFontBold());

        $this->assertEquals(99, $mergedStyle->getFontSize());
        $this->assertTrue($mergedStyle->isFontBold());
        $this->assertEquals('Font', $mergedStyle->getFontName());
        $this->assertTrue($mergedStyle->isFontUnderline());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentAndOnBase()
    {
        $baseStyle = (new StyleBuilder())->setFontSize(10)->build();
        $currentStyle = (new StyleBuilder())->setFontSize(99)->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertEquals(99, $mergedStyle->getFontSize());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentButNotOnBase()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->setFontItalic()->setFontStrikethrough()->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertFalse($baseStyle->isFontItalic());
        $this->assertFalse($baseStyle->isFontStrikethrough());

        $this->assertTrue($mergedStyle->isFontItalic());
        $this->assertTrue($mergedStyle->isFontStrikethrough());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldPreferBaseStylePropertyIfSetOnBaseButNotOnCurrent()
    {
        $baseStyle = (new StyleBuilder())
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->setShouldWrapText()
            ->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertFalse($currentStyle->isFontUnderline());
        $this->assertTrue($mergedStyle->isFontUnderline());

        $this->assertFalse($currentStyle->shouldWrapText());
        $this->assertTrue($mergedStyle->shouldWrapText());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnBaseNorCurrent()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertTrue($this->styleManager->serialize($baseStyle) === $this->styleManager->serialize($currentStyle));
        $this->assertTrue($this->styleManager->serialize($currentStyle) === $this->styleManager->serialize($mergedStyle));
    }

    /**
     * @return void
     */
    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnCurrentAndIsDefaultValueOnBase()
    {
        $baseStyle = (new StyleBuilder())
            ->setFontName(Style::DEFAULT_FONT_NAME)
            ->setFontSize(Style::DEFAULT_FONT_SIZE)
            ->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertTrue($this->styleManager->serialize($currentStyle) === $this->styleManager->serialize($mergedStyle));
    }

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
        $mergedStyle = $this->styleManager->merge($currentStyle, $baseStyle);

        $this->assertEquals(null, $currentStyle->getBorder(), 'Current style has no border');
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Style\Border', $baseStyle->getBorder(), 'Base style has a border');
        $this->assertInstanceOf('Box\Spout\Writer\Common\Entity\Style\Border', $mergedStyle->getBorder(), 'Merged style has a border');
    }
}
