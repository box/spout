<?php

namespace Box\Spout\Writer\XLSX\Helper;

use Box\Spout\Writer\Common\Helper\AbstractStyleHelper;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\Style;

/**
 * Class StyleHelper
 * This class provides helper functions to manage styles
 *
 * @package Box\Spout\Writer\XLSX\Helper
 */
class StyleHelper extends AbstractStyleHelper
{

    /**
     * @see https://msdn.microsoft.com/en-us/library/ff529597(v=office.12).aspx
     * @var array Mapping between built-in format and the associated numFmtId
     */
    protected static $builtinNumFormatToIdMapping = [

        'General' => 0,
        '0' => 1,
        '0.00' => 2,
        '#,##0' => 3,
        '#,##0.00' => 4,
        '$#,##0,\-$#,##0' => 5,
        '$#,##0,[Red]\-$#,##0' => 6,
        '$#,##0.00,\-$#,##0.00' => 7,
        '$#,##0.00,[Red]\-$#,##0.00' => 8,
        '0%' => 9,
        '0.00%' => 10,
        '0.00E+00' => 11,
        '# ?/?' => 12,
        '# ??/??' => 13,
        'mm-dd-yy' => 14,
        'd-mmm-yy' => 15,
        'd-mmm' => 16,
        'mmm-yy' => 17,
        'h:mm AM/PM' => 18,
        'h:mm:ss AM/PM' => 19,
        'h:mm' => 20,
        'h:mm:ss' => 21,
        'm/d/yy h:mm' => 22,

        '#,##0 ,(#,##0)' => 37,
        '#,##0 ,[Red](#,##0)' => 38,
        '#,##0.00,(#,##0.00)' => 39,
        '#,##0.00,[Red](#,##0.00)' => 40,

        '_("$"* #,##0.00_),_("$"* \(#,##0.00\),_("$"* "-"??_),_(@_)' => 44,
        'mm:ss' => 45,
        '[h]:mm:ss' => 46,
        'mm:ss.0' => 47,

        '##0.0E+0' => 48,
        '@' => 49,

        '[$-404]e/m/d' => 27,
        'm/d/yy' => 30,
        't0' => 59,
        't0.00' => 60,
        't#,##0' => 61,
        't#,##0.00' => 62,
        't0%' => 67,
        't0.00%' => 68,
        't# ?/?' => 69,
        't# ??/??' => 70,
    ];

    /**
     * @var array
     */
    protected $registeredFills = [];

    /**
     * @var array [STYLE_ID] => [FILL_ID] maps a style to a fill declaration
     */
    protected $styleIdToFillMappingTable = [];

    /**
     * @var array
     */
    protected $registeredFormats = [];

    /**
     * @var array [STYLE_ID] => [FORMAT_ID] maps a style to a format declaration
     */
    protected $styleIdToFormatsMappingTable = [];

    /**
     * Excel preserves two default fills with index 0 and 1
     * Since Excel is the dominant vendor - we play along here
     *
     * @var int The fill index counter for custom fills.
     */
    protected $fillIndex = 2;

    /**
     * If the numFmtId is lower than 0xA4 (164 in decimal)
     * then it's a built-in number format.
     * Since Excel is the dominant vendor - we play along here
     *
     * @var int The fill index counter for custom fills.
     */
    protected $formatIndex = 164;

    /**
     * @var array
     */
    protected $registeredBorders = [];

    /**
     * @var array [STYLE_ID] => [BORDER_ID] maps a style to a border declaration
     */
    protected $styleIdToBorderMappingTable = [];

    /**
     * XLSX specific operations on the registered styles
     *
     * @param \Box\Spout\Writer\Style\Style $style
     * @return \Box\Spout\Writer\Style\Style
     */
    public function registerStyle($style)
    {
        $registeredStyle = parent::registerStyle($style);
        $this->registerFill($registeredStyle);
        $this->registerFormat($registeredStyle);
        $this->registerBorder($registeredStyle);

        return $registeredStyle;
    }

    /**
     * Register a format definition
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    protected function registerFormat($style)
    {
        $styleId = $style->getId();

        $format = $style->getFormat();

        if ($format) {
            $isFormatRegistered = isset($this->registeredFormats[$format]);

            // We need to track the already registered format definitions
            if ($isFormatRegistered) {
                $registeredStyleId = $this->registeredFormats[$format];
                $registeredFormatId = $this->styleIdToFormatsMappingTable[$registeredStyleId];
                $this->styleIdToFormatsMappingTable[$styleId] = $registeredFormatId;
            } else {
                $this->registeredFormats[$format] = $styleId;
                if (isset(self::$builtinNumFormatToIdMapping[$format])) {
                    $id = self::$builtinNumFormatToIdMapping[$format];
                } else {
                    $id = $this->formatIndex++;
                }
                $this->styleIdToFormatsMappingTable[$styleId] = $id;
            }

        } else {
            // The formatId maps a style to a format declaration
            // When there is no format definition - we default to 0 ( General )
            $this->styleIdToFormatsMappingTable[$styleId] = 0;
        }
    }

    /**
     * Register a fill definition
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    protected function registerFill($style)
    {
        $styleId = $style->getId();

        // Currently - only solid backgrounds are supported
        // so $backgroundColor is a scalar value (RGB Color)
        $backgroundColor = $style->getBackgroundColor();

        if ($backgroundColor) {
            $isBackgroundColorRegistered = isset($this->registeredFills[$backgroundColor]);

            // We need to track the already registered background definitions
            if ($isBackgroundColorRegistered) {
                $registeredStyleId = $this->registeredFills[$backgroundColor];
                $registeredFillId = $this->styleIdToFillMappingTable[$registeredStyleId];
                $this->styleIdToFillMappingTable[$styleId] = $registeredFillId;
            } else {
                $this->registeredFills[$backgroundColor] = $styleId;
                $this->styleIdToFillMappingTable[$styleId] = $this->fillIndex++;
            }

        } else {
            // The fillId maps a style to a fill declaration
            // When there is no background color definition - we default to 0
            $this->styleIdToFillMappingTable[$styleId] = 0;
        }
    }

    /**
     * Register a border definition
     *
     * @param \Box\Spout\Writer\Style\Style $style
     */
    protected function registerBorder($style)
    {
        $styleId = $style->getId();

        if ($style->shouldApplyBorder()) {
            $border = $style->getBorder();
            $serializedBorder = serialize($border);

            $isBorderAlreadyRegistered = isset($this->registeredBorders[$serializedBorder]);

            if ($isBorderAlreadyRegistered) {
                $registeredStyleId = $this->registeredBorders[$serializedBorder];
                $registeredBorderId = $this->styleIdToBorderMappingTable[$registeredStyleId];
                $this->styleIdToBorderMappingTable[$styleId] = $registeredBorderId;
            } else {
                $this->registeredBorders[$serializedBorder] = $styleId;
                $this->styleIdToBorderMappingTable[$styleId] = count($this->registeredBorders);
            }

        } else {
            // If no border should be applied - the mapping is the default border: 0
            $this->styleIdToBorderMappingTable[$styleId] = 0;
        }
    }


    /**
     * For empty cells, we can specify a style or not. If no style are specified,
     * then the software default will be applied. But sometimes, it may be useful
     * to override this default style, for instance if the cell should have a
     * background color different than the default one or some borders
     * (fonts property don't really matter here).
     *
     * @param int $styleId
     * @return bool Whether the cell should define a custom style
     */
    public function shouldApplyStyleOnEmptyCell($styleId)
    {
        $hasStyleCustomFill = (isset($this->styleIdToFillMappingTable[$styleId]) && $this->styleIdToFillMappingTable[$styleId] !== 0);
        $hasStyleCustomBorders = (isset($this->styleIdToBorderMappingTable[$styleId]) && $this->styleIdToBorderMappingTable[$styleId] !== 0);
        $hasStyleCustomFormats = (isset($this->styleIdToFormatsMappingTable[$styleId]) && $this->styleIdToFormatsMappingTable[$styleId] !== 0);

        return ($hasStyleCustomFill || $hasStyleCustomBorders || $hasStyleCustomFormats);
    }


    /**
     * Returns the content of the "styles.xml" file, given a list of styles.
     *
     * @return string
     */
    public function getStylesXMLFileContent()
    {
        $content = <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
EOD;

        $content .= $this->getNumFmtsSectionContent();
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
     *
     * @return string
     */
    protected function getNumFmtsSectionContent()
    {
        $tags = [];
        foreach ($this->registeredFormats as $styleId) {
            $numFmtId = $this->styleIdToFormatsMappingTable[$styleId];

            //Built-in formats do not need to be declared, skip them
            if ($numFmtId < 164) {
                continue;
            }

            /** @var Style $style */
            $style = $this->styleIdToStyleMappingTable[$styleId];
            $format = $style->getFormat();
            $tags[] = '<numFmt numFmtId="' . $numFmtId . '" formatCode="' . $format . '"/>';
        }
        $content = '<numFmts count="' . count($tags) . '">';
        $content .= implode('', $tags);
        $content .= '</numFmts>';

        return $content;
    }

    /**
     * Returns the content of the "<fonts>" section.
     *
     * @return string
     */
    protected function getFontsSectionContent()
    {
        $content = '<fonts count="' . count($this->styleIdToStyleMappingTable) . '">';

        /** @var \Box\Spout\Writer\Style\Style $style */
        foreach ($this->getRegisteredStyles() as $style) {
            $content .= '<font>';

            $content .= '<sz val="' . $style->getFontSize() . '"/>';
            $content .= '<color rgb="' . Color::toARGB($style->getFontColor()) . '"/>';
            $content .= '<name val="' . $style->getFontName() . '"/>';

            if ($style->isFontBold()) {
                $content .= '<b/>';
            }
            if ($style->isFontItalic()) {
                $content .= '<i/>';
            }
            if ($style->isFontUnderline()) {
                $content .= '<u/>';
            }
            if ($style->isFontStrikethrough()) {
                $content .= '<strike/>';
            }

            $content .= '</font>';
        }

        $content .= '</fonts>';

        return $content;
    }

    /**
     * Returns the content of the "<fills>" section.
     *
     * @return string
     */
    protected function getFillsSectionContent()
    {
        // Excel reserves two default fills
        $fillsCount = count($this->registeredFills) + 2;
        $content = sprintf('<fills count="%d">', $fillsCount);

        $content .= '<fill><patternFill patternType="none"/></fill>';
        $content .= '<fill><patternFill patternType="gray125"/></fill>';

        // The other fills are actually registered by setting a background color
        foreach ($this->registeredFills as $styleId) {
            /** @var Style $style */
            $style = $this->styleIdToStyleMappingTable[$styleId];

            $backgroundColor = $style->getBackgroundColor();
            $content .= sprintf(
                '<fill><patternFill patternType="solid"><fgColor rgb="%s"/></patternFill></fill>',
                $backgroundColor
            );
        }

        $content .= '</fills>';

        return $content;
    }

    /**
     * Returns the content of the "<borders>" section.
     *
     * @return string
     */
    protected function getBordersSectionContent()
    {

        // There is one default border with index 0
        $borderCount = count($this->registeredBorders) + 1;

        $content = '<borders count="' . $borderCount . '">';

        // Default border starting at index 0
        $content .= '<border><left/><right/><top/><bottom/></border>';

        foreach ($this->registeredBorders as $styleId) {
            /** @var \Box\Spout\Writer\Style\Style $style */
            $style = $this->styleIdToStyleMappingTable[$styleId];
            $border = $style->getBorder();
            $content .= '<border>';

            // @link https://github.com/box/spout/issues/271
            $sortOrder = ['left', 'right', 'top', 'bottom'];

            foreach ($sortOrder as $partName) {
                if ($border->hasPart($partName)) {
                    /** @var $part \Box\Spout\Writer\Style\BorderPart */
                    $part = $border->getPart($partName);
                    $content .= BorderHelper::serializeBorderPart($part);
                }

            }

            $content .= '</border>';
        }

        $content .= '</borders>';

        return $content;
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
     *
     * @return string
     */
    protected function getCellXfsSectionContent()
    {
        $registeredStyles = $this->getRegisteredStyles();

        $content = '<cellXfs count="' . count($registeredStyles) . '">';

        foreach ($registeredStyles as $style) {
            $styleId = $style->getId();
            $fillId = $this->styleIdToFillMappingTable[$styleId];
            $borderId = $this->styleIdToBorderMappingTable[$styleId];
            $numFmtId = $this->styleIdToFormatsMappingTable[$styleId];

            $content .= '<xf numFmtId="' . $numFmtId . '" fontId="' . $styleId . '" fillId="' . $fillId . '" borderId="' . $borderId . '" xfId="0"';

            if ($style->shouldApplyFont()) {
                $content .= ' applyFont="1"';
            }

            $content .= sprintf(' applyBorder="%d"', $style->shouldApplyBorder() ? 1 : 0);

            if ($style->shouldWrapText()) {
                $content .= ' applyAlignment="1">';
                $content .= '<alignment wrapText="1"/>';
                $content .= '</xf>';
            } else {
                $content .= '/>';
            }
        }

        $content .= '</cellXfs>';

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
