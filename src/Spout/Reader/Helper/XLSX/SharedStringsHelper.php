<?php

namespace Box\Spout\Reader\Helper\XLSX;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\FileSystemHelper;
use Box\Spout\Reader\Exception\SharedStringNotFoundException;
use Box\Spout\Reader\Helper\XLSX\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\Helper\XLSX\SharedStringsCaching\CachingStrategyInterface;

/**
 * Class SharedStringsHelper
 * This class provides helper functions for reading sharedStrings XML file
 *
 * @package Box\Spout\Reader\Helper\XLSX
 */
class SharedStringsHelper
{
    /** Path of sharedStrings XML file inside the XLSX file */
    const SHARED_STRINGS_XML_FILE_PATH = 'xl/sharedStrings.xml';

    /** Main namespace for the sharedStrings.xml file */
    const MAIN_NAMESPACE_FOR_SHARED_STRINGS_XML = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /** @var string Temporary folder where the temporary files to store shared strings will be stored */
    protected $tempFolder;

    /** @var CachingStrategyInterface The best caching strategy for storing shared strings */
    protected $cachingStrategy;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string|void $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     */
    public function __construct($filePath, $tempFolder = null)
    {
        $this->filePath = $filePath;
        $this->tempFolder = $tempFolder;
    }

    /**
     * Returns whether the XLSX file contains a shared strings XML file
     *
     * @return bool
     */
    public function hasSharedStrings()
    {
        $hasSharedStrings = false;
        $zip = new \ZipArchive();

        if ($zip->open($this->filePath) === true) {
            $hasSharedStrings = ($zip->locateName(self::SHARED_STRINGS_XML_FILE_PATH) !== false);
            $zip->close();
        }

        return $hasSharedStrings;
    }

    /**
     * Builds an in-memory array containing all the shared strings of the worksheet.
     * All the strings are stored in a XML file, located at 'xl/sharedStrings.xml'.
     * It is then accessed by the worksheet data, via the string index in the built table.
     *
     * More documentation available here: http://msdn.microsoft.com/en-us/library/office/gg278314.aspx
     *
     * The XML file can be really big with worksheets containing a lot of data. That is why
     * we need to use a XML reader that provides streaming like the XMLReader library.
     * Please note that SimpleXML does not provide such a functionality but since it is faster
     * and more handy to parse few XML nodes, it is used in combination with XMLReader for that purpose.
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If sharedStrings.xml can't be read
     */
    public function extractSharedStrings()
    {
        $xmlReader = new \XMLReader();
        $sharedStringIndex = 0;
        $escaper = new \Box\Spout\Common\Escaper\XLSX();

        $sharedStringsFilePath = $this->getSharedStringsFilePath();
        if ($xmlReader->open($sharedStringsFilePath, null, LIBXML_NOENT|LIBXML_NONET) === false) {
            throw new IOException('Could not open "' . self::SHARED_STRINGS_XML_FILE_PATH . '".');
        }

        $sharedStringsUniqueCount = $this->getSharedStringsUniqueCount($xmlReader);
        $this->cachingStrategy = $this->getBestSharedStringsCachingStrategy($sharedStringsUniqueCount);

        while ($xmlReader->read() && $xmlReader->name !== 'si') {
            // do nothing until a 'si' tag is reached
        }

        while ($xmlReader->name === 'si') {
            $node = new \SimpleXMLElement($xmlReader->readOuterXml());
            $node->registerXPathNamespace('ns', self::MAIN_NAMESPACE_FOR_SHARED_STRINGS_XML);

            // removes nodes that should not be read, like the pronunciation of the Kanji characters
            $cleanNode = $this->removeSuperfluousTextNodes($node);

            // find all text nodes 't'; there can be multiple if the cell contains formatting
            $textNodes = $cleanNode->xpath('//ns:t');

            $textValue = '';
            foreach ($textNodes as $textNode) {
                if ($this->shouldPreserveWhitespace($textNode)) {
                    $textValue .= $textNode->__toString();
                } else {
                    $textValue .= trim($textNode->__toString());
                }
            }

            $unescapedTextValue = $escaper->unescape($textValue);
            $this->cachingStrategy->addStringForIndex($unescapedTextValue, $sharedStringIndex);

            $sharedStringIndex++;

            // jump to the next 'si' tag
            $xmlReader->next('si');
        }

        $this->cachingStrategy->closeCache();

        $xmlReader->close();
    }

    /**
     * @return string The path to the shared strings XML file
     */
    protected function getSharedStringsFilePath()
    {
        return 'zip://' . $this->filePath . '#' . self::SHARED_STRINGS_XML_FILE_PATH;
    }

    /**
     * Returns the shared strings unique count, as specified in <sst> tag.
     *
     * @param \XMLReader $xmlReader XMLReader instance
     * @return int Number of unique shared strings in the sharedStrings.xml file
     */
    protected function getSharedStringsUniqueCount($xmlReader)
    {
        $xmlReader->next('sst');
        return intval($xmlReader->getAttribute('uniqueCount'));
    }

    /**
     * Returns the best shared strings caching strategy.
     *
     * @param int $sharedStringsUniqueCount
     * @return CachingStrategyInterface
     */
    protected function getBestSharedStringsCachingStrategy($sharedStringsUniqueCount)
    {
        $factory = new CachingStrategyFactory();
        return $factory->getBestCachingStrategy($sharedStringsUniqueCount, $this->tempFolder);
    }

    /**
     * Removes nodes that should not be read, like the pronunciation of the Kanji characters.
     * By keeping them, their text content would be added to the read string.
     *
     * @param \SimpleXMLElement $parentNode Parent node that may contain nodes to remove
     * @return \SimpleXMLElement Cleaned parent node
     */
    protected function removeSuperfluousTextNodes($parentNode)
    {
        $tagsToRemove = [
            'rPh', // Pronunciation of the text
        ];

        foreach ($tagsToRemove as $tagToRemove) {
            $xpath = '//ns:' . $tagToRemove;
            $nodesToRemove = $parentNode->xpath($xpath);

            foreach ($nodesToRemove as $nodeToRemove) {
                // This is how to remove a node from the XML
                unset($nodeToRemove[0]);
            }
        }

        return $parentNode;
    }

    /**
     * If the text node has the attribute 'xml:space="preserve"', then preserve whitespace.
     *
     * @param \SimpleXMLElement $textNode The text node element (<t>) whitespace may be preserved
     * @return bool Whether whitespace should be preserved
     */
    protected function shouldPreserveWhitespace($textNode)
    {
        $shouldPreserveWhitespace = false;

        $attributes = $textNode->attributes('xml', true);
        if ($attributes) {
            foreach ($attributes as $attributeName => $attributeValue) {
                if ($attributeName === 'space' && $attributeValue->__toString() === 'preserve') {
                    $shouldPreserveWhitespace = true;
                    break;
                }
            }
        }

        return $shouldPreserveWhitespace;
    }

    /**
     * Returns the shared string at the given index, using the previously chosen caching strategy.
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return string The shared string at the given index
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If no shared string found for the given index
     */
    public function getStringAtIndex($sharedStringIndex)
    {
        return $this->cachingStrategy->getStringAtIndex($sharedStringIndex);
    }

    /**
     * Destroys the cache, freeing memory and removing any created artifacts
     *
     * @return void
     */
    public function cleanup()
    {
        if ($this->cachingStrategy) {
            $this->cachingStrategy->clearCache();
        }
    }
}
