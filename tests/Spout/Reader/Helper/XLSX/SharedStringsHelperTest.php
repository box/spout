<?php

namespace Box\Spout\Reader\Helper\XLSX;

use Box\Spout\TestUsingResource;

/**
 * Class SharedStringsHelperTest
 *
 * @package Box\Spout\Reader\Helper\XLSX
 */
class SharedStringsHelperTest extends \PHPUnit_Framework_TestCase
{
    use TestUsingResource;

    /** @var SharedStringsHelper */
    private $sharedStringsHelper;

    /**
     * @return void
     */
    public function setUp()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_shared_strings.xlsx');
        $this->sharedStringsHelper = new SharedStringsHelper($resourcePath);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->sharedStringsHelper->cleanup();
    }

    /**
     * @return void
     */
    public function testExtractSharedStringsShouldCreateTempFileWithSharedStrings()
    {
        $this->sharedStringsHelper->extractSharedStrings();

        $tempFolder = \ReflectionHelper::getValueOnObject($this->sharedStringsHelper, 'tempFolder');

        $filesInTempFolder = $this->getFilesInFolder($tempFolder);
        $this->assertEquals(1, count($filesInTempFolder), 'One temp file should have been created in the temp folder.');

        $tempFileContents = file_get_contents($filesInTempFolder[0]);
        $tempFileContentsPerLine = explode("\n", $tempFileContents);

        $this->assertEquals('s1--A1', $tempFileContentsPerLine[0]);
        $this->assertEquals('s1--E5', $tempFileContentsPerLine[24]);
    }

    /**
     * Returns all files that are in the given folder.
     * It does not include "." and ".." and is not recursive.
     *
     * @param string $folderPath
     * @return array
     */
    private function getFilesInFolder($folderPath)
    {
        $files = [];
        $directoryIterator = new \DirectoryIterator($folderPath);

        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }

    /**
     * @expectedException \Box\Spout\Reader\Exception\SharedStringNotFoundException
     * @return void
     */
    public function testGetStringAtIndexShouldThrowExceptionIfStringNotFound()
    {
        $this->sharedStringsHelper->extractSharedStrings();
        $this->sharedStringsHelper->getStringAtIndex(PHP_INT_MAX);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldReturnTheCorrectStringIfFound()
    {
        $this->sharedStringsHelper->extractSharedStrings();

        $sharedString = $this->sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals('s1--A1', $sharedString);

        $sharedString = $this->sharedStringsHelper->getStringAtIndex(24);
        $this->assertEquals('s1--E5', $sharedString);
    }
}
