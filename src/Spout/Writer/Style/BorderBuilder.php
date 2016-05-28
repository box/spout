<?php

namespace Box\Spout\Writer\Style;

/**
 * Class BorderBuilder
 */
class BorderBuilder
{
    /**
     * @var Border
     */
    protected $border;

    public function __construct()
    {
        $this->border = new Border();
    }

    /**
     * @param string $style Border style @see BorderPart::allowedStyles
     * @param string $color Border A RGB color code
     * @param string $width Border width @see BorderPart::allowedWidths
     * @return self
     */
    public function setBorderTop($style = Border::STYLE_SOLID, $color = Color::BLACK, $width = Border::WIDTH_THICK)
    {
        $this->border->addPart(new BorderPart(Border::TOP, $style, $color, $width));
        return $this;
    }

    /**
     * @param string $style Border style @see BorderPart::allowedStyles
     * @param string $color Border A RGB color code
     * @param string $width Border width @see BorderPart::allowedWidths
     * @return self
     */
    public function setBorderRight($style = Border::STYLE_SOLID, $color = Color::BLACK, $width = Border::WIDTH_THICK)
    {
        $this->border->addPart(new BorderPart(Border::RIGHT, $style, $color, $width));
        return $this;
    }

    /**
     * @param string $style Border style @see BorderPart::allowedStyles
     * @param string $color Border A RGB color code
     * @param string $width Border width @see BorderPart::allowedWidths
     * @return self
     */
    public function setBorderBottom($style = Border::STYLE_SOLID, $color = Color::BLACK, $width = Border::WIDTH_THICK)
    {
        $this->border->addPart(new BorderPart(Border::BOTTOM, $style, $color, $width));
        return $this;
    }

    /**
     * @param string $style Border style @see BorderPart::allowedStyles
     * @param string $color Border A RGB color code
     * @param string $width Border width @see BorderPart::allowedWidths
     * @return self
     */
    public function setBorderLeft($style = Border::STYLE_SOLID, $color = Color::BLACK, $width = Border::WIDTH_THICK)
    {
        $this->border->addPart(new BorderPart(Border::LEFT, $style, $color, $width));
        return $this;
    }

    /**
     * @return Border
     */
    public function build()
    {
        return $this->border;
    }
}
