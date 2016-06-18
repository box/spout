<?php

namespace Box\Spout\Writer\Style;

class BorderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testValidInstance()
    {
        $noConstructorParams = new Border();
        $withConstructorParams = new Border([
            new BorderPart(Border::LEFT)
        ]);
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\Border\InvalidNameException
     */
    public function testInvalidBorderPart()
    {
        $invalidBorderPart = new BorderPart('invalid');
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\Border\InvalidStyleException
     */
    public function testInvalidBorderPartStyle()
    {
        $invalidBorderPartStyle = new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, 'invalid');
    }

    /**
     * @expectedException \Box\Spout\Writer\Exception\Border\InvalidWidthException
     */
    public function testInvalidBorderPartWidth()
    {
        $invalidBorderPartStyle = new BorderPart(Border::LEFT, Color::BLACK, 'invalid', Border::STYLE_DASHED);
    }

    /**
     * @return void
     */
    public function testNotMoreThanFourPartsPossible()
    {
        $border = new Border();
        $border
            ->addPart(new BorderPart(Border::LEFT))
            ->addPart(new BorderPart(Border::RIGHT))
            ->addPart(new BorderPart(Border::TOP))
            ->addPart(new BorderPart(Border::BOTTOM))
            ->addPart(new BorderPart(Border::LEFT));

        $this->assertEquals(4, count($border->getParts()), 'There should never be more than 4 border parts');
    }

    /**
     * @return void
     */
    public function testSetParts()
    {
        $border = new Border();
        $border->setParts([
            new BorderPart(Border::LEFT)
        ]);

        $this->assertEquals(1, count($border->getParts()), 'It should be possible to set the border parts');
    }

    /**
     * @return void
     */
    public function testBorderBuilderFluent()
    {
        $border = (new BorderBuilder())
            ->setBorderBottom()
            ->setBorderTop()
            ->setBorderLeft()
            ->setBorderRight()
            ->build();
        $this->assertEquals(4, count($border->getParts()), 'The border builder exposes a fluent interface');
    }

    /**
     * :D :S
     * @return void
     */
    public function testAnyCombinationOfAllowedBorderPartsParams()
    {
        $color = Color::BLACK;
        foreach (BorderPart::getAllowedNames() as $allowedName) {
            foreach (BorderPart::getAllowedStyles() as $allowedStyle) {
                foreach (BorderPart::getAllowedWidths() as $allowedWidth) {
                    $borderPart = new BorderPart($allowedName, $color, $allowedWidth, $allowedStyle);
                    $border = new Border();
                    $border->addPart($borderPart);
                    $this->assertEquals(1, count($border->getParts()));

                    /** @var $part BorderPart */
                    $part = $border->getParts()[$allowedName];

                    $this->assertEquals($allowedStyle, $part->getStyle());
                    $this->assertEquals($allowedWidth, $part->getWidth());
                    $this->assertEquals($color, $part->getColor());
                }
            }
        }
    }
}
