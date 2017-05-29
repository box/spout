<?php

namespace Box\Spout\Writer\Common\Helper;

use Box\Spout\Writer\Common\Entity\Style\Style;
use Box\Spout\Writer\Common\Manager\StyleManager;

/**
 * Class StyleHelperAbstract
 * This class provides helper functions to manage styles
 *
 * @package Box\Spout\Writer\Common\Helper
 */
abstract class StyleHelperAbstract implements StyleHelperInterface
{
    /** @var StyleManager Style manager */
    private $styleManager;

    /** @var array [SERIALIZED_STYLE] => [STYLE_ID] mapping table, keeping track of the registered styles */
    protected $serializedStyleToStyleIdMappingTable = [];

    /** @var array [STYLE_ID] => [STYLE] mapping table, keeping track of the registered styles */
    protected $styleIdToStyleMappingTable = [];

    /**
     * @param Style $defaultStyle
     * @param StyleManager $styleManager
     */
    public function __construct(Style $defaultStyle, StyleManager $styleManager)
    {
        $this->styleManager = $styleManager;

        // This ensures that the default style is the first one to be registered
        $this->registerStyle($defaultStyle);
    }

    /**
     * Registers the given style as a used style.
     * Duplicate styles won't be registered more than once.
     *
     * @param Style $style The style to be registered
     * @return Style The registered style, updated with an internal ID.
     */
    public function registerStyle($style)
    {
        $serializedStyle = $this->styleManager->serialize($style);

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
     * @param Style $style
     * @return bool
     */
    protected function hasStyleAlreadyBeenRegistered($style)
    {
        $serializedStyle = $this->styleManager->serialize($style);

        // Using isset here because it is way faster than array_key_exists...
        return isset($this->serializedStyleToStyleIdMappingTable[$serializedStyle]);
    }

    /**
     * Returns the registered style associated to the given serialization.
     *
     * @param string $serializedStyle The serialized style from which the actual style should be fetched from
     * @return Style
     */
    protected function getStyleFromSerializedStyle($serializedStyle)
    {
        $styleId = $this->serializedStyleToStyleIdMappingTable[$serializedStyle];
        return $this->styleIdToStyleMappingTable[$styleId];
    }

    /**
     * @return Style[] List of registered styles
     */
    protected function getRegisteredStyles()
    {
        return array_values($this->styleIdToStyleMappingTable);
    }

    /**
     * Returns the default style
     *
     * @return Style Default style
     */
    protected function getDefaultStyle()
    {
        // By construction, the default style has ID 0
        return $this->styleIdToStyleMappingTable[0];
    }

    /**
     * Apply additional styles if the given row needs it.
     * Typically, set "wrap text" if a cell contains a new line.
     *
     * @param Style $style The original style
     * @param array $dataRow The row the style will be applied to
     * @return Style The updated style
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
     * @param Style $style The original style
     * @param array $dataRow The row the style will be applied to
     * @return Style The eventually updated style
     */
    protected function applyWrapTextIfCellContainsNewLine($style, $dataRow)
    {
        // if the "wrap text" option is already set, no-op
        if ($style->hasSetWrapText()) {
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
}
