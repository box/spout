<?php

namespace Box\Spout\Common\Entity\Style;

use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Exception\Border\InvalidNameException;
use Box\Spout\Writer\Exception\Border\InvalidStyleException;
use Box\Spout\Writer\Exception\Border\InvalidWidthException;
use PHPUnit\Framework\TestCase;

/**
 * Class BorderTest
 */
class BorderTest extends TestCase
{
    /**
     * @return void
     */
    public function testValidInstance()
    {
        $noConstructorParams = new Border();
        $withConstructorParams = new Border([
            new BorderPart(Border::LEFT),
        ]);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function testInvalidBorderPart()
    {
        $this->expectException(InvalidNameException::class);

        new BorderPart('invalid');
    }

    /**
     * @return void
     */
    public function testInvalidBorderPartStyle()
    {
        $this->expectException(InvalidStyleException::class);

        new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, 'invalid');
    }

    /**
     * @return void
     */
    public function testInvalidBorderPartWidth()
    {
        $this->expectException(InvalidWidthException::class);

        new BorderPart(Border::LEFT, Color::BLACK, 'invalid', Border::STYLE_DASHED);
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

        $this->assertCount(4, $border->getParts(), 'There should never be more than 4 border parts');
    }

    /**
     * @return void
     */
    public function testSetParts()
    {
        $border = new Border();
        $border->setParts([
            new BorderPart(Border::LEFT),
        ]);

        $this->assertCount(1, $border->getParts(), 'It should be possible to set the border parts');
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
        $this->assertCount(4, $border->getParts(), 'The border builder exposes a fluent interface');
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
                    $this->assertCount(1, $border->getParts());

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
