<?php

namespace Box\Spout\Reader\Helper\XLSX;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Helper\FileSystemHelper;
use Box\Spout\Reader\Exception\SharedStringNotFoundException;

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

    /**
     * To avoid running out of memory when extracting the shared strings, they will be saved to temporary files
     * instead of in memory. Then, when accessing a string, the corresponding file contents will be loaded in memory
     * and the string will be quickly retrieved.
     * The performance bottleneck is not when creating these temporary files, but rather when loading their content.
     * Because the contents of the last loaded file stays in memory until another file needs to be loaded, it works
     * best when the indexes of the shared strings are sorted in the sheet data.
     * 10,000 was chosen because it creates small files that are fast to be loaded in memory.
     */
    const MAX_NUM_STRINGS_PER_TEMP_FILE = 10000;

    /** @var string Path of the XLSX file being read */
    protected $filePath;

    /** @var string Temporary folder where the temporary files to store shared strings will be stored */
    protected $tempFolder;

    /** @var \Box\Spout\Writer\Helper\XLSX\FileSystemHelper Helper to perform file system operations */
    protected $fileSystemHelper;

    /** @var resource Pointer to the last temp file a shared string was written to */
    protected $tempFilePointer;

    /**
     * @var string Path of the temporary file whose contents is currently stored in memory
     * @see MAX_NUM_STRINGS_PER_TEMP_FILE
     */
    protected $inMemoryTempFilePath;

    /**
     * @var string Contents of the temporary file that was last read
     * @see MAX_NUM_STRINGS_PER_TEMP_FILE
     */
    protected $inMemoryTempFileContents;

    /**
     * @param string $filePath Path of the XLSX file being read
     * @param string|void $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     */
    public function __construct($filePath, $tempFolder = null)
    {
        $this->filePath = $filePath;

        $rootTempFolder = ($tempFolder) ?: sys_get_temp_dir();
        $this->fileSystemHelper = new FileSystemHelper($rootTempFolder);
        $this->tempFolder = $this->fileSystemHelper->createFolder($rootTempFolder, uniqid('sharedstrings'));
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
     * @param  string $filePath
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException If sharedStrings.xml can't be read
     */
    public function extractSharedStrings()
    {
        $xmlReader = new \XMLReader();
        $sharedStringIndex = 0;
        $this->tempFilePointer = null;
        $escaper = new \Box\Spout\Common\Escaper\XLSX();

        $sharedStringsFilePath = 'zip://' . $this->filePath . '#' . self::SHARED_STRINGS_XML_FILE_PATH;
        if ($xmlReader->open($sharedStringsFilePath, null, LIBXML_NONET) === false) {
            throw new IOException('Could not open "' . self::SHARED_STRINGS_XML_FILE_PATH . '".');
        }

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
            $this->writeSharedStringToTempFile($unescapedTextValue, $sharedStringIndex);

            $sharedStringIndex++;

            // jump to the next 'si' tag
            $xmlReader->next('si');
        }

        // close pointer to the last temp file that was written
        if ($this->tempFilePointer) {
            fclose($this->tempFilePointer);
        }

        $xmlReader->close();
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
        $tagsToRemove = array(
            'rPh', // Pronunciation of the text
        );

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
     * Writes the given string to its associated temp file.
     * A new temporary file is created when the previous one has reached its max capacity.
     *
     * @param string $sharedString Shared string to write to the temp file
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return void
     */
    protected function writeSharedStringToTempFile($sharedString, $sharedStringIndex)
    {
        $tempFilePath = $this->getSharedStringTempFilePath($sharedStringIndex);

        if (!file_exists($tempFilePath)) {
            if ($this->tempFilePointer) {
                fclose($this->tempFilePointer);
            }
            $this->tempFilePointer = fopen($tempFilePath, 'w');
        }

        fwrite($this->tempFilePointer, $sharedString . PHP_EOL);
    }

    /**
     * Returns the path for the temp file that should contain the string for the given index
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return string The temp file path for the given index
     */
    protected function getSharedStringTempFilePath($sharedStringIndex)
    {
        $numTempFile = intval($sharedStringIndex / self::MAX_NUM_STRINGS_PER_TEMP_FILE);
        return $this->tempFolder . DIRECTORY_SEPARATOR . 'sharedstrings' . $numTempFile;
    }

    /**
     * Returns the shared string at the given index.
     * Because the strings have been split into different files, it looks for the value in the correct file.
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return string The shared string at the given index
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If no shared string found for the given index
     */
    public function getStringAtIndex($sharedStringIndex)
    {
        $tempFilePath = $this->getSharedStringTempFilePath($sharedStringIndex);
        $indexInFile = $sharedStringIndex % self::MAX_NUM_STRINGS_PER_TEMP_FILE;

        if (!file_exists($tempFilePath)) {
            throw new SharedStringNotFoundException("Shared string temp file not found: $tempFilePath ; for index: $sharedStringIndex");
        }

        if ($this->inMemoryTempFilePath !== $tempFilePath) {
            // free memory
            unset($this->inMemoryTempFileContents);

            $this->inMemoryTempFileContents = explode(PHP_EOL, file_get_contents($tempFilePath));
            $this->inMemoryTempFilePath = $tempFilePath;
        }

        $sharedString = null;
        if (array_key_exists($indexInFile, $this->inMemoryTempFileContents)) {
            $sharedString = $this->inMemoryTempFileContents[$indexInFile];
        }

        if (!$sharedString) {
            throw new SharedStringNotFoundException("Shared string not found for index: $sharedStringIndex");
        }

        return rtrim($sharedString, PHP_EOL);
    }

    /**
     * Deletes the created temporary folder and all its contents
     *
     * @return void
     */
    public function cleanup()
    {
        $this->fileSystemHelper->deleteFolderRecursively($this->tempFolder);
    }
}
