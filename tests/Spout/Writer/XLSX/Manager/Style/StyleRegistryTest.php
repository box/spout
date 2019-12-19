<?php

namespace Box\Spout\Writer\XLSX\Manager\Style;

use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class StyleRegistryTest
 */
class StyleRegistryTest extends TestCase
{
    /**
     * @return StyleRegistry
     */
    private function getStyleRegistry()
    {
        $defaultStyle = (new StyleBuilder())->build();

        return new StyleRegistry($defaultStyle);
    }

    /**
     * @return void
     */
    public function testRegisterStyleAlsoRegistersFills()
    {
        $styleRegistry = $this->getStyleRegistry();

        $styleBlack = (new StyleBuilder())->setBackgroundColor(Color::BLACK)->build();
        $styleOrange = (new StyleBuilder())->setBackgroundColor(Color::ORANGE)->build();
        $styleOrangeBold = (new StyleBuilder())->setBackgroundColor(Color::ORANGE)->setFontBold()->build();
        $styleNoBackgroundColor = (new StyleBuilder())->setFontItalic()->build();

        $styleRegistry->registerStyle($styleBlack);
        $styleRegistry->registerStyle($styleOrange);
        $styleRegistry->registerStyle($styleOrangeBold);
        $styleRegistry->registerStyle($styleNoBackgroundColor);

        $this->assertCount(2, $styleRegistry->getRegisteredFills(), 'There should be 2 registered fills');

        $this->assertEquals(2, $styleRegistry->getFillIdForStyleId($styleBlack->getId()), 'First style with background color set should have index 2 (0 and 1 being reserved)');
        $this->assertEquals(3, $styleRegistry->getFillIdForStyleId($styleOrange->getId()), 'Second style with background color set - different from first style - should have index 3');
        $this->assertEquals(3, $styleRegistry->getFillIdForStyleId($styleOrangeBold->getId()), 'Style with background color already set should have the same index');
        $this->assertEquals(0, $styleRegistry->getFillIdForStyleId($styleNoBackgroundColor->getId()), 'Style with no background color should have index 0');
    }

    /**
     * @return void
     */
    public function testRegisterStyleAlsoRegistersBorders()
    {
        $styleRegistry = $this->getStyleRegistry();

        $borderLeft = (new BorderBuilder())->setBorderLeft()->build();
        $borderRight = (new BorderBuilder())->setBorderRight()->build();

        $styleBorderLeft = (new StyleBuilder())->setBorder($borderLeft)->build();
        $styleBorderRight = (new StyleBuilder())->setBorder($borderRight)->build();
        $styleBorderRightBold = (new StyleBuilder())->setBorder($borderRight)->setFontBold()->build();
        $styleNoBorder = (new StyleBuilder())->setFontItalic()->build();

        $styleRegistry->registerStyle($styleBorderLeft);
        $styleRegistry->registerStyle($styleBorderRight);
        $styleRegistry->registerStyle($styleBorderRightBold);
        $styleRegistry->registerStyle($styleNoBorder);

        $this->assertCount(2, $styleRegistry->getRegisteredBorders(), 'There should be 2 registered borders');

        $this->assertEquals(1, $styleRegistry->getBorderIdForStyleId($styleBorderLeft->getId()), 'First style with border set should have index 1 (0 is for the default style)');
        $this->assertEquals(2, $styleRegistry->getBorderIdForStyleId($styleBorderRight->getId()), 'Second style with border set - different from first style - should have index 2');
        $this->assertEquals(2, $styleRegistry->getBorderIdForStyleId($styleBorderRightBold->getId()), 'Style with border already set should have the same index');
        $this->assertEquals(0, $styleRegistry->getBorderIdForStyleId($styleNoBorder->getId()), 'Style with no border should have index 0');
    }

    /**
     * @return void
     */
    public function testRegisterStyleAlsoRegistersFormats()
    {
        $styleRegistry = $this->getStyleRegistry();

        $styleBuiltinFormat = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.00')//Builtin format
            ->build();

        $styleUserFormat = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.000')
            ->build();
        $styleNoFormat = (new StyleBuilder())->setFontItalic()->build();

        $styleRegistry->registerStyle($styleBuiltinFormat);
        $styleRegistry->registerStyle($styleUserFormat);
        $styleRegistry->registerStyle($styleNoFormat);

        $this->assertCount(2, $styleRegistry->getRegisteredFormats(), 'There should be 2 registered formats');

        $this->assertEquals(2, $styleRegistry->getFormatIdForStyleId($styleBuiltinFormat->getId()), 'First style with builtin format set should have index 2 (0 is for the default style)');
        $this->assertEquals(164, $styleRegistry->getFormatIdForStyleId($styleUserFormat->getId()), 'Second style with user format set should have index 164 (0 is for the default style)');

        $this->assertEquals(0, $styleRegistry->getFormatIdForStyleId($styleNoFormat->getId()), 'Style with no format should have index 0');
    }
}
