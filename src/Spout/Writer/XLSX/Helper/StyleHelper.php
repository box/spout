<?php

namespace Box\Spout\Writer\XLSX\Helper;

/**
 * Class StyleHelper
 * This class provides helper functions to manage styles
 *
 * @package Box\Spout\Writer\XLSX\Helper
 */
class StyleHelper
{
    /** @var array [SERIALIZED_STYLE] => [STYLE_ID] mapping table, keeping track of the registered styles */
    protected $serializedStyleToStyleIdMappingTable = [];

    /** @var array [STYLE_ID] => [STYLE] mapping table, keeping track of the registered styles */
    protected $styleIdToStyleMappingTable = [];

    /**
     * @param \Box\Spout\Writer\Style\Style $defaultStyle
     */
    public function __construct($defaultStyle)
    {
        // This ensures that the default style is the first one to be registered
        $this->registerStyle($defaultStyle);
    }

    /**
     * Registers the given style as a used style.
     * Duplicate styles won't be registered more than once.
     *
     * @param \Box\Spout\Writer\Style\Style $style The style to be registered
     * @return \Box\Spout\Writer\Style\Style The registered style, updated with an internal ID.
     */
    public function registerStyle($style)
    {
        $serializedStyle = $style->serialize();

        if (!$this->hasStyleAlreadyBeenRegistered($style)) {
            $nextStyleId = count($this->serializedStyleToStyleIdMappingTable);
            $style->setId($nextStyleId);

            $this->serializedStyleToStyleIdMappingTable[$serializedStyle] = $nextStyleId;
            $this->styleIdToStyleMappingTable[$nextStyleId] = $style;
        }

        return $this->getStyleFromSerializedStyle($serializedStyle);
    }

    /**
     * Returns whether the given style has already been registered.
     *
     * @param \Box\Spout\Writer\Style\Style $style
     * @return bool
     */
    protected function hasStyleAlreadyBeenRegistered($style)
    {
        $serializedStyle = $style->serialize();
        return array_key_exists($serializedStyle, $this->serializedStyleToStyleIdMappingTable);
    }

    /**
     * Returns the registered style associated to the given serialization.
     *
     * @param string $serializedStyle The serialized style from which the actual style should be fetched from
     * @return \Box\Spout\Writer\Style\Style
     */
    protected function getStyleFromSerializedStyle($serializedStyle)
    {
        $styleId = $this->serializedStyleToStyleIdMappingTable[$serializedStyle];
        return $this->styleIdToStyleMappingTable[$styleId];
    }

    /**
     * Apply additional styles if the given row needs it.
     * Typically, set "wrap text" if a cell contains a new line.
     *
     * @param \Box\Spout\Writer\Style\Style $style The original style
     * @param array $dataRow The row the style will be applied to
     * @return \Box\Spout\Writer\Style\Style The updated style
     */
    public function applyExtraStylesIfNeeded($style, $dataRow)
    {
        $updatedStyle = $this->applyWrapTextIfCellContainsNewLine($style, $dataRow);

        return $updatedStyle;
    }

    /**
     * Set the "wrap text" option if a cell of the given row contains a new line.
     *
     * @NOTE: There is a bug on the Mac version of Excel (2011 and below) where new lines
     *        are ignored even when the "wrap text" option is set. This only occurs with
     *        inline strings (shared strings do work fine).
     *        A workaround would be to encode "\n" as "_x000D_" but it does not work
     *        on the Windows version of Excel...
     *
     * @param \Box\Spout\Writer\Style\Style $style The original style
     * @param array $dataRow The row the style will be applied to
     * @return \Box\Spout\Writer\Style\Style The eventually updated style
     */
    protected function applyWrapTextIfCellContainsNewLine($style, $dataRow)
    {
        // if the "wrap text" option is already set, no-op
        if ($style->shouldWrapText()) {
            return $style;
        }

        foreach ($dataRow as $cell) {
            if (is_string($cell) && strpos($cell, "\n") !== false) {
                $style->setShouldWrapText();
                break;
            }
        }

        return $style;
    }

    /**
     * Returns the content of the "styles.xml" file, given a list of styles.
     * @return string
     */
    public function getStylesXMLFileContent()
    {
        $content = <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">

EOD;

        $content .= $this->getFontsSectionContent();
        $content .= $this->getFillsSectionContent();
        $content .= $this->getBordersSectionContent();
        $content .= $this->getCellStyleXfsSectionContent();
        $content .= $this->getCellXfsSectionContent();
        $content .= $this->getCellStylesSectionContent();

        $content .= <<<EOD
</styleSheet>
EOD;

        return $content;
    }

    /**
     * Returns the content of the "<fonts>" section.
     * @return string
     */
    protected function getFontsSectionContent()
    {
        $content = '    <fonts count="' . count($this->styleIdToStyleMappingTable) . '">' . PHP_EOL;

        foreach ($this->styleIdToStyleMappingTable as $style) {
            $content .= '        <font>' . PHP_EOL;

            if ($style->isFontBold()) {
                $content .= '            <b/>' . PHP_EOL;
            }
            if ($style->isFontItalic()) {
                $content .= '            <i/>' . PHP_EOL;
            }
            if ($style->isFontUnderline()) {
                $content .= '            <u/>' . PHP_EOL;
            }
            if ($style->isFontStrikethrough()) {
                $content .= '            <strike/>' . PHP_EOL;
            }

            $content .= '            <sz val="' . $style->getFontSize() . '"/>' . PHP_EOL;
            $content .= '            <name val="' . $style->getFontName() . '"/>' . PHP_EOL;
            $content .= '        </font>' . PHP_EOL;
        }

        $content .= '    </fonts>' . PHP_EOL;

        return $content;
    }

    /**
     * Returns the content of the "<fills>" section.
     *
     * @return string
     */
    protected function getFillsSectionContent()
    {
        return <<<EOD
    <fills count="1">
        <fill>
            <patternFill patternType="none"/>
        </fill>
    </fills>

EOD;
    }

    /**
     * Returns the content of the "<borders>" section.
     *
     * @return string
     */
    protected function getBordersSectionContent()
    {
        return <<<EOD
    <borders count="1">
        <border>
            <left/>
            <right/>
            <top/>
            <bottom/>
            <diagonal/>
        </border>
    </borders>

EOD;
    }

    /**
     * Returns the content of the "<cellStyleXfs>" section.
     *
     * @return string
     */
    protected function getCellStyleXfsSectionContent()
    {
        return <<<EOD
    <cellStyleXfs count="1">
        <xf borderId="0" fillId="0" fontId="0" numFmtId="0"/>
    </cellStyleXfs>

EOD;
    }

    /**
     * Returns the content of the "<cellXfs>" section.
     * @return string
     */
    protected function getCellXfsSectionContent()
    {
        $content = '    <cellXfs count="' . count($this->styleIdToStyleMappingTable) . '">' . PHP_EOL;

        foreach ($this->styleIdToStyleMappingTable as $styleId => $style) {
            $content .= '        <xf numFmtId="0" fontId="' . $styleId . '" fillId="0" borderId="0" xfId="0"';

            if ($style->shouldApplyFont()) {
                $content .= ' applyFont="1"';
            }

            if ($style->shouldWrapText()) {
                $content .= ' applyAlignment="1">' . PHP_EOL;
                $content .= '            <alignment wrapText="1"/>' . PHP_EOL;
                $content .= '        </xf>' . PHP_EOL;
            } else {
                $content .= '/>' . PHP_EOL;
            }
        }

        $content .= '    </cellXfs>' . PHP_EOL;

        return $content;
    }

    /**
     * Returns the content of the "<cellStyles>" section.
     *
     * @return string
     */
    protected function getCellStylesSectionContent()
    {
        return <<<EOD
    <cellStyles count="1">
        <cellStyle builtinId="0" name="Normal" xfId="0"/>
    </cellStyles>

EOD;
    }
}
