<?php

namespace Box\Spout\Writer\Style;

/**
 * Class StyleBuilder
 * Builder to create new styles
 *
 * @package Box\Spout\Writer\Style
 */
class StyleBuilder
{
    /** @var Style Style to be created */
    protected $style;

    /**
     *
     */
    public function __construct()
    {
        $this->style = new Style();
    }

    /**
     * Makes the font bold.
     *
     * @return StyleBuilder
     */
    public function setFontBold()
    {
        $this->style->setFontBold();
        return $this;
    }

    /**
     * Makes the font italic.
     *
     * @return StyleBuilder
     */
    public function setFontItalic()
    {
        $this->style->setFontItalic();
        return $this;
    }

    /**
     * Makes the font underlined.
     *
     * @return StyleBuilder
     */
    public function setFontUnderline()
    {
        $this->style->setFontUnderline();
        return $this;
    }

    /**
     * Makes the font struck through.
     *
     * @return StyleBuilder
     */
    public function setFontStrikeThrough()
    {
        $this->style->setFontStrikeThrough();
        return $this;
    }

    /**
     * Sets the font size.
     *
     * @param int $fontSize Font size, in pixels
     * @return StyleBuilder
     */
    public function setFontSize($fontSize)
    {
        $this->style->setFontSize($fontSize);
        return $this;
    }

    /**
     * Sets the font name.
     *
     * @param string $fontName Name of the font to use
     * @return StyleBuilder
     */
    public function setFontName($fontName)
    {
        $this->style->setFontName($fontName);
        return $this;
    }

    /**
     * Makes the text wrap in the cell if it's too long or
     * on multiple lines.
     *
     * @return StyleBuilder
     */
    public function setShouldWrapText()
    {
        $this->style->setShouldWrapText();
        return $this;
    }

    /**
     * Returns the configured style. The style is cached and can be reused.
     *
     * @return Style
     */
    public function build()
    {
        return $this->style;
    }
}
