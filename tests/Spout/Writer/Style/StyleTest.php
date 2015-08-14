<?php

namespace Box\Spout\Writer\Style;

/**
 * Class StyleTest
 *
 * @package Box\Spout\Writer\Style
 */
class StyleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testSerializeShouldNotTakeIntoAccountId()
    {
        $style1 = (new StyleBuilder())->setFontBold()->build();
        $style1->setId(1);

        $style2 = (new StyleBuilder())->setFontBold()->build();
        $style2->setId(2);

        $this->assertEquals($style1->serialize(), $style2->serialize());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldReturnACopy()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

        $this->assertNotSame($mergedStyle, $currentStyle);
    }

    /**
     * @return void
     */
    public function testMergeWithShouldMergeSetProperties()
    {
        $baseStyle = (new StyleBuilder())->setFontSize(99)->setFontBold()->build();
        $currentStyle = (new StyleBuilder())->setFontName('Font')->setFontUnderline()->build();
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

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
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

        $this->assertEquals(99, $mergedStyle->getFontSize());
    }

    /**
     * @return void
     */
    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentButNotOnBase()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->setFontItalic()->setFontStrikethrough()->build();
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

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
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

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
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

        $this->assertTrue($baseStyle->serialize() === $currentStyle->serialize());
        $this->assertTrue($currentStyle->serialize() === $mergedStyle->serialize());
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
        $mergedStyle = $currentStyle->mergeWith($baseStyle);

        $this->assertTrue($currentStyle->serialize() === $mergedStyle->serialize());
    }
}
