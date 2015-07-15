<?php

namespace Box\Spout\Reader\XLSX\Helper;

use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\CachingStrategyFactory;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\FileBasedStrategy;
use Box\Spout\Reader\XLSX\Helper\SharedStringsCaching\InMemoryStrategy;
use Box\Spout\TestUsingResource;

/**
 * Class SharedStringsHelperTest
 *
 * @package Box\Spout\Reader\XLSX\Helper
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

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($this->sharedStringsHelper, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof InMemoryStrategy);
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexShouldWorkWithMultilineStrings()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_shared_multiline_strings.xlsx');
        $sharedStringsHelper = new SharedStringsHelper($resourcePath);

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals("s1\nA1", $sharedString);

        $sharedString = $sharedStringsHelper->getStringAtIndex(24);
        $this->assertEquals("s1\nE5", $sharedString);

        $sharedStringsHelper->cleanup();
    }

    /**
     * @return void
     */
    public function testGetStringAtIndexWithFileBasedStrategy()
    {
        // force the file-based strategy by setting no memory limit
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        $resourcePath = $this->getResourcePath('sheet_with_lots_of_shared_strings.xlsx');
        $sharedStringsHelper = new SharedStringsHelper($resourcePath);

        $sharedStringsHelper->extractSharedStrings();

        $sharedString = $sharedStringsHelper->getStringAtIndex(0);
        $this->assertEquals('str', $sharedString);

        $sharedString = $sharedStringsHelper->getStringAtIndex(CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 1);
        $this->assertEquals('str', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsHelper, 'cachingStrategy');
        $this->assertTrue($usedCachingStrategy instanceof FileBasedStrategy);

        $sharedStringsHelper->cleanup();

        ini_set('memory_limit', $originalMemoryLimit);
    }
}
