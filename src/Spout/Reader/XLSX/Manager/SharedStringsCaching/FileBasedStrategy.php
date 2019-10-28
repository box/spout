<?php

namespace Box\Spout\Reader\XLSX\Manager\SharedStringsCaching;

use Box\Spout\Reader\Exception\SharedStringNotFoundException;
use Box\Spout\Reader\XLSX\Creator\HelperFactory;

/**
 * Class FileBasedStrategy
 *
 * This class implements the file-based caching strategy for shared strings.
 * Shared strings are stored in small files (with a max number of strings per file).
 * This strategy is slower than an in-memory strategy but is used to avoid out of memory crashes.
 */
class FileBasedStrategy implements CachingStrategyInterface
{
    /** Value to use to escape the line feed character ("\n") */
    const ESCAPED_LINE_FEED_CHARACTER = '_x000A_';

    /** Index entry size uint32 for offset and uint16 for length */
    const INDEX_ENTRY_SIZE = 6;

    /** @var \Box\Spout\Common\Helper\GlobalFunctionsHelper Helper to work with global functions */
    protected $globalFunctionsHelper;

    /** @var \Box\Spout\Common\Helper\FileSystemHelper Helper to perform file system operations */
    protected $fileSystemHelper;

    /** @var string Temporary folder where the temporary files will be created */
    protected $tempFolder;

    /**
     * @var int Maximum number of strings that can be stored in one temp file
     * @see CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE
     */
    protected $maxNumStringsPerTempFile;

    /** @var resource Pointer to the last temp file a shared string was written to */
    protected $tempFilePointers;

    /**
     * @param string $tempFolder Temporary folder where the temporary files to store shared strings will be stored
     * @param int $maxNumStringsPerTempFile Maximum number of strings that can be stored in one temp file
     * @param HelperFactory $helperFactory Factory to create helpers
     */
    public function __construct($tempFolder, $maxNumStringsPerTempFile, $helperFactory)
    {
        $this->fileSystemHelper = $helperFactory->createFileSystemHelper($tempFolder);
        $this->tempFolder = $this->fileSystemHelper->createFolder($tempFolder, uniqid('sharedstrings'));

        $this->maxNumStringsPerTempFile = $maxNumStringsPerTempFile;

        $this->globalFunctionsHelper = $helperFactory->createGlobalFunctionsHelper();
        $this->tempFilePointers = [];
    }

    /**
     * Open file with cache
     *
     * @param string $tempFilePath filename with shared strings
     */
    private function openCache($tempFilePath)
    {
        if (!array_key_exists($tempFilePath, $this->tempFilePointers)) {
            // Open index file and seek to end
            $index = $this->globalFunctionsHelper->fopen($tempFilePath . '.index', 'c+');
            $this->globalFunctionsHelper->fseek($index, 0, SEEK_END);

            // Open data file and seek to end
            $data = $this->globalFunctionsHelper->fopen($tempFilePath, 'c+');
            $this->globalFunctionsHelper->fseek($data, 0, SEEK_END);

            $this->tempFilePointers[$tempFilePath] = [$index, $data];
        }

        return $this->tempFilePointers[$tempFilePath];
    }

    /**
     * Adds the given string to the cache.
     *
     * @param string $sharedString The string to be added to the cache
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return void
     */
    public function addStringForIndex($sharedString, $sharedStringIndex)
    {
        $tempFilePath = $this->getSharedStringTempFilePath($sharedStringIndex);

        list($index, $data) = $this->openCache($tempFilePath);

        // The shared string retrieval logic expects each cell data to be on one line only
        // Encoding the line feed character allows to preserve this assumption
        $lineFeedEncodedSharedString = $this->escapeLineFeed($sharedString);

        $this->globalFunctionsHelper->fwrite($index, pack('Nn', $this->globalFunctionsHelper->ftell($data), strlen($lineFeedEncodedSharedString) + strlen(PHP_EOL)));
        $this->globalFunctionsHelper->fwrite($data, $lineFeedEncodedSharedString . PHP_EOL);
    }

    /**
     * Returns the path for the temp file that should contain the string for the given index
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @return string The temp file path for the given index
     */
    protected function getSharedStringTempFilePath($sharedStringIndex)
    {
        $numTempFile = (int) ($sharedStringIndex / $this->maxNumStringsPerTempFile);

        return $this->tempFolder . '/sharedstrings' . $numTempFile;
    }

    /**
     * Closes the cache after the last shared string was added.
     * This prevents any additional string from being added to the cache.
     *
     * @return void
     */
    public function closeCache()
    {
        // close pointer to the last temp file that was written
        if (!empty($this->tempFilePointers)) {
            foreach ($this->tempFilePointers as $pointer) {
                $this->globalFunctionsHelper->fclose($pointer[0]);
                $this->globalFunctionsHelper->fclose($pointer[1]);
            }
        }
        $this->tempFilePointers = [];
    }

    /**
     * Returns the string located at the given index from the cache.
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     * @throws \Box\Spout\Reader\Exception\SharedStringNotFoundException If no shared string found for the given index
     * @return string The shared string at the given index
     */
    public function getStringAtIndex($sharedStringIndex)
    {
        $tempFilePath = $this->getSharedStringTempFilePath($sharedStringIndex);
        $indexInFile = $sharedStringIndex % $this->maxNumStringsPerTempFile;

        if (!$this->globalFunctionsHelper->file_exists($tempFilePath)) {
            throw new SharedStringNotFoundException("Shared string temp file not found: $tempFilePath ; for index: $sharedStringIndex");
        }

        list($index, $data) = $this->openCache($tempFilePath);

        // Read index entry
        $this->globalFunctionsHelper->fseek($index, $indexInFile * self::INDEX_ENTRY_SIZE);
        $indexEntryBytes = $this->globalFunctionsHelper->fread($index, self::INDEX_ENTRY_SIZE);
        $indexEntry = unpack('Noffset/nlen', $indexEntryBytes);

        $sharedString = null;
        if ($indexEntry['offset'] + $indexEntry['len'] <= filesize($tempFilePath)) {
            $this->globalFunctionsHelper->fseek($data, $indexEntry['offset']);
            $escapedSharedString  = $this->globalFunctionsHelper->fread($data, $indexEntry['len']);
            $sharedString = $this->unescapeLineFeed($escapedSharedString);
        }

        if ($sharedString === null) {
            throw new SharedStringNotFoundException("Shared string not found for index: $sharedStringIndex");
        }

        return rtrim($sharedString, PHP_EOL);
    }

    /**
     * Escapes the line feed characters (\n)
     *
     * @param string $unescapedString
     * @return string
     */
    private function escapeLineFeed($unescapedString)
    {
        return str_replace("\n", self::ESCAPED_LINE_FEED_CHARACTER, $unescapedString);
    }

    /**
     * Unescapes the line feed characters (\n)
     *
     * @param string $escapedString
     * @return string
     */
    private function unescapeLineFeed($escapedString)
    {
        return str_replace(self::ESCAPED_LINE_FEED_CHARACTER, "\n", $escapedString);
    }

    /**
     * Destroys the cache, freeing memory and removing any created artifacts
     *
     * @return void
     */
    public function clearCache()
    {
        if ($this->tempFolder) {
            $this->fileSystemHelper->deleteFolderRecursively($this->tempFolder);
        }
    }
}
