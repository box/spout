<?php

namespace Box\Spout\Writer\ODS\Manager\Style;

use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;

/**
 * Class StyleRegistryTest
 *
 * @package Box\Spout\Writer\ODS\Manager\Style
 */
class StyleRegistryTest extends \PHPUnit_Framework_TestCase
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
    public function testRegisterStyleKeepsTrackOfUsedFonts()
    {
        $styleRegistry = $this->getStyleRegistry();

        $this->assertEquals(1, count($styleRegistry->getUsedFonts()), 'There should only be the default font name');

        $style1 = (new StyleBuilder())->setFontName("MyFont1")->build();
        $styleRegistry->registerStyle($style1);

        $style2 = (new StyleBuilder())->setFontName("MyFont2")->build();
        $styleRegistry->registerStyle($style2);

        $this->assertEquals(3, count($styleRegistry->getUsedFonts()), 'There should be 3 fonts registered');
    }
}
