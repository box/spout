<?php

namespace Box\Spout\Writer\Style;

/**
 * Class ColorTest
 *
 * @package Box\Spout\Writer\Style
 */
class ColorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestRGB()
    {

        return [
            [0, 0, 0, Color::BLACK],
            [255, 255, 255, Color::WHITE],
            [255, 0, 0, Color::RED],
            [192, 0, 0, Color::DARK_RED],
            [255, 192, 0, Color::ORANGE],
            [255, 255, 0, Color::YELLOW],
            [146, 208, 64, Color::LIGHT_GREEN],
            [0, 176, 80, Color::GREEN],
            [0, 176, 224, Color::LIGHT_BLUE],
            [0, 112, 192, Color::BLUE],
            [0, 32, 96, Color::DARK_BLUE],
            [112, 48, 160, Color::PURPLE],
            [0, 0, 0, '000000'],
            [255, 255, 255, 'FFFFFF'],
            [255, 0, 0, 'FF0000'],
            [0, 128, 0, '008000'],
            [0, 255, 0, '00FF00'],
            [0, 0, 255, '0000FF'],
            [128, 22, 43, '80162B'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestRGB
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param string $expectedColor
     * @return void
     */
    public function testRGB($red, $green, $blue, $expectedColor)
    {
        $color = Color::rgb($red, $green, $blue);
        $this->assertEquals($expectedColor, $color);
    }

    /**
     * @return array
     */
    public function dataProviderForTestRGBAInvalidColorComponents()
    {
        return [
            [-1, 0, 0],
            [0, -1, 0],
            [0, 0, -1],
            [999, 0, 0],
            [0, 999, 0],
            [0, 0, 999],
            [null, 0, 0],
            [0, null, 0],
            [0, 0, null],
            ['1', 0, 0],
            [0, '1', 0],
            [0, 0, '1'],
            [true, 0, 0],
            [0, true, 0],
            [0, 0, true],
        ];
    }

    /**
     * @dataProvider dataProviderForTestRGBAInvalidColorComponents
     * @expectedException \Box\Spout\Writer\Exception\InvalidColorException
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return void
     */
    public function testRGBInvalidColorComponents($red, $green, $blue)
    {
        Color::rgb($red, $green, $blue);
    }
}
