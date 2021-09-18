<?php

namespace Box\Spout\Writer\Common\Manager\Style;

use Box\Spout\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class StyleRegistryTest
 */
class StyleRegistryTest extends TestCase
{
    /** @var Style */
    private $defaultStyle;

    /** @var StyleRegistry */
    private $styleRegistry;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->defaultStyle = (new StyleBuilder())->build();
        $this->styleRegistry = new StyleRegistry($this->defaultStyle);
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

        $this->assertEquals($this->styleRegistry->serialize($style1), $this->styleRegistry->serialize($style2));
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

        $registeredStyle1 = $this->styleRegistry->registerStyle($style1);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style2);

        $this->assertEquals(1, $registeredStyle1->getId());
        $this->assertEquals(2, $registeredStyle2->getId());
    }

    /**
     * @return void
     */
    public function testRegisterStyleShouldReuseAlreadyRegisteredStyles()
    {
        $style = (new StyleBuilder())->setFontBold()->build();

        $registeredStyle1 = $this->styleRegistry->registerStyle($style);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style);

        $this->assertEquals(1, $registeredStyle1->getId());
        $this->assertEquals(1, $registeredStyle2->getId());
    }
}
